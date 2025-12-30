<?php
/**
 * Person Model
 * Handles all person-related database operations
 */

class Person {
    
    /**
     * Find person by ID
     */
    public static function findById($id, $organisationId = null) {
        $db = getDbConnection();
        
            $query = "
            SELECT p.*, sp.job_title, sp.employment_start_date, sp.employment_end_date,
                   sp.line_manager_id, sp.emergency_contact_name, sp.emergency_contact_phone,
                   sp.notes, sp.ni_number, sp.bank_sort_code, sp.bank_account_number, sp.bank_account_name,
                   sp.address_line1, sp.address_line2, sp.address_city, sp.address_county,
                   sp.address_postcode, sp.address_country,
                   sp.contracted_hours, sp.place_of_work, sp.job_post_id,
                   sp.annual_leave_allocation, sp.annual_leave_used, sp.annual_leave_carry_over,
                   sp.time_in_lieu_hours, sp.time_in_lieu_used, sp.lying_time_hours, sp.lying_time_used,
                   sp.leave_year_start_date, sp.leave_year_end_date,
                   sp.signature_path, sp.signature_created_at, sp.signature_method,
                   jp.title as job_post_title, jp.location as job_post_location, jp.hours_per_week as job_post_hours,
                   jp.contract_type as job_post_contract_type, jp.salary_range_min as job_post_salary_min,
                   jp.salary_range_max as job_post_salary_max, jp.salary_currency as job_post_salary_currency,
                   jd.title as job_description_title, jd.description as job_description_text,
                   u.email as user_email
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN job_posts jp ON sp.job_post_id = jp.id
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            WHERE p.id = ?
        ";
        
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND p.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Find person by user ID
     */
    public static function findByUserId($userId, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "
            SELECT p.*, sp.job_title, sp.employment_start_date, sp.employment_end_date,
                   sp.line_manager_id, sp.emergency_contact_name, sp.emergency_contact_phone,
                   sp.notes, sp.ni_number, sp.bank_sort_code, sp.bank_account_number, sp.bank_account_name,
                   sp.address_line1, sp.address_line2, sp.address_city, sp.address_county,
                   sp.address_postcode, sp.address_country,
                   sp.contracted_hours, sp.place_of_work, sp.job_post_id,
                   sp.annual_leave_allocation, sp.annual_leave_used, sp.annual_leave_carry_over,
                   sp.time_in_lieu_hours, sp.time_in_lieu_used, sp.lying_time_hours, sp.lying_time_used,
                   sp.leave_year_start_date, sp.leave_year_end_date,
                   sp.signature_path, sp.signature_created_at, sp.signature_method,
                   jp.title as job_post_title, jp.location as job_post_location, jp.hours_per_week as job_post_hours,
                   jp.contract_type as job_post_contract_type, jp.salary_range_min as job_post_salary_min,
                   jp.salary_range_max as job_post_salary_max, jp.salary_currency as job_post_salary_currency,
                   jd.title as job_description_title, jd.description as job_description_text,
                   u.email as user_email
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            LEFT JOIN job_posts jp ON sp.job_post_id = jp.id
            LEFT JOIN job_descriptions jd ON jp.job_description_id = jd.id
            WHERE p.user_id = ?
        ";
        
        $params = [$userId];
        
        if ($organisationId !== null) {
            $query .= " AND p.organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Find person by employee reference
     */
    public static function findByEmployeeReference($organisationId, $employeeReference) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            SELECT p.*, sp.job_title, sp.employment_start_date, sp.employment_end_date,
                   sp.line_manager_id, sp.emergency_contact_name, sp.emergency_contact_phone,
                   sp.notes
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            WHERE p.organisation_id = ? AND p.employee_reference = ? AND p.person_type = 'staff'
        ");
        $stmt->execute([$organisationId, $employeeReference]);
        return $stmt->fetch();
    }
    
    /**
     * Get all staff for an organisation
     */
    public static function getStaffByOrganisation($organisationId, $activeOnly = true, $limit = null, $offset = 0) {
        $db = getDbConnection();
        
        $query = "
            SELECT p.*, sp.job_title, sp.employment_start_date, sp.employment_end_date,
                   sp.line_manager_id, u.email as user_email
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.organisation_id = ? AND p.person_type = 'staff'
        ";
        
        $params = [$organisationId];
        
        if ($activeOnly) {
            $query .= " AND p.is_active = TRUE";
        }
        
        $query .= " ORDER BY p.last_name, p.first_name";
        
        if ($limit !== null) {
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new person (staff)
     */
    public static function createStaff($data) {
        // #region agent log
        $logPath = ROOT_PATH . '/.cursor/debug.log';
        $logDir = dirname($logPath);
        if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
        $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Person.php:162', 'message' => 'createStaff entry', 'data' => ['dataKeys' => array_keys($data), 'hasOrganisationId' => isset($data['organisation_id']), 'hasUserId' => isset($data['user_id']), 'hasFirstName' => isset($data['first_name']), 'hasLastName' => isset($data['last_name']), 'hasEmail' => isset($data['email']), 'organisationId' => $data['organisation_id'] ?? null, 'userId' => $data['user_id'] ?? null, 'firstName' => $data['first_name'] ?? null, 'lastName' => $data['last_name'] ?? null, 'email' => $data['email'] ?? null]];
        @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
        error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
        // #endregion
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            // Check if user_id is provided and already has a profile
            if (!empty($data['user_id'])) {
                // #region agent log
                $logPath = ROOT_PATH . '/.cursor/debug.log';
                $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Person.php:170', 'message' => 'Checking for existing profile by user_id', 'data' => ['userId' => $data['user_id'], 'organisationId' => $data['organisation_id']]];
                @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
                // #endregion
                $checkStmt = $db->prepare("
                    SELECT id FROM people 
                    WHERE user_id = ? AND organisation_id = ?
                ");
                $checkStmt->execute([$data['user_id'], $data['organisation_id']]);
                $existingProfile = $checkStmt->fetch();
                
                // #region agent log
                $logPath = ROOT_PATH . '/.cursor/debug.log';
                $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Person.php:175', 'message' => 'Existing profile check result', 'data' => ['existingProfileFound' => !empty($existingProfile), 'existingPersonId' => $existingProfile['id'] ?? null]];
                @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
                // #endregion
                
                if ($existingProfile) {
                    // User already has a profile - update it instead of creating a new one
                    $personId = $existingProfile['id'];
                    
                    // Update the existing profile
                    $result = self::update($personId, $data, $data['organisation_id']);
                    
                    if ($result) {
                        $db->commit();
                        return $result;
                    } else {
                        $db->rollBack();
                        return null;
                    }
                }
            }
            
            // Check if email is provided and matches an existing user's profile
            if (!empty($data['email'])) {
                // #region agent log
                $logPath = ROOT_PATH . '/.cursor/debug.log';
                $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Person.php:195', 'message' => 'Checking for existing profile by email', 'data' => ['email' => $data['email'], 'organisationId' => $data['organisation_id']]];
                @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
                // #endregion
                $checkStmt = $db->prepare("
                    SELECT p.id, p.user_id 
                    FROM people p
                    LEFT JOIN users u ON p.user_id = u.id
                    WHERE (p.email = ? OR u.email = ?) 
                    AND p.organisation_id = ? 
                    AND p.person_type = 'staff'
                ");
                $checkStmt->execute([$data['email'], $data['email'], $data['organisation_id']]);
                $existingProfile = $checkStmt->fetch();
                
                // #region agent log
                $logPath = ROOT_PATH . '/.cursor/debug.log';
                $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'A', 'location' => 'Person.php:205', 'message' => 'Email check result', 'data' => ['existingProfileFound' => !empty($existingProfile), 'existingPersonId' => $existingProfile['id'] ?? null]];
                @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
                error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
                // #endregion
                
                if ($existingProfile) {
                    // Found existing profile by email - update it and link user if provided
                    $personId = $existingProfile['id'];
                    
                    // If user_id was provided but profile doesn't have it, add it
                    if (!empty($data['user_id']) && empty($existingProfile['user_id'])) {
                        $data['user_id'] = $data['user_id'];
                    } elseif (empty($data['user_id']) && !empty($existingProfile['user_id'])) {
                        // Keep existing user_id if new data doesn't have one
                        unset($data['user_id']);
                    }
                    
                    // Update the existing profile
                    $result = self::update($personId, $data, $data['organisation_id']);
                    
                    if ($result) {
                        $db->commit();
                        return $result;
                    } else {
                        $db->rollBack();
                        return null;
                    }
                }
            }
            
            // Insert into people table
            // #region agent log
            $logPath = ROOT_PATH . '/.cursor/debug.log';
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'Person.php:233', 'message' => 'Before INSERT into people table', 'data' => ['organisationId' => $data['organisation_id'], 'userId' => $data['user_id'] ?? null, 'firstName' => $data['first_name'] ?? null, 'lastName' => $data['last_name'] ?? null, 'email' => $data['email'] ?? null, 'hasFirstName' => !empty($data['first_name']), 'hasLastName' => !empty($data['last_name'])]];
            @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
            // #endregion
            $stmt = $db->prepare("
                INSERT INTO people (
                    organisation_id, person_type, user_id, first_name, last_name,
                    email, phone, date_of_birth, employee_reference, is_active, photo_path
                ) VALUES (?, 'staff', ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Ensure is_active is always an integer (0 or 1) for MySQL
            $isActive = isset($data['is_active']) ? ($data['is_active'] === true || $data['is_active'] === 1 || $data['is_active'] === '1') ? 1 : 0 : 1;
            
            $stmt->execute([
                $data['organisation_id'],
                $data['user_id'] ?? null,
                $data['first_name'],
                $data['last_name'],
                $data['email'] ?? null,
                $data['phone'] ?? null,
                $data['date_of_birth'] ?? null,
                $data['employee_reference'] ?? null,
                $isActive,
                $data['photo_path'] ?? null
            ]);
            
            $personId = $db->lastInsertId();
            
            // #region agent log
            $logPath = ROOT_PATH . '/.cursor/debug.log';
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'Person.php:256', 'message' => 'After INSERT into people table', 'data' => ['personId' => $personId, 'lastInsertId' => $personId]];
            @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
            // #endregion
            
            // Insert staff profile if staff-specific data provided
            $hasStaffData = isset($data['job_title']) || isset($data['employment_start_date']) || 
                isset($data['line_manager_id']) || isset($data['emergency_contact_name']) ||
                isset($data['ni_number']) || isset($data['bank_sort_code']) || isset($data['bank_account_number']) ||
                isset($data['address_line1']) || isset($data['contracted_hours']) || isset($data['place_of_work']);
            
            if ($hasStaffData) {
                $profileStmt = $db->prepare("
                    INSERT INTO staff_profiles (
                        person_id, job_title, employment_start_date, employment_end_date,
                        line_manager_id, emergency_contact_name, emergency_contact_phone, notes,
                        ni_number, bank_sort_code, bank_account_number, bank_account_name,
                        address_line1, address_line2, address_city, address_county,
                        address_postcode, address_country,
                        contracted_hours, place_of_work, job_description_id,
                        external_job_description_url, external_job_description_ref
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $profileStmt->execute([
                    $personId,
                    $data['job_title'] ?? null,
                    $data['employment_start_date'] ?? null,
                    $data['employment_end_date'] ?? null,
                    $data['line_manager_id'] ?? null,
                    $data['emergency_contact_name'] ?? null,
                    $data['emergency_contact_phone'] ?? null,
                    $data['notes'] ?? null,
                    $data['ni_number'] ?? null,
                    $data['bank_sort_code'] ?? null,
                    $data['bank_account_number'] ?? null,
                    $data['bank_account_name'] ?? null,
                    $data['address_line1'] ?? null,
                    $data['address_line2'] ?? null,
                    $data['address_city'] ?? null,
                    $data['address_county'] ?? null,
                    $data['address_postcode'] ?? null,
                    $data['address_country'] ?? null,
                    $data['contracted_hours'] ?? null,
                    $data['place_of_work'] ?? null,
                    $data['job_description_id'] ?? null,
                    $data['external_job_description_url'] ?? null,
                    $data['external_job_description_ref'] ?? null
                ]);
            }
            
            $db->commit();
            
            // #region agent log
            $logPath = ROOT_PATH . '/.cursor/debug.log';
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'Person.php:304', 'message' => 'Transaction committed, calling findById', 'data' => ['personId' => $personId]];
            @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
            // #endregion
            
            $result = self::findById($personId);
            
            // #region agent log
            $logPath = ROOT_PATH . '/.cursor/debug.log';
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'B', 'location' => 'Person.php:305', 'message' => 'findById result', 'data' => ['resultFound' => !empty($result), 'resultId' => $result['id'] ?? null]];
            @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
            // #endregion
            
            return $result;
            
        } catch (Exception $e) {
            $db->rollBack();
            // #region agent log
            $errorInfo = $db->errorInfo();
            $logPath = ROOT_PATH . '/.cursor/debug.log';
            $logData = ['sessionId' => 'debug-session', 'runId' => 'run1', 'hypothesisId' => 'C', 'location' => 'Person.php:307', 'message' => 'Exception in createStaff', 'data' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'dbErrorCode' => $errorInfo[0] ?? null, 'dbErrorMsg' => $errorInfo[2] ?? null, 'trace' => substr($e->getTraceAsString(), 0, 1000)]];
            @file_put_contents($logPath, json_encode($logData, JSON_UNESCAPED_SLASHES) . "\n", FILE_APPEND);
            error_log("DEBUG: " . json_encode($logData, JSON_UNESCAPED_SLASHES));
            // #endregion
            error_log("Error creating staff: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update person
     */
    public static function update($id, $data, $organisationId = null) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            // Build update query for people table
            $peopleFields = [];
            $peopleParams = [];
            
            $allowedPeopleFields = ['first_name', 'last_name', 'email', 'phone', 'date_of_birth', 
                                   'employee_reference', 'is_active', 'photo_path', 'photo_approval_status',
                                   'photo_pending_path', 'user_id'];
            
            foreach ($allowedPeopleFields as $field) {
                if (isset($data[$field])) {
                    $peopleFields[] = "$field = ?";
                    // Handle null values properly - array_key_exists allows null values
                    $value = $data[$field];
                    // For date fields, ensure empty strings become null
                    if (in_array($field, ['date_of_birth']) && ($value === '' || $value === null)) {
                        $peopleParams[] = null;
                    } else {
                        $peopleParams[] = $value;
                    }
                }
            }
            
            if (!empty($peopleFields)) {
                $peopleParams[] = $id;
                if ($organisationId !== null) {
                    $peopleParams[] = $organisationId;
                }
                
                $query = "UPDATE people SET " . implode(', ', $peopleFields) . " WHERE id = ?";
                if ($organisationId !== null) {
                    $query .= " AND organisation_id = ?";
                }
                
                $stmt = $db->prepare($query);
                $stmt->execute($peopleParams);
            }
            
            // Update staff profile if staff-specific data provided
            $allowedStaffFields = ['job_title', 'employment_start_date', 'employment_end_date',
                                  'line_manager_id', 'emergency_contact_name', 'emergency_contact_phone', 'notes',
                                  'ni_number', 'bank_sort_code', 'bank_account_number', 'bank_account_name',
                                  'address_line1', 'address_line2', 'address_city', 'address_county', 
                                  'address_postcode', 'address_country',
                                  'contracted_hours', 'place_of_work', 'job_post_id',
                                  'signature_path', 'signature_created_at', 'signature_method'];
            
            $hasStaffData = false;
            foreach ($allowedStaffFields as $field) {
                if (isset($data[$field])) {
                    $hasStaffData = true;
                    break;
                }
            }
            
            if ($hasStaffData) {
                // Check if staff profile exists
                $checkStmt = $db->prepare("SELECT id FROM staff_profiles WHERE person_id = ?");
                $checkStmt->execute([$id]);
                $profileExists = $checkStmt->fetch();
                
                $staffFields = [];
                $staffParams = [];
                
                foreach ($allowedStaffFields as $field) {
                    if (isset($data[$field])) {
                        $staffFields[] = "$field = ?";
                        $value = $data[$field];
                        // No boolean fields in production schema - all fields are handled the same way
                        $staffParams[] = $value;
                    }
                }
                
                if (!empty($staffFields)) {
                    if ($profileExists) {
                        $staffParams[] = $id;
                        $query = "UPDATE staff_profiles SET " . implode(', ', $staffFields) . " WHERE person_id = ?";
                        $stmt = $db->prepare($query);
                        $stmt->execute($staffParams);
                    } else {
                        // Create staff profile
                        $staffParams = array_merge([$id], $staffParams);
                        $placeholders = implode(', ', array_fill(0, count($staffFields), '?'));
                        $fields = implode(', ', array_map(function($f) {
                            return str_replace(' = ?', '', $f);
                        }, $staffFields));
                        
                        $query = "INSERT INTO staff_profiles (person_id, $fields) VALUES (?, $placeholders)";
                        $stmt = $db->prepare($query);
                        $stmt->execute($staffParams);
                    }
                }
            }
            
            $db->commit();
            return self::findById($id, $organisationId);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error updating person: " . $e->getMessage());
            error_log("Error updating person - Stack trace: " . $e->getTraceAsString());
            // Re-throw exception so calling code can handle it
            throw $e;
        }
    }
    
    /**
     * Delete person (soft delete - set is_active to false)
     */
    public static function deactivate($id, $organisationId = null) {
        $db = getDbConnection();
        
        $query = "UPDATE people SET is_active = FALSE WHERE id = ?";
        $params = [$id];
        
        if ($organisationId !== null) {
            $query .= " AND organisation_id = ?";
            $params[] = $organisationId;
        }
        
        $stmt = $db->prepare($query);
        return $stmt->execute($params);
    }
    
    /**
     * Get organisational units for a person
     */
    public static function getOrganisationalUnits($personId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            SELECT pou.*, ou.name as unit_name, ou.code as unit_code,
                   outy.name as unit_type_name, outy.display_name as unit_type_display
            FROM person_organisational_units pou
            JOIN organisational_units ou ON pou.organisational_unit_id = ou.id
            LEFT JOIN organisational_unit_types outy ON ou.unit_type_id = outy.id
            WHERE pou.person_id = ?
            ORDER BY pou.is_primary DESC, ou.name
        ");
        $stmt->execute([$personId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Assign person to organisational unit
     */
    public static function assignToOrganisationalUnit($personId, $organisationalUnitId, $roleInUnit = 'member', $isPrimary = false) {
        $db = getDbConnection();
        
        try {
            // If this is being set as primary, unset other primaries for this person
            if ($isPrimary) {
                $updateStmt = $db->prepare("
                    UPDATE person_organisational_units 
                    SET is_primary = FALSE 
                    WHERE person_id = ?
                ");
                $updateStmt->execute([$personId]);
            }
            
            // Insert or update assignment
            $stmt = $db->prepare("
                INSERT INTO person_organisational_units (person_id, organisational_unit_id, role_in_unit, is_primary)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE role_in_unit = VALUES(role_in_unit), is_primary = VALUES(is_primary)
            ");
            
            return $stmt->execute([$personId, $organisationalUnitId, $roleInUnit, $isPrimary ? 1 : 0]);
            
        } catch (Exception $e) {
            error_log("Error assigning to organisational unit: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove person from organisational unit
     */
    public static function removeFromOrganisationalUnit($personId, $organisationalUnitId) {
        $db = getDbConnection();
        
        $stmt = $db->prepare("
            DELETE FROM person_organisational_units 
            WHERE person_id = ? AND organisational_unit_id = ?
        ");
        return $stmt->execute([$personId, $organisationalUnitId]);
    }
    
    /**
     * Search staff
     */
    public static function searchStaff($organisationId, $searchTerm, $activeOnly = true) {
        $db = getDbConnection();
        
        $query = "
            SELECT p.*, sp.job_title, u.email as user_email
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.organisation_id = ? AND p.person_type = 'staff'
            AND (
                p.first_name LIKE ? OR
                p.last_name LIKE ? OR
                p.employee_reference LIKE ? OR
                p.email LIKE ? OR
                u.email LIKE ?
            )
        ";
        
        $params = [$organisationId];
        $searchPattern = '%' . $searchTerm . '%';
        $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        
        if ($activeOnly) {
            $query .= " AND p.is_active = TRUE";
        }
        
        $query .= " ORDER BY p.last_name, p.first_name LIMIT 100";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Count staff by organisation
     */
    public static function countStaff($organisationId, $activeOnly = true) {
        $db = getDbConnection();
        
        $query = "SELECT COUNT(*) as count FROM people WHERE organisation_id = ? AND person_type = 'staff'";
        $params = [$organisationId];
        
        if ($activeOnly) {
            $query .= " AND is_active = TRUE";
        }
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    /**
     * Merge two person profiles
     * Merges source profile into target profile, then deletes source
     * 
     * @param int $targetId The profile to keep
     * @param int $sourceId The profile to merge and delete
     * @param int $organisationId Organisation ID for security
     * @return array|null Merged profile or null on failure
     */
    public static function mergeProfiles($targetId, $sourceId, $organisationId) {
        $db = getDbConnection();
        
        try {
            $db->beginTransaction();
            
            // Verify both profiles exist and belong to the organisation
            $target = self::findById($targetId, $organisationId);
            $source = self::findById($sourceId, $organisationId);
            
            if (!$target || !$source) {
                $db->rollBack();
                return null;
            }
            
            // Merge data: prefer non-null values from source if target is null/empty
            $mergedData = [];
            
            // Basic fields - prefer source if target is empty
            $fieldsToMerge = ['email', 'phone', 'date_of_birth', 'employee_reference', 'photo_path', 'user_id'];
            foreach ($fieldsToMerge as $field) {
                if (empty($target[$field]) && !empty($source[$field])) {
                    $mergedData[$field] = $source[$field];
                } elseif (!empty($target[$field]) && empty($source[$field])) {
                    // Keep target value
                } elseif (!empty($target[$field]) && !empty($source[$field])) {
                    // Both have values - prefer target (the one we're keeping)
                    $mergedData[$field] = $target[$field];
                }
            }
            
            // Prefer source user_id if target doesn't have one
            if (empty($target['user_id']) && !empty($source['user_id'])) {
                $mergedData['user_id'] = $source['user_id'];
            }
            
            // Merge staff profile data
            $targetStaff = $db->prepare("SELECT * FROM staff_profiles WHERE person_id = ?");
            $targetStaff->execute([$targetId]);
            $targetStaffData = $targetStaff->fetch();
            
            $sourceStaff = $db->prepare("SELECT * FROM staff_profiles WHERE person_id = ?");
            $sourceStaff->execute([$sourceId]);
            $sourceStaffData = $sourceStaff->fetch();
            
            $staffFieldsToMerge = ['job_title', 'employment_start_date', 'employment_end_date', 
                                  'line_manager_id', 'emergency_contact_name', 'emergency_contact_phone', 'notes'];
            foreach ($staffFieldsToMerge as $field) {
                if (empty($targetStaffData[$field]) && !empty($sourceStaffData[$field])) {
                    $mergedData[$field] = $sourceStaffData[$field];
                }
            }
            
            // Update target with merged data
            if (!empty($mergedData)) {
                self::update($targetId, $mergedData, $organisationId);
            }
            
            // Move organisational unit assignments from source to target (avoid duplicates)
            // First, get all source unit assignments
            $sourceUnits = $db->prepare("
                SELECT organisational_unit_id, role_in_unit, is_primary 
                FROM person_organisational_units 
                WHERE person_id = ?
            ");
            $sourceUnits->execute([$sourceId]);
            $sourceUnitAssignments = $sourceUnits->fetchAll();
            
            // Get existing target unit assignments
            $targetUnits = $db->prepare("
                SELECT organisational_unit_id 
                FROM person_organisational_units 
                WHERE person_id = ?
            ");
            $targetUnits->execute([$targetId]);
            $existingUnitIds = array_column($targetUnits->fetchAll(), 'organisational_unit_id');
            
            // Insert source assignments that don't already exist for target
            foreach ($sourceUnitAssignments as $assignment) {
                if (!in_array($assignment['organisational_unit_id'], $existingUnitIds)) {
                    $insertUnit = $db->prepare("
                        INSERT INTO person_organisational_units 
                        (person_id, organisational_unit_id, role_in_unit, is_primary)
                        VALUES (?, ?, ?, ?)
                    ");
                    $insertUnit->execute([
                        $targetId,
                        $assignment['organisational_unit_id'],
                        $assignment['role_in_unit'],
                        $assignment['is_primary']
                    ]);
                }
            }
            
            // Delete source unit assignments
            $deleteUnits = $db->prepare("DELETE FROM person_organisational_units WHERE person_id = ?");
            $deleteUnits->execute([$sourceId]);
            
            // Delete source profile (cascade will handle staff_profiles and person_organisational_units)
            $deleteStmt = $db->prepare("DELETE FROM people WHERE id = ? AND organisation_id = ?");
            $deleteStmt->execute([$sourceId, $organisationId]);
            
            $db->commit();
            
            // Return merged profile
            return self::findById($targetId, $organisationId);
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Error merging profiles: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find potential duplicate profiles by email or name
     * 
     * @param int $organisationId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return array Array of potential duplicates
     */
    public static function findDuplicates($organisationId, $email = null, $firstName = null, $lastName = null) {
        $db = getDbConnection();
        
        $conditions = ["p.organisation_id = ?", "p.person_type = 'staff'"];
        $params = [$organisationId];
        
        if ($email) {
            $conditions[] = "(p.email = ? OR u.email = ?)";
            $params[] = $email;
            $params[] = $email;
        }
        
        if ($firstName && $lastName) {
            $conditions[] = "p.first_name LIKE ? AND p.last_name LIKE ?";
            $params[] = $firstName;
            $params[] = $lastName;
        }
        
        $query = "
            SELECT p.*, sp.job_title, u.email as user_email
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY p.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Find potential matches for linking person records
     * Enhanced version that uses multiple criteria (email, name, date of birth)
     * 
     * @param int $organisationId
     * @param string|null $email
     * @param string|null $firstName
     * @param string|null $lastName
     * @param string|null $dateOfBirth
     * @return array Array of potential matches with match score
     */
    public static function findPotentialMatches($organisationId, $email = null, $firstName = null, $lastName = null, $dateOfBirth = null) {
        $db = getDbConnection();
        
        $conditions = ["p.organisation_id = ?", "p.person_type = 'staff'"];
        $params = [$organisationId];
        $matchScore = 0;
        
        // Build conditions and calculate match score
        if ($email) {
            $conditions[] = "(p.email = ? OR u.email = ?)";
            $params[] = $email;
            $params[] = $email;
            $matchScore += 10; // Email match is strong
        }
        
        if ($firstName && $lastName) {
            $conditions[] = "(p.first_name LIKE ? AND p.last_name LIKE ?)";
            $params[] = $firstName;
            $params[] = $lastName;
            $matchScore += 5; // Name match is moderate
        }
        
        if ($dateOfBirth) {
            $conditions[] = "p.date_of_birth = ?";
            $params[] = $dateOfBirth;
            $matchScore += 8; // DOB match is strong
        }
        
        $query = "
            SELECT p.*, sp.job_title, sp.employment_start_date, sp.employment_end_date,
                   u.email as user_email,
                   ? as match_score
            FROM people p
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE " . implode(" AND ", $conditions) . "
            ORDER BY p.created_at DESC
        ";
        
        $params[] = $matchScore; // Add match score to params
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Link two person records together
     * 
     * @param int $primaryPersonId The current/active person record
     * @param int $linkedPersonId The old/inactive person record
     * @param int $organisationId Organisation ID for security
     * @param string $relationshipType Type of relationship (previous_employment, merged, linked)
     * @param string|null $notes Optional notes
     * @return bool Success
     */
    public static function linkPersonRecords($primaryPersonId, $linkedPersonId, $organisationId, $relationshipType = 'previous_employment', $notes = null) {
        $db = getDbConnection();
        
        try {
            // Verify both persons exist and belong to organisation
            $primary = self::findById($primaryPersonId, $organisationId);
            $linked = self::findById($linkedPersonId, $organisationId);
            
            if (!$primary || !$linked) {
                return false;
            }
            
            // Prevent self-linking
            if ($primaryPersonId === $linkedPersonId) {
                return false;
            }
            
            // Check if relationship already exists
            $checkStmt = $db->prepare("
                SELECT id FROM person_relationships 
                WHERE (primary_person_id = ? AND linked_person_id = ?)
                   OR (primary_person_id = ? AND linked_person_id = ?)
            ");
            $checkStmt->execute([$primaryPersonId, $linkedPersonId, $linkedPersonId, $primaryPersonId]);
            if ($checkStmt->fetch()) {
                return false; // Relationship already exists
            }
            
            $stmt = $db->prepare("
                INSERT INTO person_relationships 
                (primary_person_id, linked_person_id, organisation_id, relationship_type, linked_by, notes)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $linkedBy = $_SESSION['user_id'] ?? null;
            
            $stmt->execute([
                $primaryPersonId,
                $linkedPersonId,
                $organisationId,
                $relationshipType,
                $linkedBy,
                $notes
            ]);
            
            return true;
            
        } catch (Exception $e) {
            error_log("Error linking person records: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all linked person IDs for a given person (both directions)
     * Returns array of person IDs that are linked to this person
     * 
     * @param int $personId
     * @param int $organisationId
     * @return array Array of linked person IDs
     */
    public static function getLinkedPersonIds($personId, $organisationId) {
        $db = getDbConnection();
        
        $query = "
            SELECT DISTINCT 
                CASE 
                    WHEN primary_person_id = ? THEN linked_person_id
                    WHEN linked_person_id = ? THEN primary_person_id
                END as linked_id
            FROM person_relationships
            WHERE organisation_id = ?
            AND (primary_person_id = ? OR linked_person_id = ?)
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$personId, $personId, $organisationId, $personId, $personId]);
        $results = $stmt->fetchAll();
        
        return array_filter(array_column($results, 'linked_id'));
    }
    
    /**
     * Get all linked person records with details
     * 
     * @param int $personId
     * @param int $organisationId
     * @return array Array of linked person records with relationship details
     */
    public static function getLinkedPersonRecords($personId, $organisationId) {
        $db = getDbConnection();
        
        $query = "
            SELECT 
                pr.*,
                CASE 
                    WHEN pr.primary_person_id = ? THEN pr.linked_person_id
                    ELSE pr.primary_person_id
                END as linked_person_id,
                p.first_name, p.last_name, p.email, p.employee_reference,
                sp.job_title, sp.employment_start_date, sp.employment_end_date,
                u.email as user_email
            FROM person_relationships pr
            JOIN people p ON (
                CASE 
                    WHEN pr.primary_person_id = ? THEN p.id = pr.linked_person_id
                    ELSE p.id = pr.primary_person_id
                END
            )
            LEFT JOIN staff_profiles sp ON p.id = sp.person_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE pr.organisation_id = ?
            AND (pr.primary_person_id = ? OR pr.linked_person_id = ?)
            ORDER BY pr.linked_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$personId, $personId, $organisationId, $personId, $personId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Unlink two person records
     * 
     * @param int $primaryPersonId
     * @param int $linkedPersonId
     * @param int $organisationId
     * @return bool Success
     */
    public static function unlinkPersonRecords($primaryPersonId, $linkedPersonId, $organisationId) {
        $db = getDbConnection();
        
        try {
            $stmt = $db->prepare("
                DELETE FROM person_relationships 
                WHERE organisation_id = ?
                AND (
                    (primary_person_id = ? AND linked_person_id = ?)
                    OR (primary_person_id = ? AND linked_person_id = ?)
                )
            ");
            
            $stmt->execute([
                $organisationId,
                $primaryPersonId,
                $linkedPersonId,
                $linkedPersonId,
                $primaryPersonId
            ]);
            
            return $stmt->rowCount() > 0;
            
        } catch (Exception $e) {
            error_log("Error unlinking person records: " . $e->getMessage());
            return false;
        }
    }
}

