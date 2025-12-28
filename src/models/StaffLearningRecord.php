<?php
/**
 * StaffLearningRecord Model
 * Handles qualifications and learning records for staff members
 */

class StaffLearningRecord {
    
    /**
     * Find learning record by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT slr.*, 
                   p.first_name, p.last_name, p.employee_reference,
                   u.first_name as creator_first_name, u.last_name as creator_last_name
            FROM staff_learning_records slr
            JOIN people p ON slr.person_id = p.id
            LEFT JOIN users u ON slr.created_by = u.id
            WHERE slr.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND slr.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get all learning records for a staff member (including linked records)
     * 
     * @param int $personId
     * @param int|null $organisationId
     * @param array $filters
     * @param bool $includeLinked Whether to include records from linked person records
     * @return array
     */
    public static function getByPersonId($personId, $organisationId = null, $filters = [], $includeLinked = true) {
        // Get all person IDs (including linked ones)
        $personIds = [$personId];
        
        if ($includeLinked && $organisationId !== null) {
            $linkedIds = Person::getLinkedPersonIds($personId, $organisationId);
            $personIds = array_merge($personIds, $linkedIds);
        }
        
        if (empty($personIds)) {
            return [];
        }
        
        $db = getDbConnection();
        
        $placeholders = implode(',', array_fill(0, count($personIds), '?'));
        $query = "
            SELECT slr.*, 
                   p.employee_reference,
                   CASE 
                       WHEN slr.person_id = ? THEN 0
                       ELSE 1
                   END as is_from_linked_record
            FROM staff_learning_records slr
            LEFT JOIN people p ON slr.person_id = p.id
            WHERE slr.person_id IN ($placeholders)
        ";
        
        $params = array_merge([$personId], $personIds);
        
        if ($organisationId !== null) {
            $query .= " AND slr.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        // Apply filters
        if (!empty($filters['record_type'])) {
            $query .= " AND slr.record_type = ?";
            $params[] = $filters['record_type'];
        }
        
        if (!empty($filters['source_system'])) {
            $query .= " AND slr.source_system = ?";
            $params[] = $filters['source_system'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND slr.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['is_mandatory'])) {
            $query .= " AND slr.is_mandatory = ?";
            $params[] = $filters['is_mandatory'] ? 1 : 0;
        }
        
        $query .= " ORDER BY slr.completion_date DESC, slr.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get learning records by employee reference
     * 
     * @param int $organisationId
     * @param string $employeeReference
     * @param array $filters
     * @return array
     */
    public static function getByEmployeeReference($organisationId, $employeeReference, $filters = []) {
        $db = getDbConnection();
        
        $query = "
            SELECT slr.*, 
                   p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_learning_records slr
            JOIN people p ON slr.person_id = p.id
            WHERE slr.organisation_id = ?
            AND p.employee_reference = ?
            AND p.person_type = 'staff'
        ";
        
        $params = [$organisationId, $employeeReference];
        
        // Apply filters
        if (!empty($filters['record_type'])) {
            $query .= " AND slr.record_type = ?";
            $params[] = $filters['record_type'];
        }
        
        if (!empty($filters['source_system'])) {
            $query .= " AND slr.source_system = ?";
            $params[] = $filters['source_system'];
        }
        
        if (!empty($filters['status'])) {
            $query .= " AND slr.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['is_mandatory'])) {
            $query .= " AND slr.is_mandatory = ?";
            $params[] = $filters['is_mandatory'] ? 1 : 0;
        }
        
        $query .= " ORDER BY slr.completion_date DESC, slr.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get learning records for a person including linked records (explicit method)
     * This is an alias for getByPersonId with includeLinked=true
     * 
     * @param int $personId
     * @param int|null $organisationId
     * @param array $filters
     * @return array
     */
    public static function getByPersonIdWithLinked($personId, $organisationId = null, $filters = []) {
        return self::getByPersonId($personId, $organisationId, $filters, true);
    }
    
    /**
     * Get expiring certifications (within specified days)
     */
    public static function getExpiringSoon($organisationId, $days = 90) {
        $db = getDbConnection();
        
        $query = "
            SELECT slr.*, p.first_name, p.last_name, p.employee_reference, p.email
            FROM staff_learning_records slr
            JOIN people p ON slr.person_id = p.id
            WHERE slr.organisation_id = ?
            AND slr.expiry_date IS NOT NULL
            AND slr.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND slr.status = 'completed'
            ORDER BY slr.expiry_date ASC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$organisationId, $days]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get mandatory training that's missing or expired
     */
    public static function getMissingMandatoryTraining($personId, $organisationId) {
        $db = getDbConnection();
        
        $query = "
            SELECT slr.*
            FROM staff_learning_records slr
            WHERE slr.person_id = ?
            AND slr.organisation_id = ?
            AND slr.is_mandatory = TRUE
            AND (
                slr.status != 'completed' 
                OR (slr.expiry_date IS NOT NULL AND slr.expiry_date < CURDATE())
            )
            ORDER BY slr.is_required_for_role DESC, slr.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$personId, $organisationId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new learning record
     */
    public static function create($data) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO staff_learning_records (
                    person_id, organisation_id, record_type, title, description,
                    provider, qualification_level, subject_area, completion_date,
                    expiry_date, grade, credits, certificate_number, certificate_path,
                    external_url, source_system, external_id, is_mandatory,
                    is_required_for_role, status, notes, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['person_id'],
                $data['organisation_id'],
                $data['record_type'],
                $data['title'],
                $data['description'] ?? null,
                $data['provider'] ?? null,
                $data['qualification_level'] ?? null,
                $data['subject_area'] ?? null,
                $data['completion_date'] ?? null,
                $data['expiry_date'] ?? null,
                $data['grade'] ?? null,
                $data['credits'] ?? null,
                $data['certificate_number'] ?? null,
                $data['certificate_path'] ?? null,
                $data['external_url'] ?? null,
                $data['source_system'] ?? 'manual',
                $data['external_id'] ?? null,
                $data['is_mandatory'] ?? false,
                $data['is_required_for_role'] ?? false,
                $data['status'] ?? 'completed',
                $data['notes'] ?? null,
                $data['created_by'] ?? null
            ]);
            
            $id = $db->lastInsertId();
            $db->commit();
            
            return self::findById($id, $data['organisation_id']);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error creating learning record: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update learning record
     */
    public static function update($id, $data, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $allowedFields = [
                'record_type', 'title', 'description', 'provider', 'qualification_level',
                'subject_area', 'completion_date', 'expiry_date', 'grade', 'credits',
                'certificate_number', 'certificate_path', 'external_url', 'source_system',
                'external_id', 'is_mandatory', 'is_required_for_role', 'status', 'notes'
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
            $query = "UPDATE staff_learning_records SET " . implode(', ', $fields) . " WHERE id = ?";
            
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
            error_log("Error updating learning record: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Delete learning record
     */
    public static function delete($id, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $query = "DELETE FROM staff_learning_records WHERE id = ?";
            $params = [$id];
            
            if ($organisationId !== null) {
                $query .= " AND organisation_id = ?";
                $params[] = $organisationId;
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error deleting learning record: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sync from external system (LMS, recruitment, HR)
     */
    public static function syncFromExternal($personId, $organisationId, $sourceSystem, $externalRecords) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $synced = 0;
            $updated = 0;
            
            foreach ($externalRecords as $record) {
                // Check if record already exists
                $checkStmt = $db->prepare("
                    SELECT id FROM staff_learning_records 
                    WHERE person_id = ? AND organisation_id = ? 
                    AND source_system = ? AND external_id = ?
                ");
                $checkStmt->execute([
                    $personId, 
                    $organisationId, 
                    $sourceSystem, 
                    $record['external_id']
                ]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    // Update existing record
                    $updateData = [
                        'title' => $record['title'],
                        'completion_date' => $record['completion_date'] ?? null,
                        'expiry_date' => $record['expiry_date'] ?? null,
                        'status' => $record['status'] ?? 'completed',
                        'last_synced_at' => date('Y-m-d H:i:s')
                    ];
                    
                    if (isset($record['description'])) $updateData['description'] = $record['description'];
                    if (isset($record['provider'])) $updateData['provider'] = $record['provider'];
                    if (isset($record['grade'])) $updateData['grade'] = $record['grade'];
                    if (isset($record['external_url'])) $updateData['external_url'] = $record['external_url'];
                    
                    self::update($existing['id'], $updateData, $organisationId);
                    $updated++;
                } else {
                    // Create new record
                    $createData = [
                        'person_id' => $personId,
                        'organisation_id' => $organisationId,
                        'record_type' => $record['record_type'] ?? 'course',
                        'title' => $record['title'],
                        'description' => $record['description'] ?? null,
                        'provider' => $record['provider'] ?? null,
                        'completion_date' => $record['completion_date'] ?? null,
                        'expiry_date' => $record['expiry_date'] ?? null,
                        'grade' => $record['grade'] ?? null,
                        'external_url' => $record['external_url'] ?? null,
                        'source_system' => $sourceSystem,
                        'external_id' => $record['external_id'],
                        'status' => $record['status'] ?? 'completed'
                    ];
                    
                    self::create($createData);
                    $synced++;
                }
            }
            
            $db->commit();
            
            return [
                'synced' => $synced,
                'updated' => $updated,
                'total' => count($externalRecords)
            ];
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error syncing learning records: " . $e->getMessage());
            return null;
        }
    }
}

