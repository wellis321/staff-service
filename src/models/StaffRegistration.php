<?php
/**
 * StaffRegistration Model
 * Handles professional registrations and certifications for staff members
 */

class StaffRegistration {
    
    /**
     * Find registration by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT sr.*, p.first_name, p.last_name, p.employee_reference
            FROM staff_registrations sr
            JOIN people p ON sr.person_id = p.id
            WHERE sr.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND sr.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get all registrations for a staff member
     */
    public static function getByPersonId($personId, $organisationId = null, $activeOnly = false) {
        $db = getDbConnection();
        
        $query = "
            SELECT sr.*
            FROM staff_registrations sr
            WHERE sr.person_id = ?
        ";
        
        $params = [$personId];
        
        if ($organisationId !== null) {
            $query .= " AND sr.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        if ($activeOnly) {
            $query .= " AND sr.is_active = TRUE";
        }
        
        $query .= " ORDER BY sr.expiry_date ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get registrations expiring soon (within specified days)
     */
    public static function getExpiringSoon($organisationId, $days = 90) {
        $db = getDbConnection();
        
        $query = "
            SELECT sr.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_registrations sr
            JOIN people p ON sr.person_id = p.id
            WHERE sr.organisation_id = ?
            AND sr.is_active = TRUE
            AND sr.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY sr.expiry_date ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$organisationId, $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get expired registrations
     */
    public static function getExpired($organisationId) {
        $db = getDbConnection();
        
        $query = "
            SELECT sr.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_registrations sr
            JOIN people p ON sr.person_id = p.id
            WHERE sr.organisation_id = ?
            AND sr.is_active = TRUE
            AND sr.expiry_date < CURDATE()
            ORDER BY sr.expiry_date DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new registration
     */
    public static function create($data) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO staff_registrations (
                    person_id, organisation_id, registration_type, registration_number,
                    registration_body, issue_date, expiry_date, renewal_date,
                    is_active, is_required_for_role, notes, document_path
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['person_id'],
                $data['organisation_id'],
                $data['registration_type'],
                $data['registration_number'] ?? null,
                $data['registration_body'] ?? null,
                $data['issue_date'] ?? null,
                $data['expiry_date'],
                $data['renewal_date'] ?? null,
                $data['is_active'] ?? true,
                $data['is_required_for_role'] ?? true,
                $data['notes'] ?? null,
                $data['document_path'] ?? null
            ]);
            
            $id = $db->lastInsertId();
            $db->commit();
            
            return self::findById($id, $data['organisation_id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error creating staff registration: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update registration
     */
    public static function update($id, $data, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $allowedFields = [
                'registration_type', 'registration_number', 'registration_body',
                'issue_date', 'expiry_date', 'renewal_date',
                'is_active', 'is_required_for_role', 'notes', 'document_path'
            ];
            
            $fields = [];
            $params = [];
            
            foreach ($allowedFields as $field) {
                if (isset($data[$field])) {
                    $fields[] = "$field = ?";
                    $params[] = $data[$field];
                }
            }
            
            if (empty($fields)) {
                $db->rollBack();
                return null;
            }
            
            $params[] = $id;
            $query = "UPDATE staff_registrations SET " . implode(', ', $fields) . " WHERE id = ?";
            
            if ($organisationId !== null) {
                $query .= " AND organisation_id = ?";
                $params[] = $organisationId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $db->commit();
            
            return self::findById($id, $organisationId);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error updating staff registration: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete registration
     */
    public static function delete($id, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $query = "DELETE FROM staff_registrations WHERE id = ?";
            $params = [$id];
            
            if ($organisationId !== null) {
                $query .= " AND organisation_id = ?";
                $params[] = $organisationId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error deleting staff registration: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate days until expiry.
     * Returns a positive integer if in the future, negative if expired.
     */
    public static function daysUntilExpiry($expiryDate) {
        if (!$expiryDate) {
            return null;
        }

        $expiry = new DateTime($expiryDate);
        $today  = new DateTime('today');
        $diff   = (int) $today->diff($expiry)->days;

        return $expiry >= $today ? $diff : -$diff;
    }

    // ── Status helpers ────────────────────────────────────────────────────────

    /**
     * Notification thresholds: key = days before/after expiry,
     * value = which recipient types get alerted.
     */
    public static function thresholds(): array
    {
        return [
            90 => ['staff'],
            60 => ['staff', 'manager'],
            30 => ['staff', 'manager', 'org_admin'],
            14 => ['staff', 'manager', 'org_admin'],
             7 => ['staff', 'manager', 'org_admin'],
             0 => ['staff', 'manager', 'org_admin'],
            -7 => ['manager', 'org_admin'],
           -14 => ['manager', 'org_admin'],
           -21 => ['manager', 'org_admin'],
           -28 => ['manager', 'org_admin'],
        ];
    }

    /**
     * Derive a status string from days-until-expiry.
     * Returns: active | expiring_soon | expiring_critical | expired
     */
    public static function statusFromDays(?int $days): string
    {
        if ($days === null)  return 'unknown';
        if ($days < 0)       return 'expired';
        if ($days <= 14)     return 'expiring_critical';
        if ($days <= 90)     return 'expiring_soon';
        return 'active';
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'expired'           => 'Expired',
            'expiring_critical' => 'Expiring soon',
            'expiring_soon'     => 'Due for renewal',
            default             => 'Active',
        };
    }

    public static function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'expired'           => 'badge-red',
            'expiring_critical' => 'badge-amber',
            'expiring_soon'     => 'badge-yellow',
            default             => 'badge-green',
        };
    }

    // ── Organisation-wide queries ─────────────────────────────────────────────

    /**
     * All registrations for an organisation, soonest expiry first.
     * Includes staff name, job title, and line manager email.
     */
    public static function findByOrganisation(int $orgId, bool $activeOnly = true): array
    {
        $db  = getDbConnection();
        $sql = '
            SELECT sr.*,
                   p.first_name, p.last_name, p.email AS staff_email,
                   p.employee_reference,
                   sp.job_title,
                   mgr.first_name AS mgr_first,
                   mgr.last_name  AS mgr_last,
                   mgr.email      AS mgr_email
            FROM   staff_registrations sr
            JOIN   people p             ON p.id  = sr.person_id
            LEFT   JOIN staff_profiles sp   ON sp.person_id = sr.person_id
            LEFT   JOIN people mgr          ON mgr.id = sp.line_manager_id
            WHERE  sr.organisation_id = ?
        ';
        $params = [$orgId];
        if ($activeOnly) {
            $sql .= ' AND sr.is_active = 1';
        }
        $sql .= ' ORDER BY sr.expiry_date ASC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // Attach derived status
        foreach ($rows as &$row) {
            $days = self::daysUntilExpiry($row['expiry_date']);
            $row['days_until'] = $days;
            $row['reg_status'] = self::statusFromDays($days);
        }
        return $rows;
    }

    /**
     * All active registrations expiring within 90 days OR expired within 28 days.
     * Used by the cron notification script — covers all organisations.
     */
    public static function findAllForNotificationCheck(): array
    {
        $db   = getDbConnection();
        $stmt = $db->prepare('
            SELECT sr.*,
                   p.first_name, p.last_name, p.email AS staff_email,
                   p.employee_reference,
                   sp.job_title,
                   mgr.first_name AS mgr_first,
                   mgr.last_name  AS mgr_last,
                   mgr.email      AS mgr_email
            FROM   staff_registrations sr
            JOIN   people p             ON p.id  = sr.person_id
            LEFT   JOIN staff_profiles sp   ON sp.person_id = sr.person_id
            LEFT   JOIN people mgr          ON mgr.id = sp.line_manager_id
            WHERE  sr.is_active = 1
              AND  sr.expiry_date >= DATE_SUB(CURDATE(), INTERVAL 28 DAY)
              AND  sr.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            ORDER  BY sr.expiry_date ASC
        ');
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $days = self::daysUntilExpiry($row['expiry_date']);
            $row['days_until'] = $days;
            $row['reg_status'] = self::statusFromDays($days);
        }
        return $rows;
    }

    // ── Notification helpers ──────────────────────────────────────────────────

    public static function notificationAlreadySent(int $registrationId, int $thresholdKey, string $recipientType): bool
    {
        $db   = getDbConnection();
        $stmt = $db->prepare('
            SELECT COUNT(*) FROM registration_notifications
            WHERE registration_id = ? AND threshold_key = ? AND recipient_type = ?
        ');
        $stmt->execute([$registrationId, $thresholdKey, $recipientType]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function logNotification(int $registrationId, int $thresholdKey, string $recipientType, string $email): void
    {
        $db = getDbConnection();
        $db->prepare('
            INSERT IGNORE INTO registration_notifications
                (registration_id, threshold_key, recipient_type, recipient_email)
            VALUES (?, ?, ?, ?)
        ')->execute([$registrationId, $thresholdKey, $recipientType, $email]);
    }

    /**
     * Organisation admin email addresses for a given org.
     */
    public static function getOrgAdminEmails(int $orgId): array
    {
        $db   = getDbConnection();
        $stmt = $db->prepare('
            SELECT u.email FROM users u
            JOIN   user_roles ur ON ur.user_id = u.id
            JOIN   roles r       ON r.id = ur.role_id
            WHERE  u.organisation_id = ?
              AND  r.name IN ("organisation_admin", "superadmin")
              AND  u.is_active = 1
        ');
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

