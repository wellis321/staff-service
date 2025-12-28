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
     * Calculate days until expiry
     */
    public static function daysUntilExpiry($expiryDate) {
        if (!$expiryDate) {
            return null;
        }
        
        $expiry = new DateTime($expiryDate);
        $today = new DateTime();
        $diff = $today->diff($expiry);
        
        if ($expiry < $today) {
            return -$diff->days; // Negative for expired
        }
        
        return $diff->days;
    }
}

