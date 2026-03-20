<?php
/**
 * StaffAppraisal Model
 * Manages annual appraisals and performance reviews for staff members
 */

class StaffAppraisal {

    public static function getByPersonId($personId, $organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT sa.*,
                   CONCAT(ap.first_name, ' ', ap.last_name) AS appraiser_full_name
            FROM staff_appraisals sa
            LEFT JOIN people ap ON ap.id = sa.appraiser_person_id
            WHERE sa.person_id = ? AND sa.organisation_id = ?
            ORDER BY sa.appraisal_date DESC
        ");
        $stmt->execute([$personId, $organisationId]);
        return $stmt->fetchAll();
    }

    public static function getDueWithin($organisationId, $days = 30) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT sa.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_appraisals sa
            JOIN people p ON p.id = sa.person_id
            WHERE sa.organisation_id = ?
              AND sa.next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY sa.next_due_date ASC
        ");
        $stmt->execute([$organisationId, $days]);
        return $stmt->fetchAll();
    }

    public static function getOverdue($organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT sa.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_appraisals sa
            JOIN people p ON p.id = sa.person_id
            WHERE sa.organisation_id = ?
              AND sa.next_due_date < CURDATE()
            ORDER BY sa.next_due_date ASC
        ");
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }

    public static function create($data) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO staff_appraisals (
                person_id, organisation_id, appraisal_date, due_date,
                appraiser_name, appraiser_person_id, appraisal_type,
                outcome, next_due_date, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['person_id'],
            $data['organisation_id'],
            $data['appraisal_date'],
            $data['due_date'] ?? null,
            $data['appraiser_name'] ?? null,
            $data['appraiser_person_id'] ?? null,
            $data['appraisal_type'] ?? 'annual',
            $data['outcome'] ?? 'pending',
            $data['next_due_date'] ?? null,
            $data['notes'] ?? null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete($id, $organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("DELETE FROM staff_appraisals WHERE id = ? AND organisation_id = ?");
        $stmt->execute([$id, $organisationId]);
        return $stmt->rowCount() > 0;
    }
}
