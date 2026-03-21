<?php
/**
 * ProfileChangeNotifications
 *
 * Sends email notifications for the staff self-service approval workflow:
 *   - To the manager when a staff member submits changes for review
 *   - To the staff member when their change is approved or rejected
 *
 * Uses the same Email::sendEmail pattern as the rest of the application
 * (PHP mail() with HTML content, MAIL_FROM / MAIL_REPLY_TO from env).
 */

class ProfileChangeNotifications {

    // ─── Notify manager ───────────────────────────────────────────────────────

    /**
     * Tell the line manager (or HR admin) that a staff member has submitted
     * one or more profile changes awaiting their approval.
     *
     * @param string $managerEmail
     * @param string $managerFirstName
     * @param string $staffFullName      Display name of the staff member
     * @param array  $fieldLabels        Human-readable names of the changed fields
     * @param string $approveUrl         Direct link to the approval inbox
     * @return bool
     */
    public static function notifyManagerOfPendingChanges(
        string $managerEmail,
        string $managerFirstName,
        string $staffFullName,
        array  $fieldLabels,
        string $approveUrl
    ): bool {
        $count   = count($fieldLabels);
        $subject = $staffFullName . ' has submitted ' . $count . ' profile change' . ($count !== 1 ? 's' : '') . ' for approval — ' . APP_NAME;

        $fieldList = '';
        foreach ($fieldLabels as $label) {
            $fieldList .= '<li style="margin-bottom: 4px;">' . htmlspecialchars($label) . '</li>';
        }

        $message = self::wrap('
            <p>Hello ' . htmlspecialchars($managerFirstName) . ',</p>

            <p>
                <strong>' . htmlspecialchars($staffFullName) . '</strong>
                has updated their staff profile and the following
                ' . ($count !== 1 ? $count . ' changes require' : 'change requires') . ' your approval
                before they take effect:
            </p>

            <ul style="margin: 16px 0; padding-left: 20px;">
                ' . $fieldList . '
            </ul>

            <p style="text-align: center; margin: 28px 0;">
                <a href="' . htmlspecialchars($approveUrl) . '" class="button">
                    Review Changes
                </a>
            </p>

            <p style="color: #6b7280; font-size: 13px;">
                If you were not expecting this notification, please log in and check
                your approval inbox. Do not approve changes you do not recognise.
            </p>
        ', 'Profile Change Approval Required');

        return self::send($managerEmail, $subject, $message);
    }

    // ─── Notify staff member ──────────────────────────────────────────────────

    /**
     * Tell the staff member that one of their change requests has been approved.
     *
     * @param string $staffEmail
     * @param string $staffFirstName
     * @param string $fieldLabel
     * @param string $profileUrl      Link to their self-service profile page
     * @return bool
     */
    public static function notifyStaffChangeApproved(
        string $staffEmail,
        string $staffFirstName,
        string $fieldLabel,
        string $profileUrl
    ): bool {
        $subject = 'Your profile change has been approved — ' . APP_NAME;

        $message = self::wrap('
            <p>Hello ' . htmlspecialchars($staffFirstName) . ',</p>

            <p>
                Your request to update your
                <strong>' . htmlspecialchars($fieldLabel) . '</strong>
                has been <span style="color: #10b981; font-weight: 600;">approved</span>
                and is now live on your profile.
            </p>

            <p style="text-align: center; margin: 28px 0;">
                <a href="' . htmlspecialchars($profileUrl) . '" class="button">
                    View My Profile
                </a>
            </p>
        ', 'Profile Change Approved');

        return self::send($staffEmail, $subject, $message);
    }

    /**
     * Tell the staff member that one of their change requests has been rejected,
     * including the reason so they know what to correct.
     *
     * @param string $staffEmail
     * @param string $staffFirstName
     * @param string $fieldLabel
     * @param string $rejectionReason
     * @param string $profileUrl
     * @return bool
     */
    public static function notifyStaffChangeRejected(
        string $staffEmail,
        string $staffFirstName,
        string $fieldLabel,
        string $rejectionReason,
        string $profileUrl
    ): bool {
        $subject = 'Your profile change was not approved — ' . APP_NAME;

        $message = self::wrap('
            <p>Hello ' . htmlspecialchars($staffFirstName) . ',</p>

            <p>
                Your request to update your
                <strong>' . htmlspecialchars($fieldLabel) . '</strong>
                has been <span style="color: #ef4444; font-weight: 600;">not approved</span>.
            </p>

            <div style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 12px 16px; margin: 20px 0;">
                <p style="margin: 0; font-weight: 600; margin-bottom: 6px;">Reason given:</p>
                <p style="margin: 0; color: #374151;">' . nl2br(htmlspecialchars($rejectionReason)) . '</p>
            </div>

            <p>
                Please review the reason above, make any necessary corrections, and
                resubmit your change from your staff profile page.
            </p>

            <p style="text-align: center; margin: 28px 0;">
                <a href="' . htmlspecialchars($profileUrl) . '" class="button">
                    Update My Profile
                </a>
            </p>

            <p style="color: #6b7280; font-size: 13px;">
                If you believe this decision is incorrect, please speak to your line manager
                or contact HR directly.
            </p>
        ', 'Profile Change Not Approved');

        return self::send($staffEmail, $subject, $message);
    }

    // ─── Dispatch helpers (look up recipients from DB) ────────────────────────

    /**
     * Look up the line manager's email and fire a manager notification.
     * Falls back to all organisation admins if the person has no line manager.
     *
     * @param PDO    $db
     * @param array  $person         Row from people + staff_profiles join
     * @param int    $organisationId
     * @param array  $fieldLabels    Human-readable names of the changed fields
     */
    public static function dispatchManagerNotification(PDO $db, array $person, int $organisationId, array $fieldLabels): void {
        if (empty($fieldLabels)) {
            return;
        }

        $staffFullName = trim(($person['first_name'] ?? '') . ' ' . ($person['last_name'] ?? ''));
        $approveUrl    = APP_URL . url('staff/approve-changes.php');

        $managerEmail     = null;
        $managerFirstName = null;

        if (!empty($person['line_manager_id'])) {
            // Find the line manager's linked user account
            $stmt = $db->prepare("
                SELECT u.email, u.first_name
                FROM people p
                JOIN users u ON p.user_id = u.id
                WHERE p.id = ? AND p.organisation_id = ?
                LIMIT 1
            ");
            $stmt->execute([$person['line_manager_id'], $organisationId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                $managerEmail     = $row['email'];
                $managerFirstName = $row['first_name'];
            }
        }

        if ($managerEmail) {
            self::notifyManagerOfPendingChanges($managerEmail, $managerFirstName, $staffFullName, $fieldLabels, $approveUrl);
            return;
        }

        // No line manager — notify all organisation admins
        $adminStmt = $db->prepare("
            SELECT u.email, u.first_name
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN roles r ON ur.role_id = r.id
            WHERE u.organisation_id = ?
              AND r.name IN ('admin', 'organisation_admin')
              AND u.is_active = 1
        ");
        $adminStmt->execute([$organisationId]);
        foreach ($adminStmt->fetchAll(PDO::FETCH_ASSOC) as $admin) {
            self::notifyManagerOfPendingChanges($admin['email'], $admin['first_name'], $staffFullName, $fieldLabels, $approveUrl);
        }
    }

    /**
     * Look up the staff member's email and send an approval notification.
     *
     * @param PDO   $db
     * @param array $change  Row from pending_profile_changes
     */
    public static function dispatchStaffApprovedNotification(PDO $db, array $change): void {
        $staffDetails = self::lookUpStaffEmail($db, (int) $change['person_id']);
        if (!$staffDetails) {
            return;
        }

        self::notifyStaffChangeApproved(
            $staffDetails['email'],
            $staffDetails['first_name'],
            $change['field_label'],
            APP_URL . url('staff/my-profile.php')
        );
    }

    /**
     * Look up the staff member's email and send a rejection notification.
     *
     * @param PDO    $db
     * @param array  $change  Row from pending_profile_changes
     * @param string $reason
     */
    public static function dispatchStaffRejectedNotification(PDO $db, array $change, string $reason): void {
        $staffDetails = self::lookUpStaffEmail($db, (int) $change['person_id']);
        if (!$staffDetails) {
            return;
        }

        self::notifyStaffChangeRejected(
            $staffDetails['email'],
            $staffDetails['first_name'],
            $change['field_label'],
            $reason,
            APP_URL . url('staff/my-profile.php')
        );
    }

    /**
     * Returns ['email' => ..., 'first_name' => ...] for the user linked to a person,
     * or null if no linked user account exists.
     */
    private static function lookUpStaffEmail(PDO $db, int $personId): ?array {
        $stmt = $db->prepare("
            SELECT u.email, u.first_name
            FROM people p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = ?
            LIMIT 1
        ");
        $stmt->execute([$personId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // ─── Shared HTML wrapper ──────────────────────────────────────────────────

    /**
     * Wraps email body content in the standard application HTML template.
     */
    private static function wrap(string $body, string $heading): string {
        return '
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <style>
                body        { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
                .container  { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header     { background-color: #2563eb; color: white; padding: 24px 20px; }
                .header h1  { margin: 0; font-size: 20px; }
                .content    { padding: 24px 20px; background-color: #f9fafb; }
                .footer     { padding: 16px 20px; text-align: center; color: #9ca3af; font-size: 12px; }
                a.button    { display: inline-block; padding: 12px 28px; background-color: #2563eb;
                              color: white; text-decoration: none; font-weight: 600; }
                p           { margin: 0 0 16px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . htmlspecialchars(APP_NAME) . '</h1>
                    <p style="margin: 4px 0 0; font-size: 14px; opacity: 0.85;">' . htmlspecialchars($heading) . '</p>
                </div>
                <div class="content">
                    ' . $body . '
                </div>
                <div class="footer">
                    <p>This is an automated message from ' . htmlspecialchars(APP_NAME) . '.</p>
                    <p>Please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>';
    }

    // ─── Low-level send ───────────────────────────────────────────────────────

    private static function send(string $to, string $subject, string $body): bool {
        $from    = getenv('MAIL_FROM')     ?: 'noreply@example.com';
        $replyTo = getenv('MAIL_REPLY_TO') ?: 'support@example.com';

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: '     . APP_NAME . ' <' . $from . '>',
            'Reply-To: ' . $replyTo,
            'X-Mailer: PHP/' . phpversion(),
        ]);

        return mail($to, $subject, $body, $headers);
    }
}
