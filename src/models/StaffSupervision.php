<?php
/**
 * StaffSupervision Model
 * Manages supervision records for staff members
 */

class StaffSupervision {

    public static function getByPersonId($personId, $organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ss.*,
                   CONCAT(sv.first_name, ' ', sv.last_name) AS supervisor_full_name
            FROM staff_supervisions ss
            LEFT JOIN people sv ON sv.id = ss.supervisor_person_id
            WHERE ss.person_id = ? AND ss.organisation_id = ?
            ORDER BY ss.supervision_date DESC
        ");
        $stmt->execute([$personId, $organisationId]);
        return $stmt->fetchAll();
    }

    public static function getDueWithin($organisationId, $days = 30) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ss.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_supervisions ss
            JOIN people p ON p.id = ss.person_id
            WHERE ss.organisation_id = ?
              AND ss.next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            ORDER BY ss.next_due_date ASC
        ");
        $stmt->execute([$organisationId, $days]);
        return $stmt->fetchAll();
    }

    public static function getOverdue($organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT ss.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_supervisions ss
            JOIN people p ON p.id = ss.person_id
            WHERE ss.organisation_id = ?
              AND ss.next_due_date < CURDATE()
            ORDER BY ss.next_due_date ASC
        ");
        $stmt->execute([$organisationId]);
        return $stmt->fetchAll();
    }

    public static function create($data) {
        $db = getDbConnection();
        $stmt = $db->prepare("
            INSERT INTO staff_supervisions (
                person_id, organisation_id, supervision_date, due_date,
                supervisor_name, supervisor_person_id, supervision_type,
                duration_minutes, outcome, next_due_date, notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['person_id'],
            $data['organisation_id'],
            $data['supervision_date'],
            $data['due_date'] ?? null,
            $data['supervisor_name'] ?? null,
            $data['supervisor_person_id'] ?? null,
            $data['supervision_type'] ?? 'individual',
            !empty($data['duration_minutes']) ? (int)$data['duration_minutes'] : null,
            $data['outcome'] ?? null,
            $data['next_due_date'] ?? null,
            $data['notes'] ?? null,
        ]);
        return $stmt->rowCount() > 0;
    }

    public static function delete($id, $organisationId) {
        $db = getDbConnection();
        $stmt = $db->prepare("DELETE FROM staff_supervisions WHERE id = ? AND organisation_id = ?");
        $stmt->execute([$id, $organisationId]);
        return $stmt->rowCount() > 0;
    }
}
