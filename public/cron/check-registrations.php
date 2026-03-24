<?php
/**
 * Registration Expiry Notification Cron
 *
 * Run daily via Hostinger cron scheduler:
 *   php /path/to/public/cron/check-registrations.php
 *
 * Or via HTTP (requires CRON_SECRET in .env):
 *   GET /cron/check-registrations.php?secret=<CRON_SECRET>
 *
 * Sends escalating email alerts to staff, line manager, and org admin
 * at 90 / 60 / 30 / 14 / 7 / 0 days before expiry, then weekly for 4
 * weeks after expiry. Uses INSERT IGNORE on the notification log so
 * re-runs are safe — no duplicate emails.
 */

$isCli = (php_sapi_name() === 'cli');

// ── Bootstrap ─────────────────────────────────────────────────────────────────
$root = dirname(__DIR__, 2);
require_once $root . '/config/config.php';

// ── Auth: CLI runs freely; HTTP requires CRON_SECRET ─────────────────────────
if (!$isCli) {
    $cronSecret = getenv('CRON_SECRET') ?: '';
    $provided   = $_GET['secret'] ?? '';
    if (!$cronSecret || !hash_equals($cronSecret, $provided)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

// ── Run ───────────────────────────────────────────────────────────────────────
$log      = [];
$sent     = 0;
$skipped  = 0;

$registrations = StaffRegistration::findAllForNotificationCheck();
$thresholds    = StaffRegistration::thresholds();

foreach ($registrations as $reg) {
    $daysUntil = (int) $reg['days_until'];
    $regId     = (int) $reg['id'];
    $orgId     = (int) $reg['organisation_id'];
    $name      = htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']);

    // Work out which thresholds we've crossed but haven't notified for yet
    foreach ($thresholds as $key => $recipientTypes) {
        if ($daysUntil > $key) {
            // Not reached this threshold yet
            continue;
        }

        foreach ($recipientTypes as $type) {
            if (StaffRegistration::notificationAlreadySent($regId, $key, $type)) {
                $skipped++;
                continue;
            }

            $email = match ($type) {
                'staff'     => $reg['staff_email'] ?? null,
                'manager'   => $reg['mgr_email']   ?? null,
                'org_admin' => null, // handled below (multiple admins)
                default     => null,
            };

            if ($type === 'org_admin') {
                $adminEmails = StaffRegistration::getOrgAdminEmails($orgId);
                foreach ($adminEmails as $adminEmail) {
                    if (!$adminEmail) continue;
                    $subject = buildSubject($reg, $daysUntil, $type);
                    $body    = buildBody($reg, $daysUntil, $type, $adminEmail);
                    if (sendMail($adminEmail, $subject, $body)) {
                        StaffRegistration::logNotification($regId, $key, $type, $adminEmail);
                        $log[] = "  SENT {$type} → {$adminEmail} (threshold {$key})";
                        $sent++;
                    }
                }
            } elseif ($email) {
                $subject = buildSubject($reg, $daysUntil, $type);
                $body    = buildBody($reg, $daysUntil, $type, $email);
                if (sendMail($email, $subject, $body)) {
                    StaffRegistration::logNotification($regId, $key, $type, $email);
                    $log[] = "  SENT {$type} → {$email} (threshold {$key})";
                    $sent++;
                }
            } else {
                $skipped++;
            }
        }
    }
}

$summary = sprintf(
    "[%s] Registration check complete. %d emails sent, %d skipped. %d registrations checked.",
    date('Y-m-d H:i:s'),
    $sent,
    $skipped,
    count($registrations)
);

if ($isCli) {
    echo $summary . "\n";
    foreach ($log as $line) echo $line . "\n";
} else {
    header('Content-Type: application/json');
    echo json_encode(['summary' => $summary, 'log' => $log]);
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function buildSubject(array $reg, int $daysUntil, string $recipientType): string
{
    $staffName = $reg['first_name'] . ' ' . $reg['last_name'];
    $body      = $reg['registration_type'] ?? 'Professional Registration';
    $appName   = APP_NAME;

    if ($daysUntil < 0) {
        $days = abs($daysUntil);
        return "[{$appName}] EXPIRED {$days} days ago: {$body} — {$staffName}";
    }
    if ($daysUntil === 0) {
        return "[{$appName}] EXPIRES TODAY: {$body} — {$staffName}";
    }
    $urgency = $daysUntil <= 14 ? 'URGENT: ' : '';
    return "[{$appName}] {$urgency}{$body} expires in {$daysUntil} days — {$staffName}";
}

function buildBody(array $reg, int $daysUntil, string $recipientType, string $toEmail): string
{
    $staffName   = htmlspecialchars($reg['first_name'] . ' ' . $reg['last_name']);
    $regBody     = htmlspecialchars($reg['registration_type'] ?? 'Professional Registration');
    $regNumber   = htmlspecialchars($reg['registration_number'] ?? 'N/A');
    $expiryDate  = date(DATE_FORMAT, strtotime($reg['expiry_date']));
    $appName     = APP_NAME;
    $appUrl      = APP_URL;
    $profileUrl  = $appUrl . '/public/staff/view.php?id=' . (int)$reg['person_id'];

    $greeting = match ($recipientType) {
        'staff'   => "Dear {$staffName},",
        'manager' => 'Dear ' . htmlspecialchars(($reg['mgr_first'] ?? '') . ' ' . ($reg['mgr_last'] ?? '')) . ',',
        default   => 'Dear Administrator,',
    };

    if ($daysUntil < 0) {
        $days     = abs($daysUntil);
        $urgency  = '<span style="color:#dc2626;font-weight:600">EXPIRED ' . $days . ' days ago</span>';
        $action   = $recipientType === 'staff'
            ? 'Please contact <strong>' . htmlspecialchars($reg['registration_type'] ?? 'SSSC') . '</strong> immediately to begin the reinstatement process. You may not legally work in a regulated care service until your registration is restored.'
            : "<strong>{$staffName}</strong>'s registration has lapsed. Please contact them immediately. They cannot legally work in a regulated care service until it is reinstated.";
    } elseif ($daysUntil === 0) {
        $urgency = '<span style="color:#dc2626;font-weight:600">EXPIRES TODAY</span>';
        $action  = $recipientType === 'staff'
            ? 'Your registration expires today. If you have not already submitted a renewal, please do so immediately.'
            : "This registration expires today. Please check that {$staffName} has submitted their renewal.";
    } else {
        $colour  = $daysUntil <= 14 ? '#dc2626' : ($daysUntil <= 30 ? '#d97706' : '#059669');
        $urgency = "<span style=\"color:{$colour};font-weight:600\">{$daysUntil} days remaining</span>";
        $action  = $recipientType === 'staff'
            ? 'Please log in to the <strong>' . htmlspecialchars($reg['registration_type'] ?? 'SSSC') . '</strong> portal and begin the renewal process if you have not already done so.'
            : "Please ensure {$staffName} has started their renewal process.";
    }

    return "
<html><body style='font-family:sans-serif;color:#0f172a;max-width:600px;margin:0 auto'>
<div style='background:#1c2b3a;padding:1.25rem 1.5rem'>
  <p style='color:white;margin:0;font-size:1rem;font-weight:600'>{$appName}</p>
</div>
<div style='padding:2rem 1.5rem'>
  <p>{$greeting}</p>
  <p>This is an automated reminder about a professional registration that requires attention.</p>
  <table style='width:100%;border-collapse:collapse;margin:1.5rem 0;font-size:.9rem'>
    <tr style='background:#f8fafc'>
      <td style='padding:.6rem 1rem;font-weight:600;border:1px solid #e2e8f0;width:160px'>Staff member</td>
      <td style='padding:.6rem 1rem;border:1px solid #e2e8f0'>{$staffName}</td>
    </tr>
    <tr>
      <td style='padding:.6rem 1rem;font-weight:600;border:1px solid #e2e8f0'>Registration</td>
      <td style='padding:.6rem 1rem;border:1px solid #e2e8f0'>{$regBody}</td>
    </tr>
    <tr style='background:#f8fafc'>
      <td style='padding:.6rem 1rem;font-weight:600;border:1px solid #e2e8f0'>Reg. number</td>
      <td style='padding:.6rem 1rem;border:1px solid #e2e8f0'>{$regNumber}</td>
    </tr>
    <tr>
      <td style='padding:.6rem 1rem;font-weight:600;border:1px solid #e2e8f0'>Expiry date</td>
      <td style='padding:.6rem 1rem;border:1px solid #e2e8f0'>{$expiryDate} &nbsp; {$urgency}</td>
    </tr>
  </table>
  <p>{$action}</p>
  <p style='margin-top:1.5rem'>
    <a href='{$profileUrl}'
       style='background:#0d9488;color:white;padding:.6rem 1.25rem;border-radius:6px;text-decoration:none;font-weight:600'>
      View Staff Record
    </a>
  </p>
  <hr style='border:none;border-top:1px solid #e2e8f0;margin:2rem 0'>
  <p style='color:#475569;font-size:.8rem'>
    This is an automated message from {$appName}. Do not reply to this email.<br>
    To manage registration records, visit <a href='{$appUrl}'>{$appUrl}</a>.
  </p>
</div>
</body></html>";
}

function sendMail(string $to, string $subject, string $body): bool
{
    $from = getenv('MAIL_FROM') ?: ('noreply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com'));
    $headers = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . APP_NAME . ' <' . $from . '>',
        'X-Mailer: PHP/' . phpversion(),
    ]);
    return mail($to, $subject, $body, $headers);
}
