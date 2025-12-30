<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display, but log
ini_set('log_errors', 1);

// #region agent log
$logFile = dirname(__DIR__, 2) . '/.cursor/debug.log';
$logEntry = function($location, $message, $data = [], $hypothesisId = 'A') use ($logFile) {
    try {
        $entry = json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => $hypothesisId,
            'location' => $location,
            'message' => $message,
            'data' => $data,
            'timestamp' => time() * 1000
        ]) . "\n";
        @file_put_contents($logFile, $entry, FILE_APPEND);
    } catch (Exception $e) {
        // Silently fail logging
    }
};
// #endregion

try {
    require_once dirname(__DIR__, 2) . '/config/config.php';
    // #region agent log
    $logEntry('edit.php:28', 'Config file loaded successfully');
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:28', 'Config file load failed', ['error' => $e->getMessage()], 'A');
    // #endregion
    error_log("Fatal error loading config: " . $e->getMessage());
    http_response_code(500);
    die("Configuration error. Please contact the administrator.");
}

// #region agent log
$logEntry('edit.php:3', 'Config loaded, starting authentication');
// #endregion

try {
    Auth::requireLogin();
    // #region agent log
    $logEntry('edit.php:6', 'Auth::requireLogin() passed');
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:6', 'Auth::requireLogin() failed', ['error' => $e->getMessage()], 'A');
    // #endregion
    throw $e;
}

try {
    RBAC::requireAdmin();
    // #region agent log
    $logEntry('edit.php:9', 'RBAC::requireAdmin() passed');
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:9', 'RBAC::requireAdmin() failed', ['error' => $e->getMessage()], 'B');
    // #endregion
    throw $e;
}

$organisationId = Auth::getOrganisationId();
// #region agent log
$logEntry('edit.php:12', 'Got organisation ID', ['organisationId' => $organisationId]);
// #endregion

$error = '';
$success = '';

// Get person ID
$personId = $_GET['id'] ?? null;
if (!$personId) {
    header('Location: ' . url('staff/index.php?error=invalid_id'));
    exit;
}

// #region agent log
$logEntry('edit.php:22', 'Got person ID', ['personId' => $personId]);
// #endregion

// Get person
try {
    $person = Person::findById($personId, $organisationId);
    // #region agent log
    $logEntry('edit.php:26', 'Person::findById() completed', ['found' => !empty($person)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:26', 'Person::findById() failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()], 'C');
    // #endregion
    throw $e;
}

if (!$person || $person['organisation_id'] != $organisationId) {
    header('Location: ' . url('staff/index.php?error=not_found'));
    exit;
}

// Get all users for linking (including those who already have profiles)
try {
    $db = getDbConnection();
    // #region agent log
    $logEntry('edit.php:40', 'Database connection obtained');
    // #endregion
    
    $stmt = $db->prepare("
        SELECT u.id, u.first_name, u.last_name, u.email,
               CASE WHEN p.id IS NOT NULL AND p.id != ? THEN 1 ELSE 0 END as has_other_profile
        FROM users u
        LEFT JOIN people p ON p.user_id = u.id AND p.organisation_id = ? AND p.id != ?
        WHERE u.organisation_id = ? AND u.is_active = TRUE
        ORDER BY u.last_name, u.first_name
    ");
    $stmt->execute([$personId, $organisationId, $personId, $organisationId]);
    $users = $stmt->fetchAll();
    // #region agent log
    $logEntry('edit.php:50', 'Users query completed', ['count' => count($users)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:40', 'Users query failed', ['error' => $e->getMessage()], 'D');
    // #endregion
    throw $e;
}

// Get all staff for line manager selection
try {
    $staffForManager = Person::getStaffByOrganisation($organisationId, true);
    // #region agent log
    $logEntry('edit.php:53', 'Person::getStaffByOrganisation() completed', ['count' => count($staffForManager)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:53', 'Person::getStaffByOrganisation() failed', ['error' => $e->getMessage()], 'E');
    // #endregion
    throw $e;
}

$staffForManager = array_filter($staffForManager, function($s) use ($personId) {
    return $s['id'] != $personId; // Exclude self
});

// Get organisational units
try {
    $organisationalUnits = Person::getOrganisationalUnits($personId);
    // #region agent log
    $logEntry('edit.php:66', 'Person::getOrganisationalUnits() completed', ['count' => count($organisationalUnits)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:66', 'Person::getOrganisationalUnits() failed', ['error' => $e->getMessage()], 'F');
    // #endregion
    throw $e;
}

try {
    // Check if OrganisationalUnits class exists and has the method
    if (class_exists('OrganisationalUnits') && method_exists('OrganisationalUnits', 'getAllByOrganisation')) {
        try {
            $allUnits = OrganisationalUnits::getAllByOrganisation($organisationId);
            // #region agent log
            $logEntry('edit.php:75', 'OrganisationalUnits::getAllByOrganisation() completed', ['count' => is_array($allUnits) ? count($allUnits) : 'not_array']);
            // #endregion
        } catch (Exception $e) {
            // Method exists but failed (likely missing table) - use fallback query
            // #region agent log
            $logEntry('edit.php:75', 'OrganisationalUnits::getAllByOrganisation() failed, using fallback', ['error' => $e->getMessage()], 'G');
            // #endregion
            $db = getDbConnection();
            $stmt = $db->prepare("
                SELECT id, name, code 
                FROM organisational_units 
                WHERE organisation_id = ? 
                ORDER BY name
            ");
            $stmt->execute([$organisationId]);
            $allUnits = $stmt->fetchAll();
            // #region agent log
            $logEntry('edit.php:89', 'Fallback query completed', ['count' => count($allUnits)]);
            // #endregion
            error_log("OrganisationalUnits::getAllByOrganisation() failed, using fallback: " . $e->getMessage());
        }
    } else {
        // Fallback: query organisational_units directly
        // #region agent log
        $logEntry('edit.php:75', 'OrganisationalUnits class/method not found, using fallback query', [], 'G');
        // #endregion
        $db = getDbConnection();
        $stmt = $db->prepare("
            SELECT id, name, code 
            FROM organisational_units 
            WHERE organisation_id = ? 
            ORDER BY name
        ");
        $stmt->execute([$organisationId]);
        $allUnits = $stmt->fetchAll();
        // #region agent log
        $logEntry('edit.php:89', 'Fallback query completed', ['count' => count($allUnits)]);
        // #endregion
    }
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:75', 'Unexpected error getting organisational units', ['error' => $e->getMessage()], 'G');
    // #endregion
    // Use empty array as last resort fallback
    $allUnits = [];
    error_log("Error getting organisational units: " . $e->getMessage());
}

// Get active job descriptions for selection
try {
    $jobDescriptions = JobDescription::getAllByOrganisation($organisationId, true);
    // #region agent log
    $logEntry('edit.php:87', 'JobDescription::getAllByOrganisation() completed', ['count' => count($jobDescriptions)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:87', 'JobDescription::getAllByOrganisation() failed', ['error' => $e->getMessage()], 'H');
    // #endregion
    throw $e;
}

// Get active job posts for selection
try {
    $jobPosts = JobPost::getAllByOrganisation($organisationId, true);
    // #region agent log
    $logEntry('edit.php:97', 'JobPost::getAllByOrganisation() completed', ['count' => count($jobPosts)]);
    // #endregion
} catch (Exception $e) {
    // #region agent log
    $logEntry('edit.php:97', 'JobPost::getAllByOrganisation() failed', ['error' => $e->getMessage()], 'I');
    // #endregion
    throw $e;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? 'update';
        
        if ($action === 'update') {
            $data = [
                'first_name' => trim($_POST['first_name'] ?? ''),
                'last_name' => trim($_POST['last_name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'employee_reference' => trim($_POST['employee_reference'] ?? ''),
                'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                'is_active' => isset($_POST['is_active']) && $_POST['is_active'] === '1',
                'user_id' => !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null,
            ];
            
            // Staff-specific fields
            if ($person['person_type'] === 'staff') {
                $data['job_title'] = trim($_POST['job_title'] ?? '');
                $data['job_post_id'] = !empty($_POST['job_post_id']) ? (int)$_POST['job_post_id'] : null;
                $data['employment_start_date'] = !empty($_POST['employment_start_date']) ? $_POST['employment_start_date'] : null;
                $data['employment_end_date'] = !empty($_POST['employment_end_date']) ? $_POST['employment_end_date'] : null;
                $data['line_manager_id'] = !empty($_POST['line_manager_id']) ? (int)$_POST['line_manager_id'] : null;
                
                // TUPE fields
                $data['is_tupe'] = isset($_POST['is_tupe']) && $_POST['is_tupe'] === '1' ? 1 : 0;
                $data['tupe_transfer_date'] = !empty($_POST['tupe_transfer_date']) ? $_POST['tupe_transfer_date'] : null;
                $data['tupe_previous_organisation'] = trim($_POST['tupe_previous_organisation'] ?? '');
                $data['tupe_previous_employer_ref'] = trim($_POST['tupe_previous_employer_ref'] ?? '');
                $data['tupe_contract_type'] = trim($_POST['tupe_contract_type'] ?? '');
                $data['tupe_hours_per_week'] = !empty($_POST['tupe_hours_per_week']) ? (float)$_POST['tupe_hours_per_week'] : null;
                $data['tupe_salary'] = !empty($_POST['tupe_salary']) ? (float)$_POST['tupe_salary'] : null;
                $data['tupe_salary_currency'] = trim($_POST['tupe_salary_currency'] ?? 'GBP');
                $data['tupe_notes'] = trim($_POST['tupe_notes'] ?? '');
                $data['emergency_contact_name'] = trim($_POST['emergency_contact_name'] ?? '');
                $data['emergency_contact_phone'] = trim($_POST['emergency_contact_phone'] ?? '');
                $data['notes'] = trim($_POST['notes'] ?? '');
                
                // WTD fields
                $data['wtd_agreed'] = isset($_POST['wtd_agreed']) && $_POST['wtd_agreed'] === '1' ? 1 : 0;
                $data['wtd_agreement_date'] = !empty($_POST['wtd_agreement_date']) ? $_POST['wtd_agreement_date'] : null;
                $data['wtd_agreement_version'] = trim($_POST['wtd_agreement_version'] ?? '');
                $data['wtd_opt_out'] = isset($_POST['wtd_opt_out']) && $_POST['wtd_opt_out'] === '1' ? 1 : 0;
                $data['wtd_opt_out_date'] = !empty($_POST['wtd_opt_out_date']) ? $_POST['wtd_opt_out_date'] : null;
                $data['wtd_opt_out_expiry_date'] = !empty($_POST['wtd_opt_out_expiry_date']) ? $_POST['wtd_opt_out_expiry_date'] : null;
                $data['wtd_notes'] = trim($_POST['wtd_notes'] ?? '');
                
                // Leave management fields
                $data['annual_leave_allocation'] = !empty($_POST['annual_leave_allocation']) ? (float)$_POST['annual_leave_allocation'] : null;
                $data['annual_leave_used'] = !empty($_POST['annual_leave_used']) ? (float)$_POST['annual_leave_used'] : 0;
                $data['annual_leave_carry_over'] = !empty($_POST['annual_leave_carry_over']) ? (float)$_POST['annual_leave_carry_over'] : 0;
                $data['time_in_lieu_hours'] = !empty($_POST['time_in_lieu_hours']) ? (float)$_POST['time_in_lieu_hours'] : 0;
                $data['time_in_lieu_used'] = !empty($_POST['time_in_lieu_used']) ? (float)$_POST['time_in_lieu_used'] : 0;
                $data['lying_time_hours'] = !empty($_POST['lying_time_hours']) ? (float)$_POST['lying_time_hours'] : 0;
                $data['lying_time_used'] = !empty($_POST['lying_time_used']) ? (float)$_POST['lying_time_used'] : 0;
                $data['leave_year_start_date'] = !empty($_POST['leave_year_start_date']) ? $_POST['leave_year_start_date'] : null;
                $data['leave_year_end_date'] = !empty($_POST['leave_year_end_date']) ? $_POST['leave_year_end_date'] : null;
                
                // Contract type fields
                $data['contract_type'] = trim($_POST['contract_type'] ?? '');
                $data['is_bank_staff'] = isset($_POST['is_bank_staff']) && $_POST['is_bank_staff'] === '1' ? 1 : 0;
                $data['is_apprentice'] = isset($_POST['is_apprentice']) && $_POST['is_apprentice'] === '1' ? 1 : 0;
                $data['has_visa'] = isset($_POST['has_visa']) && $_POST['has_visa'] === '1' ? 1 : 0;
                $data['visa_type'] = trim($_POST['visa_type'] ?? '');
                $data['visa_number'] = trim($_POST['visa_number'] ?? '');
                $data['visa_issue_date'] = !empty($_POST['visa_issue_date']) ? $_POST['visa_issue_date'] : null;
                $data['visa_expiry_date'] = !empty($_POST['visa_expiry_date']) ? $_POST['visa_expiry_date'] : null;
                $data['visa_sponsor'] = trim($_POST['visa_sponsor'] ?? '');
                $data['apprenticeship_start_date'] = !empty($_POST['apprenticeship_start_date']) ? $_POST['apprenticeship_start_date'] : null;
                $data['apprenticeship_end_date'] = !empty($_POST['apprenticeship_end_date']) ? $_POST['apprenticeship_end_date'] : null;
                $data['apprenticeship_level'] = trim($_POST['apprenticeship_level'] ?? '');
                $data['apprenticeship_provider'] = trim($_POST['apprenticeship_provider'] ?? '');
            }
            
            // Remove empty strings but keep nulls for optional fields
            // Also ensure boolean fields are always set (not missing)
            foreach ($data as $key => $value) {
                if ($value === '' && in_array($key, ['email', 'phone', 'employee_reference', 'date_of_birth', 'job_title', 'employment_start_date', 'employment_end_date', 'line_manager_id', 'emergency_contact_name', 'emergency_contact_phone', 'notes'])) {
                    $data[$key] = null;
                }
            }
            
            // Ensure all boolean fields are explicitly set to 0 or 1 (not missing)
            $booleanFields = ['is_tupe', 'is_bank_staff', 'is_apprentice', 'has_visa', 'wtd_agreed', 'wtd_opt_out'];
            foreach ($booleanFields as $field) {
                if (!isset($data[$field])) {
                    $data[$field] = 0; // Default to 0 if not set
                } elseif ($data[$field] === '' || $data[$field] === false) {
                    $data[$field] = 0;
                } elseif ($data[$field] === true || $data[$field] === '1' || $data[$field] === 1) {
                    $data[$field] = 1;
                } else {
                    $data[$field] = 0;
                }
            }
            
            try {
                $result = Person::update($personId, $data, $organisationId);
                if ($result) {
                    $success = 'Staff member updated successfully.';
                    $person = Person::findById($personId, $organisationId);
                    $organisationalUnits = Person::getOrganisationalUnits($personId);
                } else {
                    $error = 'Failed to update staff member.';
                }
            } catch (Exception $e) {
                $error = 'Failed to update staff member: ' . htmlspecialchars($e->getMessage());
                error_log("Update error: " . $e->getMessage());
            }
        } elseif ($action === 'add_registration') {
            // Add new registration
            $regData = [
                'person_id' => $personId,
                'organisation_id' => $organisationId,
                'registration_type' => trim($_POST['registration_type'] ?? ''),
                'registration_number' => trim($_POST['registration_number'] ?? ''),
                'registration_body' => trim($_POST['registration_body'] ?? ''),
                'expiry_date' => $_POST['expiry_date'] ?? null,
                'is_required_for_role' => isset($_POST['is_required_for_role']) && $_POST['is_required_for_role'] === '1',
            ];
            
            $regResult = StaffRegistration::create($regData);
            if ($regResult) {
                $success = 'Registration added successfully.';
            } else {
                $error = 'Failed to add registration.';
            }
        } elseif ($action === 'delete_registration') {
            // Delete registration
            $regId = (int)($_POST['registration_id'] ?? 0);
            if ($regId > 0) {
                $deleted = StaffRegistration::delete($regId, $organisationId);
                if ($deleted) {
                    $success = 'Registration deleted successfully.';
                } else {
                    $error = 'Failed to delete registration.';
                }
            }
        } elseif ($action === 'add_role') {
            // Add new role history entry
            $roleData = [
                'person_id' => $personId,
                'organisation_id' => $organisationId,
                'job_post_id' => !empty($_POST['job_post_id']) ? (int)$_POST['job_post_id'] : null,
                'job_title' => trim($_POST['job_title'] ?? ''),
                'start_date' => $_POST['start_date'] ?? date('Y-m-d'),
                'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                'is_current' => isset($_POST['is_current']) && $_POST['is_current'] === '1',
                'salary' => !empty($_POST['salary']) ? (float)$_POST['salary'] : null,
                'salary_currency' => trim($_POST['salary_currency'] ?? 'GBP'),
                'hours_per_week' => !empty($_POST['hours_per_week']) ? (float)$_POST['hours_per_week'] : null,
                'contract_type' => trim($_POST['contract_type'] ?? ''),
                'line_manager_id' => !empty($_POST['line_manager_id']) ? (int)$_POST['line_manager_id'] : null,
                'place_of_work' => trim($_POST['place_of_work'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
                'created_by' => $_SESSION['user_id']
            ];
            
            $roleResult = StaffRoleHistory::create($roleData);
            if ($roleResult) {
                $success = 'Role added successfully.';
                $roleHistory = StaffRoleHistory::getByPersonId($personId, $organisationId, true);
                $currentRole = StaffRoleHistory::getCurrentRole($personId, $organisationId);
            } else {
                $error = 'Failed to add role.';
            }
        } elseif ($action === 'update_role') {
            // Update role history entry
            $roleId = (int)($_POST['role_id'] ?? 0);
            if ($roleId > 0) {
                $roleData = [
                    'job_post_id' => !empty($_POST['job_post_id']) ? (int)$_POST['job_post_id'] : null,
                    'job_title' => trim($_POST['job_title'] ?? ''),
                    'start_date' => $_POST['start_date'] ?? null,
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'is_current' => isset($_POST['is_current']) && $_POST['is_current'] === '1',
                    'salary' => !empty($_POST['salary']) ? (float)$_POST['salary'] : null,
                    'salary_currency' => trim($_POST['salary_currency'] ?? 'GBP'),
                    'hours_per_week' => !empty($_POST['hours_per_week']) ? (float)$_POST['hours_per_week'] : null,
                    'contract_type' => trim($_POST['contract_type'] ?? ''),
                    'line_manager_id' => !empty($_POST['line_manager_id']) ? (int)$_POST['line_manager_id'] : null,
                    'place_of_work' => trim($_POST['place_of_work'] ?? ''),
                    'notes' => trim($_POST['notes'] ?? '')
                ];
                
                $roleResult = StaffRoleHistory::update($roleId, $roleData, $organisationId);
                if ($roleResult) {
                    $success = 'Role updated successfully.';
                    $roleHistory = StaffRoleHistory::getByPersonId($personId, $organisationId, true);
                    $currentRole = StaffRoleHistory::getCurrentRole($personId, $organisationId);
                } else {
                    $error = 'Failed to update role.';
                }
            }
        } elseif ($action === 'delete_role') {
            // Delete role history entry
            $roleId = (int)($_POST['role_id'] ?? 0);
            if ($roleId > 0) {
                $deleted = StaffRoleHistory::delete($roleId, $organisationId);
                if ($deleted) {
                    $success = 'Role deleted successfully.';
                    $roleHistory = StaffRoleHistory::getByPersonId($personId, $organisationId, true);
                    $currentRole = StaffRoleHistory::getCurrentRole($personId, $organisationId);
                } else {
                    $error = 'Failed to delete role.';
                }
            }
        } elseif ($action === 'end_role') {
            // End a current role
            $roleId = (int)($_POST['role_id'] ?? 0);
            $endDate = $_POST['end_date'] ?? date('Y-m-d');
            if ($roleId > 0) {
                $ended = StaffRoleHistory::endRole($roleId, $endDate, $organisationId);
                if ($ended) {
                    $success = 'Role ended successfully.';
                    $roleHistory = StaffRoleHistory::getByPersonId($personId, $organisationId, true);
                    $currentRole = StaffRoleHistory::getCurrentRole($personId, $organisationId);
                } else {
                    $error = 'Failed to end role.';
                }
            }
        } elseif ($action === 'add_learning') {
            // Add new learning record
            $learningData = [
                'person_id' => $personId,
                'organisation_id' => $organisationId,
                'record_type' => trim($_POST['record_type'] ?? 'course'),
                'title' => trim($_POST['title'] ?? ''),
                'description' => trim($_POST['description'] ?? ''),
                'provider' => trim($_POST['provider'] ?? ''),
                'qualification_level' => trim($_POST['qualification_level'] ?? ''),
                'subject_area' => trim($_POST['subject_area'] ?? ''),
                'completion_date' => !empty($_POST['completion_date']) ? $_POST['completion_date'] : null,
                'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                'grade' => trim($_POST['grade'] ?? ''),
                'credits' => !empty($_POST['credits']) ? (float)$_POST['credits'] : null,
                'certificate_number' => trim($_POST['certificate_number'] ?? ''),
                'external_url' => trim($_POST['external_url'] ?? ''),
                'is_mandatory' => isset($_POST['is_mandatory']) && $_POST['is_mandatory'] === '1',
                'is_required_for_role' => isset($_POST['is_required_for_role']) && $_POST['is_required_for_role'] === '1',
                'status' => trim($_POST['status'] ?? 'completed'),
                'notes' => trim($_POST['notes'] ?? ''),
                'created_by' => $_SESSION['user_id']
            ];
            
            $learningResult = StaffLearningRecord::create($learningData);
            if ($learningResult) {
                $success = 'Learning record added successfully.';
                $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
                $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
                $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
                $training = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'training']);
            } else {
                $error = 'Failed to add learning record.';
            }
        } elseif ($action === 'update_learning') {
            // Update learning record
            $learningId = (int)($_POST['learning_id'] ?? 0);
            if ($learningId > 0) {
                $learningData = [
                    'record_type' => trim($_POST['record_type'] ?? 'course'),
                    'title' => trim($_POST['title'] ?? ''),
                    'description' => trim($_POST['description'] ?? ''),
                    'provider' => trim($_POST['provider'] ?? ''),
                    'qualification_level' => trim($_POST['qualification_level'] ?? ''),
                    'subject_area' => trim($_POST['subject_area'] ?? ''),
                    'completion_date' => !empty($_POST['completion_date']) ? $_POST['completion_date'] : null,
                    'expiry_date' => !empty($_POST['expiry_date']) ? $_POST['expiry_date'] : null,
                    'grade' => trim($_POST['grade'] ?? ''),
                    'credits' => !empty($_POST['credits']) ? (float)$_POST['credits'] : null,
                    'certificate_number' => trim($_POST['certificate_number'] ?? ''),
                    'external_url' => trim($_POST['external_url'] ?? ''),
                    'is_mandatory' => isset($_POST['is_mandatory']) && $_POST['is_mandatory'] === '1',
                    'is_required_for_role' => isset($_POST['is_required_for_role']) && $_POST['is_required_for_role'] === '1',
                    'status' => trim($_POST['status'] ?? 'completed'),
                    'notes' => trim($_POST['notes'] ?? '')
                ];
                
                $learningResult = StaffLearningRecord::update($learningId, $learningData, $organisationId);
                if ($learningResult) {
                    $success = 'Learning record updated successfully.';
                    $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
                    $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
                    $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
                    $training = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'training']);
                } else {
                    $error = 'Failed to update learning record.';
                }
            }
        } elseif ($action === 'delete_learning') {
            // Delete learning record
            $learningId = (int)($_POST['learning_id'] ?? 0);
            if ($learningId > 0) {
                $deleted = StaffLearningRecord::delete($learningId, $organisationId);
                if ($deleted) {
                    $success = 'Learning record deleted successfully.';
                    $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
                    $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
                    $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
                    $training = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'training']);
                } else {
                    $error = 'Failed to delete learning record.';
                }
            }
        } elseif ($action === 'sync_learning') {
            // Sync learning from external system (placeholder for future integration)
            $sourceSystem = trim($_POST['source_system'] ?? '');
            // This would call an integration service to fetch records
            // For now, just show a message
            $success = 'Learning sync functionality will be available when external system integrations are configured.';
        } elseif ($action === 'assign_unit') {
            $unitId = (int)($_POST['organisational_unit_id'] ?? 0);
            $roleInUnit = trim($_POST['role_in_unit'] ?? 'member');
            $isPrimary = isset($_POST['is_primary']) && $_POST['is_primary'] === '1';
            
            if ($unitId > 0) {
                $result = Person::assignToOrganisationalUnit($personId, $unitId, $roleInUnit, $isPrimary);
                if ($result) {
                    $success = 'Organisational unit assigned successfully.';
                    $organisationalUnits = Person::getOrganisationalUnits($personId);
                } else {
                    $error = 'Failed to assign organisational unit.';
                }
            }
        } elseif ($action === 'remove_unit') {
            $unitId = (int)($_POST['organisational_unit_id'] ?? 0);
            if ($unitId > 0) {
                $result = Person::removeFromOrganisationalUnit($personId, $unitId);
                if ($result) {
                    $success = 'Organisational unit removed successfully.';
                    $organisationalUnits = Person::getOrganisationalUnits($personId);
                } else {
                    $error = 'Failed to remove organisational unit.';
                }
            }
        } elseif ($action === 'approve_photo') {
            // Approve pending photo
            if ($person['photo_pending_path']) {
                $pendingPath = PHOTO_UPLOAD_PATH . '/' . $person['photo_pending_path'];
                $approvedPath = PHOTO_UPLOAD_PATH . '/' . 'person_' . $personId . '_' . time() . '.' . pathinfo($person['photo_pending_path'], PATHINFO_EXTENSION);
                
                if (file_exists($pendingPath)) {
                    if (rename($pendingPath, $approvedPath)) {
                        $relativePath = str_replace(PHOTO_UPLOAD_PATH . '/', '', $approvedPath);
                        Person::update($personId, [
                            'photo_path' => $relativePath,
                            'photo_pending_path' => null,
                            'photo_approval_status' => 'approved'
                        ], $organisationId);
                        
                        // Delete old approved photo if exists
                        if ($person['photo_path'] && file_exists(PHOTO_UPLOAD_PATH . '/' . $person['photo_path'])) {
                            @unlink(PHOTO_UPLOAD_PATH . '/' . $person['photo_path']);
                        }
                        
                        $success = 'Photo approved successfully.';
                        $person = Person::findById($personId, $organisationId);
                    } else {
                        $error = 'Failed to approve photo.';
                    }
                }
            }
        } elseif ($action === 'reject_photo') {
            // Reject pending photo
            if ($person['photo_pending_path']) {
                $pendingPath = PHOTO_UPLOAD_PATH . '/' . $person['photo_pending_path'];
                if (file_exists($pendingPath)) {
                    @unlink($pendingPath);
                }
                Person::update($personId, [
                    'photo_pending_path' => null,
                    'photo_approval_status' => 'rejected'
                ], $organisationId);
                $success = 'Photo rejected and removed.';
                $person = Person::findById($personId, $organisationId);
            }
        } elseif ($action === 'save_signature') {
            // Handle signature save (upload or digital)
            $signatureMethod = $_POST['signature_method'] ?? 'digital';
            
            if ($signatureMethod === 'upload' && isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === UPLOAD_ERR_OK) {
                // Handle file upload
                $file = $_FILES['signature_file'];
                
                // Validate file type
                $allowedTypes = ALLOWED_SIGNATURE_TYPES;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $error = 'Invalid file type. Please upload a JPEG or PNG image.';
                } elseif ($file['size'] > MAX_SIGNATURE_SIZE) {
                    $error = 'File size too large. Maximum size is ' . (MAX_SIGNATURE_SIZE / 1024 / 1024) . 'MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = SIGNATURE_UPLOAD_PATH;
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'person_' . $personId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Delete old signature if exists
                        if ($person['signature_path'] && file_exists(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path'])) {
                            @unlink(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path']);
                        }
                        
                        // Update person record
                        $updateData = [
                            'signature_path' => $filename,
                            'signature_method' => 'upload',
                            'signature_created_at' => date('Y-m-d H:i:s')
                        ];
                        Person::update($personId, $updateData, $organisationId);
                        $success = 'Signature uploaded successfully.';
                        // Refresh person data
                        $person = Person::findById($personId, $organisationId);
                    } else {
                        $error = 'Failed to upload signature.';
                    }
                }
            } elseif ($signatureMethod === 'digital' && !empty($_POST['signature_data'])) {
                // Handle digital signature (base64 image data)
                $signatureData = $_POST['signature_data'];
                
                // Remove data URL prefix if present
                if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $matches)) {
                    $imageType = $matches[1];
                    $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
                } else {
                    $imageType = 'png';
                }
                
                // Decode base64
                $imageData = base64_decode($signatureData);
                
                if ($imageData === false) {
                    $error = 'Invalid signature data.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = SIGNATURE_UPLOAD_PATH;
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $filename = 'person_' . $personId . '_' . time() . '.png';
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // Save image
                    if (file_put_contents($filepath, $imageData)) {
                        // Delete old signature if exists
                        if ($person['signature_path'] && file_exists(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path'])) {
                            @unlink(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path']);
                        }
                        
                        // Update person record
                        $updateData = [
                            'signature_path' => $filename,
                            'signature_method' => 'digital',
                            'signature_created_at' => date('Y-m-d H:i:s')
                        ];
                        Person::update($personId, $updateData, $organisationId);
                        $success = 'Signature saved successfully.';
                        // Refresh person data
                        $person = Person::findById($personId, $organisationId);
                    } else {
                        $error = 'Failed to save signature.';
                    }
                }
            } else {
                $error = 'Please provide a signature (upload file or draw digitally).';
            }
        }
    }
}

// Get learning records (including from linked records)
$learningRecords = [];
$qualifications = [];
$courses = [];
$training = [];
if ($person['person_type'] === 'staff') {
    $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
    $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
    $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
    $training = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'training']);
}

// Get linked person records
$linkedRecords = Person::getLinkedPersonRecords($personId, $organisationId);

$pageTitle = 'Edit Staff Member';
include dirname(__DIR__, 2) . '/includes/header.php';
?>

<div class="card">
    <div style="display: flex; align-items: center; gap: 1rem; margin-bottom: 2rem;">
        <a href="<?php echo url('staff/view.php?id=' . $personId); ?>" style="color: #6b7280;">
            <i class="fas fa-arrow-left"></i>
        </a>
        <h1>Edit Staff Member</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <!-- Photo approval section -->
    <?php if ($person['photo_pending_path'] && $person['photo_approval_status'] === 'pending'): ?>
        <div class="alert" style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1.5rem; margin-bottom: 2rem;">
            <h3 style="margin-top: 0;">Pending Photo Approval</h3>
            <div style="display: flex; gap: 1rem; align-items: center; margin-top: 1rem;">
                <img src="<?php echo url('view-image.php?path=' . urlencode($person['photo_pending_path'])); ?>" alt="Pending Photo" style="width: 150px; height: 150px; object-fit: cover; border-radius: 0; border: 2px solid #f59e0b;">
                <div style="flex: 1;">
                    <form method="POST" action="" style="display: inline-block; margin-right: 1rem;">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="action" value="approve_photo">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check"></i> Approve
                        </button>
                    </form>
                    <form method="POST" action="" style="display: inline-block;">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="action" value="reject_photo">
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Reject
                        </button>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
        <div class="profile-form-container" style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem; position: relative;">
            <!-- Sidebar Navigation -->
            <div class="profile-sidebar" style="position: sticky; top: 2rem; align-self: start; height: fit-content;">
                <div style="background: white; border-right: 1px solid #e5e7eb; padding: 0;">
                    <nav class="sidebar-nav" style="padding: 1rem 0; text-align: left;">
                        <a href="#section-personal" class="sidebar-nav-link">
                            <i class="fas fa-user"></i> <span>Personal Information</span>
                        </a>
                        <?php if ($person['person_type'] === 'staff'): ?>
                            <a href="#section-address" class="sidebar-nav-link">
                                <i class="fas fa-map-marker-alt"></i> <span>Address</span>
                            </a>
                            <a href="#section-identification" class="sidebar-nav-link">
                                <i class="fas fa-id-card"></i> <span>Identification</span>
                            </a>
                            <a href="#section-banking" class="sidebar-nav-link">
                                <i class="fas fa-university"></i> <span>Banking Information</span>
                            </a>
                            <a href="#section-employment" class="sidebar-nav-link">
                                <i class="fas fa-briefcase"></i> <span>Employment</span>
                            </a>
                            <a href="#section-tupe" class="sidebar-nav-link">
                                <i class="fas fa-exchange-alt"></i> <span>TUPE Information</span>
                            </a>
                            <a href="#section-wtd" class="sidebar-nav-link">
                                <i class="fas fa-clock"></i> <span>WTD Agreement</span>
                            </a>
                            <a href="#section-emergency" class="sidebar-nav-link">
                                <i class="fas fa-phone-alt"></i> <span>Emergency Contact</span>
                            </a>
                            <a href="#section-leave" class="sidebar-nav-link">
                                <i class="fas fa-calendar-alt"></i> <span>Leave Management</span>
                            </a>
                            <a href="#section-contract" class="sidebar-nav-link">
                                <i class="fas fa-file-contract"></i> <span>Contract & Status</span>
                            </a>
                            <a href="#section-registrations" class="sidebar-nav-link">
                                <i class="fas fa-certificate"></i> <span>Registrations</span>
                            </a>
                            <a href="#section-role-history" class="sidebar-nav-link">
                                <i class="fas fa-history"></i> <span>Role History</span>
                            </a>
                            <a href="#section-learning" class="sidebar-nav-link">
                                <i class="fas fa-graduation-cap"></i> <span>Learning & Qualifications</span>
                            </a>
                            <a href="#section-signature" class="sidebar-nav-link">
                                <i class="fas fa-pen"></i> <span>Signature</span>
                            </a>
                            <a href="#section-organisational-units" class="sidebar-nav-link">
                                <i class="fas fa-sitemap"></i> <span>Organisational Units</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div>
                <form method="POST" action="" id="staff-edit-form">
                    <?php echo CSRF::tokenField(); ?>
                    <input type="hidden" name="action" value="update">
                    
                    <!-- Sticky Save Button at Top -->
                    <div class="sticky-save-button" style="position: sticky; top: 70px; background: white; padding: 1rem 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 2rem; z-index: 999; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <button type="submit" class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                    
                    <div id="section-personal" style="margin-bottom: 2rem; scroll-margin-top: 2rem;">
                        <h2>Personal Information</h2>
        <div class="form-group">
            <label for="first_name">First Name <span style="color: #dc2626;">*</span></label>
            <input type="text" id="first_name" name="first_name" value="<?php echo htmlspecialchars($person['first_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="last_name">Last Name <span style="color: #dc2626;">*</span></label>
            <input type="text" id="last_name" name="last_name" value="<?php echo htmlspecialchars($person['last_name']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($person['email'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="phone">Phone</label>
            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($person['phone'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="date_of_birth">Date of Birth</label>
            <input type="date" id="date_of_birth" name="date_of_birth" value="<?php echo $person['date_of_birth'] ? date('Y-m-d', strtotime($person['date_of_birth'])) : ''; ?>">
        </div>
        
        <div class="form-group">
            <label for="employee_reference">Employee Reference</label>
            <input type="text" id="employee_reference" name="employee_reference" value="<?php echo htmlspecialchars($person['employee_reference'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="user_id">Link to User Account</label>
            <select id="user_id" name="user_id">
                <option value="">None - No user account link</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?php echo $user['id']; ?>" <?php echo $person['user_id'] == $user['id'] ? 'selected' : ''; ?>>
                        <?php 
                        $display = htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['email'] . ')');
                        if ($user['has_other_profile']) {
                            $display .= ' [Has other profile]';
                        }
                        echo $display;
                        ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>
                <?php if ($person['user_id']): ?>
                    Currently linked to user account. You can change or remove the link.
                <?php else: ?>
                    Link this staff profile to a user account to allow the user to log in and view/edit their profile.
                <?php endif; ?>
            </small>
        </div>
        
        <?php if ($person['person_type'] === 'staff'): ?>
            <div id="section-employment" style="margin-top: 2rem; scroll-margin-top: 2rem;">
                <h2>Employment Information</h2>
                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                    <strong>Employment Start Date</strong> is when they joined the organisation. 
                    <strong>Role History</strong> (see Role History section below) tracks specific roles, salary changes, and position changes over time.
                </p>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                    <div class="form-group">
                        <label for="employment_start_date">Employment Start Date</label>
                        <input type="date" id="employment_start_date" name="employment_start_date" 
                               value="<?php echo $person['employment_start_date'] ? date('Y-m-d', strtotime($person['employment_start_date'])) : ''; ?>">
                        <small>Date they joined the organisation</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="employment_end_date">Employment End Date</label>
                        <input type="date" id="employment_end_date" name="employment_end_date" 
                               value="<?php echo $person['employment_end_date'] ? date('Y-m-d', strtotime($person['employment_end_date'])) : ''; ?>">
                        <small>Leave blank if currently employed</small>
                    </div>
                </div>
                
                <?php if ($currentRole): ?>
                    <div style="padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; margin-bottom: 1rem;">
                        <strong>Current Role:</strong> <?php echo htmlspecialchars($currentRole['job_title'] ?: $currentRole['job_post_title'] ?: 'N/A'); ?>
                        <?php if ($currentRole['salary']): ?>
                            | <strong>Salary:</strong> <?php echo $currentRole['salary_currency'] ?? 'GBP'; ?> <?php echo number_format($currentRole['salary'], 2); ?>
                        <?php endif; ?>
                        <?php if ($currentRole['start_date']): ?>
                            | <strong>Role Started:</strong> <?php echo date('d/m/Y', strtotime($currentRole['start_date'])); ?>
                        <?php endif; ?>
                        <br><small style="color: #6b7280;">Manage roles in the <a href="#section-role-history">Role History</a> section below</small>
                    </div>
                <?php endif; ?>
            
            <div id="section-tupe" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                <h3>TUPE (Transfer of Undertakings) Information</h3>
            <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                If this staff member transferred from another organisation under TUPE regulations, their original terms (contract type, hours, salary) must be preserved and override the job post terms.
            </p>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_tupe" value="1" id="is_tupe_checkbox" <?php echo ($person['is_tupe'] ?? false) ? 'checked' : ''; ?>>
                    This staff member has a TUPE contract
                </label>
            </div>
            
            <div id="tupe-fields" style="<?php echo ($person['is_tupe'] ?? false) ? '' : 'display: none;'; ?>">
                <div class="form-group">
                    <label for="tupe_transfer_date">TUPE Transfer Date</label>
                    <input type="date" id="tupe_transfer_date" name="tupe_transfer_date" value="<?php echo htmlspecialchars($person['tupe_transfer_date'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="tupe_previous_organisation">Previous Organisation</label>
                    <input type="text" id="tupe_previous_organisation" name="tupe_previous_organisation" value="<?php echo htmlspecialchars($person['tupe_previous_organisation'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="tupe_previous_employer_ref">Previous Employer Reference</label>
                    <input type="text" id="tupe_previous_employer_ref" name="tupe_previous_employer_ref" value="<?php echo htmlspecialchars($person['tupe_previous_employer_ref'] ?? ''); ?>">
                    <small>Reference number or ID from previous employer</small>
                </div>
                
                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem;">TUPE Terms (Override Job Post)</h4>
                
                <div class="form-group">
                    <label for="tupe_contract_type">Contract Type (TUPE)</label>
                    <select id="tupe_contract_type" name="tupe_contract_type">
                        <option value="">Select...</option>
                        <option value="Permanent" <?php echo ($person['tupe_contract_type'] ?? '') === 'Permanent' ? 'selected' : ''; ?>>Permanent</option>
                        <option value="Temporary" <?php echo ($person['tupe_contract_type'] ?? '') === 'Temporary' ? 'selected' : ''; ?>>Temporary</option>
                        <option value="Contract" <?php echo ($person['tupe_contract_type'] ?? '') === 'Contract' ? 'selected' : ''; ?>>Contract</option>
                        <option value="Part-time" <?php echo ($person['tupe_contract_type'] ?? '') === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Full-time" <?php echo ($person['tupe_contract_type'] ?? '') === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                    </select>
                    <small>Contract type under TUPE (overrides job post)</small>
                </div>
                
                <div class="form-group">
                    <label for="tupe_hours_per_week">Hours per Week (TUPE)</label>
                    <input type="number" id="tupe_hours_per_week" name="tupe_hours_per_week" 
                           value="<?php echo htmlspecialchars($person['tupe_hours_per_week'] ?? ''); ?>" 
                           step="0.5" min="0" max="168">
                    <small>Hours per week under TUPE (overrides job post)</small>
                </div>
                
                <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 1rem;">
                    <div class="form-group">
                        <label for="tupe_salary">Salary (TUPE)</label>
                        <input type="number" id="tupe_salary" name="tupe_salary" 
                               value="<?php echo htmlspecialchars($person['tupe_salary'] ?? ''); ?>" 
                               step="0.01" min="0">
                        <small>Salary under TUPE (overrides job post)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="tupe_salary_currency">Currency</label>
                        <select id="tupe_salary_currency" name="tupe_salary_currency">
                            <option value="GBP" <?php echo ($person['tupe_salary_currency'] ?? 'GBP') === 'GBP' ? 'selected' : ''; ?>>GBP (£)</option>
                            <option value="USD" <?php echo ($person['tupe_salary_currency'] ?? '') === 'USD' ? 'selected' : ''; ?>>USD ($)</option>
                            <option value="EUR" <?php echo ($person['tupe_salary_currency'] ?? '') === 'EUR' ? 'selected' : ''; ?>>EUR (€)</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="tupe_notes">TUPE Notes</label>
                    <textarea id="tupe_notes" name="tupe_notes" rows="3"><?php echo htmlspecialchars($person['tupe_notes'] ?? ''); ?></textarea>
                    <small>Additional notes about the TUPE transfer</small>
                </div>
            </div>
            
            <div id="section-wtd" style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                <h3>Working Time Directive (WTD) Agreement</h3>
            <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                The Working Time Directive limits working hours. Staff must agree to WTD terms, and may opt out of the 48-hour week limit (UK only).
            </p>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="wtd_agreed" value="1" id="wtd_agreed_checkbox" <?php echo ($person['wtd_agreed'] ?? false) ? 'checked' : ''; ?>>
                    Staff member has agreed to Working Time Directive
                </label>
            </div>
            
            <div id="wtd-agreement-fields" style="<?php echo ($person['wtd_agreed'] ?? false) ? '' : 'display: none;'; ?>">
                <div class="form-group">
                    <label for="wtd_agreement_date">Agreement Date</label>
                    <input type="date" id="wtd_agreement_date" name="wtd_agreement_date" value="<?php echo htmlspecialchars($person['wtd_agreement_date'] ?? ''); ?>">
                    <small>Date when the WTD agreement was signed</small>
                </div>
                
                <div class="form-group">
                    <label for="wtd_agreement_version">Agreement Version</label>
                    <input type="text" id="wtd_agreement_version" name="wtd_agreement_version" value="<?php echo htmlspecialchars($person['wtd_agreement_version'] ?? ''); ?>" placeholder="e.g. v1.0, 2024-01">
                    <small>Version of the WTD agreement document they signed</small>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="wtd_opt_out" value="1" id="wtd_opt_out_checkbox" <?php echo ($person['wtd_opt_out'] ?? false) ? 'checked' : ''; ?>>
                        Staff member has opted out of 48-hour week limit
                    </label>
                    <small>UK allows staff to opt out of the 48-hour average working week limit</small>
                </div>
                
                <div id="wtd-opt-out-fields" style="<?php echo ($person['wtd_opt_out'] ?? false) ? '' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label for="wtd_opt_out_date">Opt-Out Date</label>
                        <input type="date" id="wtd_opt_out_date" name="wtd_opt_out_date" value="<?php echo htmlspecialchars($person['wtd_opt_out_date'] ?? ''); ?>">
                        <small>Date when the opt-out was signed</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="wtd_opt_out_expiry_date">Opt-Out Expiry Date</label>
                        <input type="date" id="wtd_opt_out_expiry_date" name="wtd_opt_out_expiry_date" value="<?php echo htmlspecialchars($person['wtd_opt_out_expiry_date'] ?? ''); ?>">
                        <small>Optional: Date when the opt-out expires (if applicable)</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="wtd_notes">WTD Notes</label>
                    <textarea id="wtd_notes" name="wtd_notes" rows="3"><?php echo htmlspecialchars($person['wtd_notes'] ?? ''); ?></textarea>
                    <small>Additional notes about the WTD agreement</small>
                </div>
            </div>
            
            <div class="form-group">
                <label for="employment_start_date">Employment Start Date</label>
                <input type="date" id="employment_start_date" name="employment_start_date" value="<?php echo $person['employment_start_date'] ? date('Y-m-d', strtotime($person['employment_start_date'])) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="employment_end_date">Employment End Date</label>
                <input type="date" id="employment_end_date" name="employment_end_date" value="<?php echo $person['employment_end_date'] ? date('Y-m-d', strtotime($person['employment_end_date'])) : ''; ?>">
            </div>
            
            <div class="form-group">
                <label for="line_manager_id">Line Manager</label>
                <select id="line_manager_id" name="line_manager_id">
                    <option value="">None</option>
                    <?php foreach ($staffForManager as $staff): ?>
                        <option value="<?php echo $staff['id']; ?>" <?php echo $person['line_manager_id'] == $staff['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div id="section-emergency" style="margin-top: 1.5rem; scroll-margin-top: 2rem;">
                <h3>Emergency Contact</h3>
                <div class="form-group">
                <label for="emergency_contact_name">Emergency Contact Name</label>
                <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($person['emergency_contact_name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="emergency_contact_phone">Emergency Contact Phone</label>
                <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($person['emergency_contact_phone'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="notes">Notes</label>
                <textarea id="notes" name="notes" rows="4"><?php echo htmlspecialchars($person['notes'] ?? ''); ?></textarea>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label>
                <input type="checkbox" name="is_active" value="1" <?php echo $person['is_active'] ? 'checked' : ''; ?>>
                Active
            </label>
        </div>
                </form>
                
                <!-- Staff Registrations -->
                <?php if ($person['person_type'] === 'staff'): ?>
                    <div id="section-registrations" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h3>Professional Registrations</h3>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            Track professional registrations and certifications that may expire (e.g., Social Services registration, HCPC, NMC).
                        </p>
                        
                        <?php
                        $registrations = StaffRegistration::getByPersonId($person['id'], $_SESSION['organisation_id']);
                        ?>
                        
                        <?php if (!empty($registrations)): ?>
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                        <th style="padding: 0.75rem; text-align: left;">Type</th>
                                        <th style="padding: 0.75rem; text-align: left;">Number</th>
                                        <th style="padding: 0.75rem; text-align: left;">Body</th>
                                        <th style="padding: 0.75rem; text-align: left;">Expiry Date</th>
                                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrations as $reg): ?>
                                        <?php 
                                        $daysUntilExpiry = StaffRegistration::daysUntilExpiry($reg['expiry_date']);
                                        $isExpired = $daysUntilExpiry !== null && $daysUntilExpiry < 0;
                                        $isExpiringSoon = $daysUntilExpiry !== null && $daysUntilExpiry >= 0 && $daysUntilExpiry <= 90;
                                        ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb;">
                                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($reg['registration_type']); ?></td>
                                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($reg['registration_number'] ?? '-'); ?></td>
                                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($reg['registration_body'] ?? '-'); ?></td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo $reg['expiry_date'] ? date('d/m/Y', strtotime($reg['expiry_date'])) : '-'; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php if ($isExpired): ?>
                                                    <span style="color: #dc2626; font-weight: 600;">
                                                        <i class="fas fa-times-circle"></i> Expired
                                                    </span>
                                                <?php elseif ($isExpiringSoon): ?>
                                                    <span style="color: #f59e0b; font-weight: 600;">
                                                        <i class="fas fa-exclamation-triangle"></i> Expires in <?php echo $daysUntilExpiry; ?> days
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #059669;">
                                                        <i class="fas fa-check-circle"></i> Active
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem; text-align: right;">
                                                <a href="?id=<?php echo $person['id']; ?>&edit_registration=<?php echo $reg['id']; ?>" 
                                                   class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <form method="POST" action="" style="display: inline-block;">
                                                    <?php echo CSRF::tokenField(); ?>
                                                    <input type="hidden" name="action" value="delete_registration">
                                                    <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" 
                                                            style="padding: 0.375rem 0.75rem; font-size: 0.875rem;"
                                                            onclick="return confirm('Are you sure you want to delete this registration?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #6b7280; margin-bottom: 1rem;">No registrations recorded.</p>
                        <?php endif; ?>
                        
                        <div style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; margin-bottom: 1rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">Add New Registration</h4>
                            <form method="POST" action="" style="display: grid; grid-template-columns: 2fr 2fr 2fr 1fr 1fr auto; gap: 0.75rem; align-items: end;">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="add_registration">
                                <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="new_registration_type" style="font-size: 0.875rem;">Type</label>
                                    <input type="text" id="new_registration_type" name="registration_type" required 
                                           placeholder="e.g. Social Services" style="font-size: 0.875rem; padding: 0.5rem;">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="new_registration_number" style="font-size: 0.875rem;">Number</label>
                                    <input type="text" id="new_registration_number" name="registration_number" 
                                           placeholder="Registration number" style="font-size: 0.875rem; padding: 0.5rem;">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="new_registration_body" style="font-size: 0.875rem;">Body</label>
                                    <input type="text" id="new_registration_body" name="registration_body" 
                                           placeholder="e.g. Social Care Wales" style="font-size: 0.875rem; padding: 0.5rem;">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="new_expiry_date" style="font-size: 0.875rem;">Expiry Date</label>
                                    <input type="date" id="new_expiry_date" name="expiry_date" required 
                                           style="font-size: 0.875rem; padding: 0.5rem;">
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label style="font-size: 0.875rem;">
                                        <input type="checkbox" name="is_required_for_role" value="1" checked style="margin-right: 0.25rem;">
                                        Required
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-plus"></i> Add
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Role History -->
                <?php if ($person['person_type'] === 'staff'): ?>
                    <div id="section-role-history" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h3>Role History & Salary Tracking</h3>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            Track role changes and salary history over time. Employment start date is when they joined the organisation, 
                            while role start dates track when they started specific positions.
                        </p>
                        
                        <?php if (!empty($roleHistory)): ?>
                            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                        <th style="padding: 0.75rem; text-align: left;">Role</th>
                                        <th style="padding: 0.75rem; text-align: left;">Start Date</th>
                                        <th style="padding: 0.75rem; text-align: left;">End Date</th>
                                        <th style="padding: 0.75rem; text-align: left;">Salary</th>
                                        <th style="padding: 0.75rem; text-align: left;">Hours/Week</th>
                                        <th style="padding: 0.75rem; text-align: left;">Status</th>
                                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($roleHistory as $role): ?>
                                        <tr style="border-bottom: 1px solid #e5e7eb; <?php echo $role['is_current'] ? 'background: #eff6ff;' : ''; ?>">
                                            <td style="padding: 0.75rem;">
                                                <strong><?php echo htmlspecialchars($role['job_title'] ?: $role['job_post_title'] ?: 'N/A'); ?></strong>
                                                <?php if ($role['job_post_title'] && $role['job_title'] !== $role['job_post_title']): ?>
                                                    <br><small style="color: #6b7280;">(Job Post: <?php echo htmlspecialchars($role['job_post_title']); ?>)</small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo $role['start_date'] ? date('d/m/Y', strtotime($role['start_date'])) : '-'; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo $role['end_date'] ? date('d/m/Y', strtotime($role['end_date'])) : '<em>Current</em>'; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php if ($role['salary']): ?>
                                                    <?php echo $role['salary_currency'] ?? 'GBP'; ?> <?php echo number_format($role['salary'], 2); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php echo $role['hours_per_week'] ? number_format($role['hours_per_week'], 1) . 'h' : '-'; ?>
                                            </td>
                                            <td style="padding: 0.75rem;">
                                                <?php if ($role['is_current']): ?>
                                                    <span style="color: #059669; font-weight: 600;">
                                                        <i class="fas fa-check-circle"></i> Current
                                                    </span>
                                                <?php else: ?>
                                                    <span style="color: #6b7280;">Past</span>
                                                <?php endif; ?>
                                            </td>
                                            <td style="padding: 0.75rem; text-align: right;">
                                                <a href="?id=<?php echo $person['id']; ?>&edit_role=<?php echo $role['id']; ?>" 
                                                   class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <?php if ($role['is_current'] && !$role['end_date']): ?>
                                                    <form method="POST" action="" style="display: inline-block;">
                                                        <?php echo CSRF::tokenField(); ?>
                                                        <input type="hidden" name="action" value="end_role">
                                                        <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                        <input type="hidden" name="end_date" value="<?php echo date('Y-m-d'); ?>">
                                                        <button type="submit" class="btn btn-warning" 
                                                                style="padding: 0.375rem 0.75rem; font-size: 0.875rem;"
                                                                onclick="return confirm('End this role?');">
                                                            <i class="fas fa-stop"></i> End
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <form method="POST" action="" style="display: inline-block;">
                                                    <?php echo CSRF::tokenField(); ?>
                                                    <input type="hidden" name="action" value="delete_role">
                                                    <input type="hidden" name="role_id" value="<?php echo $role['id']; ?>">
                                                    <button type="submit" class="btn btn-danger" 
                                                            style="padding: 0.375rem 0.75rem; font-size: 0.875rem;"
                                                            onclick="return confirm('Are you sure you want to delete this role?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p style="color: #6b7280; margin-bottom: 1rem;">No role history recorded.</p>
                        <?php endif; ?>
                        
                        <div style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; margin-bottom: 1rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">Add New Role</h4>
                            <form method="POST" action="">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="add_role">
                                <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_job_post_id" style="font-size: 0.875rem;">Job Post (Optional)</label>
                                        <select id="new_role_job_post_id" name="job_post_id" style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="">Select job post...</option>
                                            <?php foreach ($jobPosts as $post): ?>
                                                <option value="<?php echo $post['id']; ?>">
                                                    <?php echo htmlspecialchars($post['title']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_job_title" style="font-size: 0.875rem;">Job Title *</label>
                                        <input type="text" id="new_role_job_title" name="job_title" required 
                                               placeholder="e.g. Support Worker" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_start_date" style="font-size: 0.875rem;">Start Date *</label>
                                        <input type="date" id="new_role_start_date" name="start_date" required 
                                               value="<?php echo date('Y-m-d'); ?>" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_end_date" style="font-size: 0.875rem;">End Date</label>
                                        <input type="date" id="new_role_end_date" name="end_date" 
                                               style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-size: 0.875rem;">
                                            <input type="checkbox" name="is_current" value="1" checked style="margin-right: 0.25rem;">
                                            Current Role
                                        </label>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_salary" style="font-size: 0.875rem;">Salary</label>
                                        <input type="number" id="new_role_salary" name="salary" step="0.01" min="0"
                                               placeholder="0.00" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_salary_currency" style="font-size: 0.875rem;">Currency</label>
                                        <select id="new_role_salary_currency" name="salary_currency" style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="GBP" selected>GBP</option>
                                            <option value="USD">USD</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_hours_per_week" style="font-size: 0.875rem;">Hours/Week</label>
                                        <input type="number" id="new_role_hours_per_week" name="hours_per_week" step="0.5" min="0"
                                               placeholder="0.0" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_contract_type" style="font-size: 0.875rem;">Contract Type</label>
                                        <select id="new_role_contract_type" name="contract_type" style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="">Select...</option>
                                            <option value="permanent">Permanent</option>
                                            <option value="fixed_term">Fixed Term</option>
                                            <option value="zero_hours">Zero Hours</option>
                                            <option value="bank">Bank/Casual</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_line_manager_id" style="font-size: 0.875rem;">Line Manager</label>
                                        <select id="new_role_line_manager_id" name="line_manager_id" style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="">Select manager...</option>
                                            <?php foreach ($staffForManager as $manager): ?>
                                                <option value="<?php echo $manager['id']; ?>">
                                                    <?php echo htmlspecialchars($manager['first_name'] . ' ' . $manager['last_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_role_place_of_work" style="font-size: 0.875rem;">Place of Work</label>
                                        <input type="text" id="new_role_place_of_work" name="place_of_work" 
                                               placeholder="e.g. Main Office" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="new_role_notes" style="font-size: 0.875rem;">Notes</label>
                                    <textarea id="new_role_notes" name="notes" rows="2" 
                                              placeholder="Additional notes about this role..." 
                                              style="font-size: 0.875rem; padding: 0.5rem;"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-plus"></i> Add Role
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Learning & Qualifications -->
                <?php if ($person['person_type'] === 'staff'): ?>
                    <div id="section-learning" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                            <div>
                                <h3>Learning & Qualifications</h3>
                                <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                                    Track qualifications from recruitment, courses from LMS, and training completed since joining. 
                                    Records can be synced from external systems or added manually.
                                    <?php 
                                    $currentRecords = array_filter($learningRecords, function($r) { return empty($r['is_from_linked_record']); });
                                    $linkedRecordsCount = count($learningRecords) - count($currentRecords);
                                    if ($linkedRecordsCount > 0): 
                                    ?>
                                        <br><strong>Note:</strong> <?php echo $linkedRecordsCount; ?> record(s) from previous employment are included.
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if (empty($linkedRecords)): ?>
                                <a href="<?php echo url('staff/link-records.php?id=' . $personId); ?>" class="btn btn-secondary" style="font-size: 0.875rem;">
                                    <i class="fas fa-link"></i> Link Previous Records
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (!empty($linkedRecords)): ?>
                            <div style="background: #f0f9ff; border: 1px solid #bae6fd; padding: 1rem; margin-bottom: 1.5rem; border-radius: 0;">
                                <p style="margin: 0 0 0.5rem 0; font-weight: 600; color: #0369a1;">
                                    <i class="fas fa-link"></i> Linked Previous Records
                                </p>
                                <p style="margin: 0; font-size: 0.875rem; color: #6b7280;">
                                    Learning records from <?php echo count($linkedRecords); ?> previous employment period(s) are included below.
                                    <a href="<?php echo url('staff/link-records.php?id=' . $personId); ?>" style="color: #2563eb; margin-left: 0.5rem;">
                                        Manage links
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($learningRecords)): ?>
                            <!-- Qualifications (from recruitment) -->
                            <?php if (!empty($qualifications)): ?>
                                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-certificate"></i> Qualifications
                                </h4>
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                            <th style="padding: 0.75rem; text-align: left;">Qualification</th>
                                            <th style="padding: 0.75rem; text-align: left;">Level</th>
                                            <th style="padding: 0.75rem; text-align: left;">Provider</th>
                                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
                                            <th style="padding: 0.75rem; text-align: left;">Source</th>
                                            <th style="padding: 0.75rem; text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($qualifications as $qual): ?>
                                            <tr style="border-bottom: 1px solid #e5e7eb; <?php echo !empty($qual['is_from_linked_record']) ? 'background: #f9fafb;' : ''; ?>">
                                                <td style="padding: 0.75rem;">
                                                    <strong><?php echo htmlspecialchars($qual['title']); ?></strong>
                                                    <?php if (!empty($qual['is_from_linked_record'])): ?>
                                                        <br><span style="color: #6b7280; font-size: 0.75rem; font-style: italic;">
                                                            <i class="fas fa-link"></i> From previous employment
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($qual['subject_area']): ?>
                                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($qual['subject_area']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($qual['qualification_level'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($qual['provider'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <?php echo $qual['completion_date'] ? date('d/m/Y', strtotime($qual['completion_date'])) : '-'; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php if ($qual['source_system']): ?>
                                                        <span style="font-size: 0.875rem; color: #6b7280;">
                                                            <?php echo ucfirst($qual['source_system']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.875rem; color: #6b7280;">Manual</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem; text-align: right;">
                                                    <?php if ($qual['external_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($qual['external_url']); ?>" target="_blank" 
                                                           class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; margin-right: 0.5rem;">
                                                            <i class="fas fa-external-link-alt"></i> View
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (empty($qual['is_from_linked_record'])): ?>
                                                        <a href="?id=<?php echo $person['id']; ?>&edit_learning=<?php echo $qual['id']; ?>" 
                                                           class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #6b7280; font-size: 0.875rem; font-style: italic;">Read-only</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <!-- Courses & Training -->
                            <?php if (!empty($courses) || !empty($training)): ?>
                                <h4 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-book"></i> Courses & Training
                                </h4>
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                            <th style="padding: 0.75rem; text-align: left;">Title</th>
                                            <th style="padding: 0.75rem; text-align: left;">Provider</th>
                                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
                                            <th style="padding: 0.75rem; text-align: left;">Expires</th>
                                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                                            <th style="padding: 0.75rem; text-align: left;">Source</th>
                                            <th style="padding: 0.75rem; text-align: right;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_merge($courses, $training) as $record): ?>
                                            <?php 
                                            $daysUntilExpiry = null;
                                            $isExpired = false;
                                            if ($record['expiry_date']) {
                                                $expiryDate = new DateTime($record['expiry_date']);
                                                $today = new DateTime();
                                                $daysUntilExpiry = $today->diff($expiryDate)->days;
                                                $isExpired = $expiryDate < $today;
                                            }
                                            ?>
                                            <tr style="border-bottom: 1px solid #e5e7eb; <?php echo $record['is_mandatory'] ? 'background: #fef3c7;' : (!empty($record['is_from_linked_record']) ? 'background: #f9fafb;' : ''); ?>">
                                                <td style="padding: 0.75rem;">
                                                    <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                                                    <?php if (!empty($record['is_from_linked_record'])): ?>
                                                        <br><span style="color: #6b7280; font-size: 0.75rem; font-style: italic;">
                                                            <i class="fas fa-link"></i> From previous employment
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($record['is_mandatory']): ?>
                                                        <span style="color: #f59e0b; font-size: 0.75rem; margin-left: 0.5rem;">
                                                            <i class="fas fa-exclamation-circle"></i> Mandatory
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($record['provider'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <?php echo $record['completion_date'] ? date('d/m/Y', strtotime($record['completion_date'])) : '-'; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php if ($record['expiry_date']): ?>
                                                        <?php echo date('d/m/Y', strtotime($record['expiry_date'])); ?>
                                                        <?php if ($isExpired): ?>
                                                            <br><small style="color: #dc2626;">
                                                                <i class="fas fa-times-circle"></i> Expired
                                                            </small>
                                                        <?php elseif ($daysUntilExpiry <= 90): ?>
                                                            <br><small style="color: #f59e0b;">
                                                                <i class="fas fa-exclamation-triangle"></i> Expires in <?php echo $daysUntilExpiry; ?> days
                                                            </small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <span style="font-size: 0.875rem; padding: 0.25rem 0.5rem; border-radius: 0.25rem; 
                                                          background: <?php 
                                                          echo $record['status'] === 'completed' ? '#d1fae5' : 
                                                               ($record['status'] === 'in_progress' ? '#dbeafe' : '#fee2e2'); 
                                                          ?>; 
                                                          color: <?php 
                                                          echo $record['status'] === 'completed' ? '#065f46' : 
                                                               ($record['status'] === 'in_progress' ? '#1e40af' : '#991b1b'); 
                                                          ?>;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                    </span>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php if ($record['source_system']): ?>
                                                        <span style="font-size: 0.875rem; color: #6b7280;">
                                                            <?php echo ucfirst($record['source_system']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span style="font-size: 0.875rem; color: #6b7280;">Manual</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem; text-align: right;">
                                                    <?php if ($record['external_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($record['external_url']); ?>" target="_blank" 
                                                           class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem; margin-right: 0.5rem;">
                                                            <i class="fas fa-external-link-alt"></i> View
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if (empty($record['is_from_linked_record'])): ?>
                                                        <a href="?id=<?php echo $person['id']; ?>&edit_learning=<?php echo $record['id']; ?>" 
                                                           class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;">
                                                            <i class="fas fa-edit"></i> Edit
                                                        </a>
                                                    <?php else: ?>
                                                        <span style="color: #6b7280; font-size: 0.875rem; font-style: italic;">Read-only</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #6b7280; margin-bottom: 1rem;">No learning records found. Add qualifications and courses below.</p>
                        <?php endif; ?>
                        
                        <!-- Add New Learning Record -->
                        <div style="padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb; margin-bottom: 1rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">Add Learning Record</h4>
                            <form method="POST" action="">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="add_learning">
                                <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_record_type" style="font-size: 0.875rem;">Type *</label>
                                        <select id="new_learning_record_type" name="record_type" required style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="qualification">Qualification</option>
                                            <option value="course" selected>Course</option>
                                            <option value="training">Training</option>
                                            <option value="certification">Certification</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_title" style="font-size: 0.875rem;">Title *</label>
                                        <input type="text" id="new_learning_title" name="title" required 
                                               placeholder="e.g. Health & Safety Level 2" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_provider" style="font-size: 0.875rem;">Provider</label>
                                        <input type="text" id="new_learning_provider" name="provider" 
                                               placeholder="e.g. Training Company Ltd" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_level" style="font-size: 0.875rem;">Level</label>
                                        <input type="text" id="new_learning_level" name="qualification_level" 
                                               placeholder="e.g. Level 2, Degree" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_completion_date" style="font-size: 0.875rem;">Completion Date</label>
                                        <input type="date" id="new_learning_completion_date" name="completion_date" 
                                               style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_expiry_date" style="font-size: 0.875rem;">Expiry Date</label>
                                        <input type="date" id="new_learning_expiry_date" name="expiry_date" 
                                               style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_status" style="font-size: 0.875rem;">Status</label>
                                        <select id="new_learning_status" name="status" style="font-size: 0.875rem; padding: 0.5rem;">
                                            <option value="completed" selected>Completed</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="pending">Pending</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_subject_area" style="font-size: 0.875rem;">Subject Area</label>
                                        <input type="text" id="new_learning_subject_area" name="subject_area" 
                                               placeholder="e.g. Health & Safety" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_grade" style="font-size: 0.875rem;">Grade/Result</label>
                                        <input type="text" id="new_learning_grade" name="grade" 
                                               placeholder="e.g. Pass, Distinction" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label for="new_learning_external_url" style="font-size: 0.875rem;">External URL</label>
                                        <input type="url" id="new_learning_external_url" name="external_url" 
                                               placeholder="Link to LMS/HR system" style="font-size: 0.875rem; padding: 0.5rem;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-size: 0.875rem;">
                                            <input type="checkbox" name="is_mandatory" value="1" style="margin-right: 0.25rem;">
                                            Mandatory Training
                                        </label>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label style="font-size: 0.875rem;">
                                            <input type="checkbox" name="is_required_for_role" value="1" style="margin-right: 0.25rem;">
                                            Required for Role
                                        </label>
                                    </div>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 1rem;">
                                    <label for="new_learning_description" style="font-size: 0.875rem;">Description</label>
                                    <textarea id="new_learning_description" name="description" rows="2" 
                                              placeholder="Additional details..." 
                                              style="font-size: 0.875rem; padding: 0.5rem;"></textarea>
                                </div>
                                
                                <button type="submit" class="btn btn-primary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-plus"></i> Add Learning Record
                                </button>
                            </form>
                        </div>
                        
                        <!-- Sync from External Systems -->
                        <div style="padding: 1rem; background: #eff6ff; border: 1px solid #bfdbfe; margin-top: 1rem;">
                            <h4 style="margin: 0 0 0.5rem 0; font-size: 0.875rem; font-weight: 600;">
                                <i class="fas fa-sync"></i> Sync from External Systems
                            </h4>
                            <p style="font-size: 0.875rem; color: #6b7280; margin-bottom: 1rem;">
                                Pull learning records from LMS, recruitment, or HR systems. Integration configuration required.
                            </p>
                            <form method="POST" action="" style="display: inline-block;">
                                <?php echo CSRF::tokenField(); ?>
                                <input type="hidden" name="action" value="sync_learning">
                                <input type="hidden" name="person_id" value="<?php echo $person['id']; ?>">
                                <select name="source_system" style="font-size: 0.875rem; padding: 0.5rem; margin-right: 0.5rem;">
                                    <option value="lms">LMS (Learning Management System)</option>
                                    <option value="recruitment">Recruitment System</option>
                                    <option value="hr">HR System</option>
                                </select>
                                <button type="submit" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-sync"></i> Sync Now
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Signature -->
                <div id="section-signature" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                    <h2>Digital Signature</h2>
                    <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                        Manage the staff member's signature for use in forms across the organisation. They can upload an image of their signature or draw it digitally.
                    </p>
                    
                    <?php if ($person['signature_path']): ?>
                        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Current Signature:</p>
                            <img src="<?php echo url('view-image.php?path=' . urlencode('people/signatures/' . $person['signature_path'])); ?>" 
                                 alt="Signature" 
                                 style="max-width: 400px; max-height: 150px; border: 1px solid #e5e7eb; background: white; padding: 0.5rem;">
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">
                                Created: <?php echo $person['signature_created_at'] ? date('d/m/Y H:i', strtotime($person['signature_created_at'])) : 'Unknown'; ?>
                                (<?php echo $person['signature_method'] === 'upload' ? 'Uploaded' : 'Digitally drawn'; ?>)
                            </p>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Signature Upload/Drawing Interface -->
                    <div style="border: 1px solid #e5e7eb; padding: 1.5rem; background: white;">
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Method:</label>
                            <select id="signature-method-select-edit" style="width: 100%; padding: 0.5rem;">
                                <option value="digital">Draw Digitally</option>
                                <option value="upload">Upload Image</option>
                            </select>
                        </div>
                        
                        <!-- Digital Drawing -->
                        <div id="signature-drawing-section-edit">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Draw Signature:</label>
                            <div id="signature-pad-container-edit" style="margin-bottom: 1rem;"></div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" id="clear-signature-edit" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <!-- File Upload -->
                        <div id="signature-upload-section-edit" style="display: none;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Upload Signature Image:</label>
                            <input type="file" id="signature-file-input-edit" name="signature_file" accept="image/jpeg,image/png,image/jpg" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem;">
                            <small style="color: #6b7280;">Upload a JPEG or PNG image of the signature (max 2MB)</small>
                        </div>
                        
                        <form method="POST" action="" id="signature-form-edit" enctype="multipart/form-data" style="margin-top: 1.5rem;">
                            <?php echo CSRF::tokenField(); ?>
                            <input type="hidden" name="action" value="save_signature">
                            <input type="hidden" name="signature_method" id="signature-method-input-edit" value="digital">
                            <input type="hidden" name="signature_data" id="signature-data-input-edit">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Signature
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Organisational Units -->
                <div id="section-organisational-units" style="margin-top: 3rem; padding-top: 3rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
        <h2>Organisational Units</h2>
        
        <?php if (!empty($organisationalUnits)): ?>
            <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e5e7eb;">
                        <th style="padding: 0.75rem; text-align: left;">Unit</th>
                        <th style="padding: 0.75rem; text-align: left;">Role</th>
                        <th style="padding: 0.75rem; text-align: left;">Primary</th>
                        <th style="padding: 0.75rem; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($organisationalUnits as $unit): ?>
                        <tr style="border-bottom: 1px solid #e5e7eb;">
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($unit['unit_name']); ?></td>
                            <td style="padding: 0.75rem;"><?php echo htmlspecialchars($unit['role_in_unit']); ?></td>
                            <td style="padding: 0.75rem;">
                                <?php if ($unit['is_primary']): ?>
                                    <i class="fas fa-check" style="color: #10b981;"></i>
                                <?php else: ?>
                                    <i class="fas fa-times" style="color: #9ca3af;"></i>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 0.75rem; text-align: right;">
                                <form method="POST" action="" style="display: inline-block;">
                                    <?php echo CSRF::tokenField(); ?>
                                    <input type="hidden" name="action" value="remove_unit">
                                    <input type="hidden" name="organisational_unit_id" value="<?php echo $unit['organisational_unit_id']; ?>">
                                    <button type="submit" class="btn btn-secondary" style="padding: 0.375rem 0.75rem; font-size: 0.875rem;" onclick="return confirm('Are you sure you want to remove this organisational unit assignment?');">
                                        <i class="fas fa-times"></i> Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <form method="POST" action="" style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 1rem; align-items: end;">
            <?php echo CSRF::tokenField(); ?>
            <input type="hidden" name="action" value="assign_unit">
            <div class="form-group">
                <label for="organisational_unit_id">Organisational Unit</label>
                <select id="organisational_unit_id" name="organisational_unit_id" required>
                    <option value="">Select unit...</option>
                    <?php foreach ($allUnits as $unit): ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="role_in_unit">Role</label>
                <input type="text" id="role_in_unit" name="role_in_unit" value="member" placeholder="e.g. member, lead">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_primary" value="1">
                    Primary
                </label>
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add
            </button>
        </form>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill job title when job post is selected
document.getElementById('job_post_id').addEventListener('change', function() {
    const jobPostId = this.value;
    const jobTitleField = document.getElementById('job_title');
    
    if (jobPostId) {
        // Fetch job post details
        fetch('<?php echo url('api/job-post.php?id='); ?>' + jobPostId)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    if (!jobTitleField.value || confirm('Replace current job title with "' + (data.data.title || data.data.job_description_title) + '"?')) {
                        jobTitleField.value = data.data.title || data.data.job_description_title;
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching job post:', error);
            });
    }
});

// Show/hide TUPE fields
const tupeCheckbox = document.getElementById('is_tupe_checkbox');
if (tupeCheckbox) {
    tupeCheckbox.addEventListener('change', function() {
        const tupeFields = document.getElementById('tupe-fields');
        if (tupeFields) {
            tupeFields.style.display = this.checked ? 'block' : 'none';
        }
    });
}

// Show/hide WTD agreement fields
const wtdAgreedCheckbox = document.getElementById('wtd_agreed_checkbox');
if (wtdAgreedCheckbox) {
    wtdAgreedCheckbox.addEventListener('change', function() {
        const wtdFields = document.getElementById('wtd-agreement-fields');
        if (wtdFields) {
            wtdFields.style.display = this.checked ? 'block' : 'none';
        }
    });
}

// Show/hide WTD opt-out fields
const wtdOptOutCheckbox = document.getElementById('wtd_opt_out_checkbox');
if (wtdOptOutCheckbox) {
    wtdOptOutCheckbox.addEventListener('change', function() {
        const optOutFields = document.getElementById('wtd-opt-out-fields');
        if (optOutFields) {
            optOutFields.style.display = this.checked ? 'block' : 'none';
        }
    });
}

// Sidebar navigation active section highlighting
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('[id^="section-"]');
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    
    function updateActiveSection() {
        let current = '';
        const scrollPosition = window.scrollY + 150;
        
        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            const sectionHeight = section.clientHeight;
            if (scrollPosition >= sectionTop && scrollPosition < sectionTop + sectionHeight) {
                current = section.getAttribute('id');
            }
        });
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }
    
    window.addEventListener('scroll', updateActiveSection);
    updateActiveSection();
});

// Signature pad functionality for edit page
document.addEventListener('DOMContentLoaded', function() {
    const signatureMethodSelect = document.getElementById('signature-method-select-edit');
    const drawingSection = document.getElementById('signature-drawing-section-edit');
    const uploadSection = document.getElementById('signature-upload-section-edit');
    const signatureMethodInput = document.getElementById('signature-method-input-edit');
    const signatureForm = document.getElementById('signature-form-edit');
    const signatureDataInput = document.getElementById('signature-data-input-edit');
    const signatureFileInput = document.getElementById('signature-file-input-edit');
    let signaturePadEdit = null;
    
    if (signatureMethodSelect) {
        // Load signature pad script
        const script = document.createElement('script');
        script.src = '<?php echo url("assets/js/signature-pad.js"); ?>';
        script.onload = function() {
            // Initialize signature pad
            try {
                signaturePadEdit = new SignaturePad('signature-pad-container-edit', {
                    width: 600,
                    height: 200,
                    backgroundColor: '#ffffff',
                    penColor: '#000000'
                });
                
                // Clear button
                const clearBtn = document.getElementById('clear-signature-edit');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        signaturePadEdit.clear();
                    });
                }
            } catch (e) {
                console.error('Error initializing signature pad:', e);
            }
        };
        document.head.appendChild(script);
        
        // Toggle between upload and drawing
        signatureMethodSelect.addEventListener('change', function() {
            if (this.value === 'upload') {
                drawingSection.style.display = 'none';
                uploadSection.style.display = 'block';
                signatureMethodInput.value = 'upload';
            } else {
                drawingSection.style.display = 'block';
                uploadSection.style.display = 'none';
                signatureMethodInput.value = 'digital';
            }
        });
        
        // Handle form submission
        if (signatureForm) {
            signatureForm.addEventListener('submit', function(e) {
                if (signatureMethodInput.value === 'digital') {
                    // Get signature from pad
                    if (signaturePadEdit && !signaturePadEdit.isEmpty()) {
                        signatureDataInput.value = signaturePadEdit.getSignature();
                    } else {
                        e.preventDefault();
                        alert('Please draw the signature or upload an image.');
                        return false;
                    }
                } else {
                    // Check if file is selected
                    if (!signatureFileInput.files || signatureFileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Please select a signature image to upload.');
                        return false;
                    }
                }
            });
        }
    }
});
</script>

<style>
/* Smooth scrolling for anchor links */
html {
    scroll-behavior: smooth;
}

/* Documentation-style sidebar navigation */
.sidebar-nav {
    display: flex;
    flex-direction: column;
    text-align: left;
    align-items: stretch;
}

.sidebar-nav-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 1.5rem;
    color: #374151;
    text-decoration: none;
    font-size: 0.875rem;
    font-weight: 400;
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
    position: relative;
    text-align: left;
    justify-content: flex-start;
}

.sidebar-nav-link i {
    width: 18px;
    text-align: center;
    font-size: 0.875rem;
    color: #6b7280;
    transition: color 0.15s ease;
}

.sidebar-nav-link span {
    flex: 1;
}

.sidebar-nav-link:hover {
    background-color: #f9fafb;
    color: #111827;
    border-left-color: #e5e7eb;
}

.sidebar-nav-link:hover i {
    color: #374151;
}

/* Active section highlighting - docs style with blue background and white text */
.sidebar-nav-link.active,
.sidebar-nav a.active {
    background-color: #2563eb !important;
    color: white !important;
    font-weight: 500;
    border-left-color: #2563eb;
}

.sidebar-nav-link.active i,
.sidebar-nav a.active i {
    color: white !important;
}

/* Sticky save button - positioned below header */
.sticky-save-button {
    position: sticky !important;
    top: 70px !important;
    z-index: 999 !important;
}

/* Responsive: Hide sidebar on mobile */
@media (max-width: 968px) {
    .profile-form-container {
        grid-template-columns: 1fr !important;
    }
    
    .profile-sidebar {
        display: none;
    }
}
</style>

<?php include dirname(__DIR__, 2) . '/includes/footer.php'; ?>

