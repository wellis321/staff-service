<?php
/**
 * Job Description Model
 * Handles job description library operations
 * Can work standalone or integrate with external recruitment systems
 */

class JobDescription {
    
    /**
     * Find job description by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT jd.*, u.first_name as created_by_first_name, u.last_name as created_by_last_name
            FROM job_descriptions jd
            LEFT JOIN users u ON jd.created_by = u.id
            WHERE jd.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND jd.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Get all job descriptions for an organisation
     */
    public static function getAllByOrganisation($organisationId, $activeOnly = true) {
        $db = getDbConnection();
        
        $query = "
            SELECT jd.*, 
                   (SELECT COUNT(*) FROM job_posts WHERE job_description_id = jd.id) as post_count,
                   (SELECT COUNT(*) FROM staff_profiles sp JOIN job_posts jp ON sp.job_post_id = jp.id WHERE jp.job_description_id = jd.id) as staff_count
            FROM job_descriptions jd
            WHERE jd.organisation_id = ?
        ";
        
        $params = [$organisationId];
        
        if ($activeOnly) {
            $query .= " AND jd.is_active = TRUE";
        }
        
        $query .= " ORDER BY jd.title ASC";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new job description (generic template)
     */
    public static function create($data) {
        $db = getDbConnection();
        
        try {
            $stmt = $db->prepare("
                INSERT INTO job_descriptions (
                    organisation_id, title, code, description, responsibilities, requirements,
                    external_system, external_id, external_url, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['organisation_id'],
                $data['title'],
                $data['code'] ?? null,
                $data['description'] ?? null,
                $data['responsibilities'] ?? null,
                $data['requirements'] ?? null,
                $data['external_system'] ?? null,
                $data['external_id'] ?? null,
                $data['external_url'] ?? null,
                $data['created_by'] ?? null
            ]);
            
            return self::findById($db->lastInsertId());
            
        } catch (Exception $e) {
            error_log("Error creating job description: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update job description (generic template only)
     */
    public static function update($id, $data, $organisationId = null) {
        $db = getDbConnection();
        
        $allowedFields = ['title', 'code', 'description', 'responsibilities', 'requirements',
                         'external_system', 'external_id', 'external_url', 'is_active'];
        
        $updates = [];
        $params = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updates[] = "$field = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            return null;
        }
        
        $params[] = $id;
        $query = "UPDATE job_descriptions SET " . implode(', ', $updates) . " WHERE id = ?";
        
        if ($organisationId !== null) {
            $query .= " AND organisation_id = ?";
            $params[] = $organisationId;
        }
        
        try {
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            return self::findById($id, $organisationId);
        } catch (Exception $e) {
            error_log("Error updating job description: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get documents for a job description
     */
    public static function getDocuments($jobDescriptionId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            SELECT * FROM job_description_documents
            WHERE job_description_id = ?
            ORDER BY uploaded_at DESC
        ");
        $stmt->execute([$jobDescriptionId]);
        return $stmt->fetchAll();
    }
}

