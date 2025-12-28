<?php
/**
 * Job Post Model
 * Handles specific job positions/posts that are based on job descriptions
 * A job post is a specific instance with location, hours, manager, etc.
 */

class JobPost {
    
    /**
     * Find job post by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT jp.*, 
                   jd.title as job_description_title, jd.description as job_description_text,
                   jd.responsibilities as job_description_responsibilities,
                   jd.requirements as job_description_requirements,
                   u.first_name as created_by_first_name, u.last_name as created_by_last_name,
                   m.first_name as manager_first_name, m.last_name as manager_last_name
            FROM job_posts jp
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            LEFT JOIN users u ON jp.created_by = u.id
            LEFT JOIN users m ON jp.manager_user_id = m.id
            WHERE jp.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND jp.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get all job posts for an organisation
     */
    public static function getAllByOrganisation($organisationId, $activeOnly = true, $openOnly = false) {
        $db = getDbConnection();
        
        $query = "
            SELECT jp.*, 
                   jd.title as job_description_title,
                   (SELECT COUNT(*) FROM staff_profiles WHERE job_post_id = jp.id) as staff_count
            FROM job_posts jp
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            WHERE jp.organisation_id = ?
        ";
        
        $params = [$organisationId];
        
        if ($activeOnly) {
            $query .= " AND jp.is_active = TRUE";
        }
        
        if ($openOnly) {
            $query .= " AND jp.is_open = TRUE";
        }
        
        $query .= " ORDER BY jp.title ASC, jp.created_at DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Get job posts by job description
     */
    public static function getByJobDescription($jobDescriptionId, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT jp.*, 
                   (SELECT COUNT(*) FROM staff_profiles WHERE job_post_id = jp.id) as staff_count
            FROM job_posts jp
            WHERE jp.job_description_id = ?
        ";
        
        $params = [$jobDescriptionId];
        
        if ($organisationId !== null) {
            $query .= " AND jp.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $query .= " ORDER BY jp.is_active DESC, jp.title ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new job post and create initial history record
     */
    public static function create($data) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("
                INSERT INTO job_posts (
                    organisation_id, job_description_id, title, code,
                    location, place_of_work, hours_per_week, contract_type,
                    salary_range_min, salary_range_max, salary_currency,
                    reporting_to, manager_user_id, department,
                    additional_requirements, specific_attributes,
                    external_system, external_id, external_url,
                    is_active, is_open, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['organisation_id'],
                $data['job_description_id'],
                $data['title'] ?? null,
                $data['code'] ?? null,
                $data['location'] ?? null,
                $data['place_of_work'] ?? null,
                $data['hours_per_week'] ?? null,
                $data['contract_type'] ?? null,
                $data['salary_range_min'] ?? null,
                $data['salary_range_max'] ?? null,
                $data['salary_currency'] ?? 'GBP',
                $data['reporting_to'] ?? null,
                $data['manager_user_id'] ?? null,
                $data['department'] ?? null,
                $data['additional_requirements'] ?? null,
                $data['specific_attributes'] ?? null,
                $data['external_system'] ?? null,
                $data['external_id'] ?? null,
                $data['external_url'] ?? null,
                $data['is_active'] ?? true,
                $data['is_open'] ?? true,
                $data['created_by'] ?? null
            ]);
            
            $jobPostId = $db->lastInsertId();
            
            // Create initial history record
            try {
                $historyStmt = $db->prepare("
                    INSERT INTO job_post_history (
                        job_post_id, changed_by, change_type, title, code, location, place_of_work,
                        hours_per_week, contract_type, salary_range_min, salary_range_max, salary_currency,
                        reporting_to, manager_user_id, department, additional_requirements, specific_attributes,
                        is_active, is_open
                    ) VALUES (?, ?, 'create', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $historyStmt->execute([
                    $jobPostId,
                    $data['created_by'] ?? null,
                    $data['title'] ?? null,
                    $data['code'] ?? null,
                    $data['location'] ?? null,
                    $data['place_of_work'] ?? null,
                    $data['hours_per_week'] ?? null,
                    $data['contract_type'] ?? null,
                    $data['salary_range_min'] ?? null,
                    $data['salary_range_max'] ?? null,
                    $data['salary_currency'] ?? 'GBP',
                    $data['reporting_to'] ?? null,
                    $data['manager_user_id'] ?? null,
                    $data['department'] ?? null,
                    $data['additional_requirements'] ?? null,
                    $data['specific_attributes'] ?? null,
                    $data['is_active'] ?? true,
                    $data['is_open'] ?? true
                ]);
            } catch (Exception $e) {
                // History creation failed, but post was created - log and continue
                error_log("Failed to create history record: " . $e->getMessage());
            }
            
            $db->commit();
            return self::findById($jobPostId);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error creating job post: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update job post and track history
     */
    public static function update($id, $data, $organisationId = null, $changedBy = null) {
        $db = getDbConnection();
        
        // Get current values for history
        $current = self::findById($id, $organisationId);
        if (!$current) {
            return null;
        }
        
        $allowedFields = ['title', 'code', 'location', 'place_of_work', 'hours_per_week', 'contract_type',
                         'salary_range_min', 'salary_range_max', 'salary_currency',
                         'reporting_to', 'manager_user_id', 'department',
                         'additional_requirements', 'specific_attributes',
                         'external_system', 'external_id', 'external_url',
                         'is_active', 'is_open', 'job_description_id'];
        
        $updates = [];
        $params = [];
        $changedFields = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $newValue = $data[$field];
                $oldValue = $current[$field] ?? null;
                
                // Track if value actually changed
                if ($newValue != $oldValue) {
                    $changedFields[] = $field;
                }
                
                $updates[] = "$field = ?";
                $params[] = $newValue;
            }
        }
        
        if (empty($updates)) {
            return $current;
        }
        
        $params[] = $id;
        $query = "UPDATE job_posts SET " . implode(', ', $updates) . " WHERE id = ?";
        
        if ($organisationId !== null) {
            $query .= " AND organisation_id = ?";
            $params[] = $organisationId;
        }
        
        try {
            $db->beginTransaction();
            
            // Update the job post
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            // Create history record if fields changed
            if (!empty($changedFields)) {
                $historyData = [
                    'job_post_id' => $id,
                    'changed_by' => $changedBy,
                    'change_type' => 'update',
                    'title' => $data['title'] ?? $current['title'],
                    'code' => $data['code'] ?? $current['code'],
                    'location' => $data['location'] ?? $current['location'],
                    'place_of_work' => $data['place_of_work'] ?? $current['place_of_work'],
                    'hours_per_week' => $data['hours_per_week'] ?? $current['hours_per_week'],
                    'contract_type' => $data['contract_type'] ?? $current['contract_type'],
                    'salary_range_min' => $data['salary_range_min'] ?? $current['salary_range_min'],
                    'salary_range_max' => $data['salary_range_max'] ?? $current['salary_range_max'],
                    'salary_currency' => $data['salary_currency'] ?? $current['salary_currency'],
                    'reporting_to' => $data['reporting_to'] ?? $current['reporting_to'],
                    'manager_user_id' => $data['manager_user_id'] ?? $current['manager_user_id'],
                    'department' => $data['department'] ?? $current['department'],
                    'additional_requirements' => $data['additional_requirements'] ?? $current['additional_requirements'],
                    'specific_attributes' => $data['specific_attributes'] ?? $current['specific_attributes'],
                    'is_active' => $data['is_active'] ?? $current['is_active'],
                    'is_open' => $data['is_open'] ?? $current['is_open'],
                    'changed_fields' => json_encode($changedFields),
                    'change_notes' => $data['change_notes'] ?? null
                ];
                
                $historyStmt = $db->prepare("
                    INSERT INTO job_post_history (
                        job_post_id, changed_by, change_type, title, code, location, place_of_work,
                        hours_per_week, contract_type, salary_range_min, salary_range_max, salary_currency,
                        reporting_to, manager_user_id, department, additional_requirements, specific_attributes,
                        is_active, is_open, changed_fields, change_notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $historyStmt->execute([
                    $historyData['job_post_id'],
                    $historyData['changed_by'],
                    $historyData['change_type'],
                    $historyData['title'],
                    $historyData['code'],
                    $historyData['location'],
                    $historyData['place_of_work'],
                    $historyData['hours_per_week'],
                    $historyData['contract_type'],
                    $historyData['salary_range_min'],
                    $historyData['salary_range_max'],
                    $historyData['salary_currency'],
                    $historyData['reporting_to'],
                    $historyData['manager_user_id'],
                    $historyData['department'],
                    $historyData['additional_requirements'],
                    $historyData['specific_attributes'],
                    $historyData['is_active'],
                    $historyData['is_open'],
                    $historyData['changed_fields'],
                    $historyData['change_notes']
                ]);
            }
            
            $db->commit();
            return self::findById($id, $organisationId);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error updating job post: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get history for a job post
     */
    public static function getHistory($jobPostId, $limit = 50) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            SELECT h.*, u.first_name as changed_by_first_name, u.last_name as changed_by_last_name
            FROM job_post_history h
            LEFT JOIN users u ON h.changed_by = u.id
            WHERE h.job_post_id = ?
            ORDER BY h.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$jobPostId, $limit]);
        return $stmt->fetchAll();
    }
}

