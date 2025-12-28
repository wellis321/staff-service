<?php
/**
 * StaffRoleHistory Model
 * Handles role history and salary tracking for staff members
 */

class StaffRoleHistory {
    
    /**
     * Find role history entry by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT srh.*, 
                   p.first_name, p.last_name, p.employee_reference,
                   jp.title as job_post_title, jp.location as job_post_location,
                   lm.first_name as manager_first_name, lm.last_name as manager_last_name
            FROM staff_role_history srh
            JOIN people p ON srh.person_id = p.id
            LEFT JOIN job_posts jp ON srh.job_post_id = jp.id
            LEFT JOIN people lm ON srh.line_manager_id = lm.id
            WHERE srh.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND srh.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get all role history for a staff member
     */
    public static function getByPersonId($personId, $organisationId = null, $includeEnded = true) {
        $db = getDbConnection();
        
        $query = "
            SELECT srh.*, 
                   jp.title as job_post_title, jp.location as job_post_location,
                   jd.title as job_description_title,
                   lm.first_name as manager_first_name, lm.last_name as manager_last_name
            FROM staff_role_history srh
            LEFT JOIN job_posts jp ON srh.job_post_id = jp.id
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            LEFT JOIN people lm ON srh.line_manager_id = lm.id
            WHERE srh.person_id = ?
        ";
        
        $params = [$personId];
        
        if ($organisationId !== null) {
            $query .= " AND srh.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        if (!$includeEnded) {
            $query .= " AND (srh.end_date IS NULL OR srh.end_date >= CURDATE())";
        }
        
        $query .= " ORDER BY srh.start_date DESC, srh.is_current DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get current role for a staff member
     */
    public static function getCurrentRole($personId, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT srh.*, 
                   jp.title as job_post_title, jp.location as job_post_location,
                   jd.title as job_description_title
            FROM staff_role_history srh
            LEFT JOIN job_posts jp ON srh.job_post_id = jp.id
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            WHERE srh.person_id = ?
            AND srh.is_current = TRUE
            AND (srh.end_date IS NULL OR srh.end_date >= CURDATE())
        ";
        
        $params = [$personId];
        
        if ($organisationId !== null) {
            $query .= " AND srh.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $query .= " ORDER BY srh.start_date DESC LIMIT 1";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Create a new role history entry
     */
    public static function create($data) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            // If this is marked as current, unset all other current roles for this person
            if (!empty($data['is_current']) && $data['is_current']) {
                $updateStmt = $db->prepare("
                    UPDATE staff_role_history 
                    SET is_current = FALSE 
                    WHERE person_id = ? AND organisation_id = ?
                ");
                $updateStmt->execute([$data['person_id'], $data['organisation_id']]);
            }
            
            $stmt = $db->prepare("
                INSERT INTO staff_role_history (
                    person_id, organisation_id, job_post_id, job_title,
                    start_date, end_date, is_current, salary, salary_currency,
                    hours_per_week, contract_type, line_manager_id, place_of_work,
                    notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['person_id'],
                $data['organisation_id'],
                $data['job_post_id'] ?? null,
                $data['job_title'] ?? null,
                $data['start_date'],
                $data['end_date'] ?? null,
                $data['is_current'] ?? false,
                $data['salary'] ?? null,
                $data['salary_currency'] ?? 'GBP',
                $data['hours_per_week'] ?? null,
                $data['contract_type'] ?? null,
                $data['line_manager_id'] ?? null,
                $data['place_of_work'] ?? null,
                $data['notes'] ?? null,
                $data['created_by'] ?? null
            ]);
            
            $id = $db->lastInsertId();
            $db->commit();
            
            return self::findById($id, $data['organisation_id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error creating role history: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update role history entry
     */
    public static function update($id, $data, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            // If this is being marked as current, unset all other current roles
            if (!empty($data['is_current']) && $data['is_current']) {
                $current = self::findById($id, $organisationId);
                if ($current) {
                    $updateStmt = $db->prepare("
                        UPDATE staff_role_history 
                        SET is_current = FALSE 
                        WHERE person_id = ? AND organisation_id = ? AND id != ?
                    ");
                    $updateStmt->execute([$current['person_id'], $current['organisation_id'], $id]);
                }
            }
            
            $allowedFields = [
                'job_post_id', 'job_title', 'start_date', 'end_date', 'is_current',
                'salary', 'salary_currency', 'hours_per_week', 'contract_type',
                'line_manager_id', 'place_of_work', 'notes'
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
            $query = "UPDATE staff_role_history SET " . implode(', ', $fields) . " WHERE id = ?";
            
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
            error_log("Error updating role history: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete role history entry
     */
    public static function delete($id, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $query = "DELETE FROM staff_role_history WHERE id = ?";
            $params = [$id];
            
            if ($organisationId !== null) {
                $query .= " AND organisation_id = ?";
                $params[] = $organisationId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error deleting role history: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * End a current role (set end date and unset is_current)
     */
    public static function endRole($id, $endDate, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $data = [
                'end_date' => $endDate,
                'is_current' => false
            ];
            
            return self::update($id, $data, $organisationId);
            
        } catch (Exception $e) {
            error_log("Error ending role: " . $e->getMessage());
            return false;
        }
    }
}

