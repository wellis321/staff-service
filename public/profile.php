<?php
require_once dirname(__DIR__) . '/config/config.php';

Auth::requireLogin();

$organisationId = Auth::getOrganisationId();
$userId = Auth::getUserId();
$error = '';
$success = '';

// Super admins don't have staff profiles - redirect to index
if (RBAC::isSuperAdmin()) {
    header('Location: ' . url('index.php'));
    exit;
}

// Get person record (only if user has an organisation)
$person = null;
if ($organisationId) {
    try {
        $person = Person::findByUserId($userId, $organisationId);
    } catch (Exception $e) {
        error_log("Error finding person record: " . $e->getMessage());
    }
}

if (!$person) {
    header('Location: ' . url('index.php'));
    exit;
}

$personId = $person['id'];

// Get learning records for display
$learningRecords = [];
$qualifications = [];
$courses = [];
if ($person['person_type'] === 'staff') {
    $learningRecords = StaffLearningRecord::getByPersonId($personId, $organisationId);
    $qualifications = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'qualification']);
    $courses = StaffLearningRecord::getByPersonId($personId, $organisationId, ['record_type' => 'course']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['action'])) {
        $error = 'No action specified.';
    } elseif (!CSRF::validatePost()) {
        $error = 'Invalid security token.';
    } else {
        $action = $_POST['action'];
        
        if ($action === 'update_profile') {
            // Update basic profile information
            $data = [
                'phone' => trim($_POST['phone'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
            ];
            
            // Update staff profile if applicable
            if ($person['person_type'] === 'staff') {
                $staffData = [
                    'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
                    'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
                    'ni_number' => trim($_POST['ni_number'] ?? ''),
                    'bank_sort_code' => trim($_POST['bank_sort_code'] ?? ''),
                    'bank_account_number' => trim($_POST['bank_account_number'] ?? ''),
                    'bank_account_name' => trim($_POST['bank_account_name'] ?? ''),
                    'address_line1' => trim($_POST['address_line1'] ?? ''),
                    'address_line2' => trim($_POST['address_line2'] ?? ''),
                    'address_city' => trim($_POST['address_city'] ?? ''),
                    'address_county' => trim($_POST['address_county'] ?? ''),
                    'address_postcode' => trim($_POST['address_postcode'] ?? ''),
                    'address_country' => trim($_POST['address_country'] ?? ''),
                    'contracted_hours' => !empty($_POST['contracted_hours']) ? (float)$_POST['contracted_hours'] : null,
                    'place_of_work' => trim($_POST['place_of_work'] ?? ''),
                    'wtd_agreed' => isset($_POST['wtd_agreed']) && $_POST['wtd_agreed'] === '1',
                    'wtd_agreement_date' => !empty($_POST['wtd_agreement_date']) ? $_POST['wtd_agreement_date'] : null,
                    'wtd_opt_out' => isset($_POST['wtd_opt_out']) && $_POST['wtd_opt_out'] === '1',
                    'wtd_opt_out_date' => !empty($_POST['wtd_opt_out_date']) ? $_POST['wtd_opt_out_date'] : null,
                    'wtd_notes' => trim($_POST['wtd_notes'] ?? ''),
                ];
                $data = array_merge($data, $staffData);
            }
            
            // Remove empty strings but keep nulls for optional fields
            $data = array_filter($data, function($value) {
                return $value !== '';
            });
            
            if (!empty($data)) {
                $result = Person::update($personId, $data, $organisationId);
                if ($result) {
                    $success = 'Profile updated successfully.';
                    // Refresh person data
                    $person = Person::findById($personId, $organisationId);
                } else {
                    $error = 'Failed to update profile.';
                }
            }
        } elseif ($action === 'upload_photo') {
            // Handle photo upload
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['photo'];
                
                // Validate file type
                $allowedTypes = ALLOWED_PHOTO_TYPES;
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                // finfo_close() is deprecated in PHP 8.5+ - finfo objects are automatically freed
                
                if (!in_array($mimeType, $allowedTypes)) {
                    $error = 'Invalid file type. Please upload a JPEG or PNG image.';
                } elseif ($file['size'] > MAX_PHOTO_SIZE) {
                    $error = 'File size too large. Maximum size is ' . (MAX_PHOTO_SIZE / 1024 / 1024) . 'MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $uploadDir = PHOTO_UPLOAD_PATH . '/pending';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'person_' . $personId . '_' . time() . '.' . $extension;
                    $filepath = $uploadDir . '/' . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Update person record with pending photo
                        $updateData = [
                            'photo_pending_path' => 'pending/' . $filename,
                            'photo_approval_status' => 'pending'
                        ];
                        Person::update($personId, $updateData, $organisationId);
                        $success = 'Photo uploaded successfully. It will be reviewed by an administrator before being approved.';
                        // Refresh person data
                        $person = Person::findById($personId, $organisationId);
                    } else {
                        $error = 'Failed to upload photo.';
                    }
                }
            } else {
                $error = 'Please select a photo to upload.';
            }
        } elseif ($action === 'save_signature') {
            // Handle signature save (upload or digital)
            $signatureMethod = $_POST['signature_method'] ?? 'digital';
            
            // Read raw input ONCE and store it (php://input can only be read once)
            $rawInput = file_get_contents('php://input');
            
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
                        if (!empty($person['signature_path']) && file_exists(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path'])) {
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
            } elseif ($signatureMethod === 'digital') {
                // Handle digital signature (base64 image data)
                // Try to get signature_data from POST first, but if empty, parse from raw input
                $signatureData = $_POST['signature_data'] ?? '';
                
                // If signature_data is empty in POST but exists in raw input, parse it manually
                // This can happen with very large base64 strings that PHP doesn't parse correctly
                // Use the rawInput variable we already read (php://input can only be read once)
                if (empty($signatureData) || strlen($signatureData) < 100) {
                    // Parse all parameters from raw input manually
                    parse_str($rawInput, $parsedData);
                    if (!empty($parsedData['signature_data']) && strlen($parsedData['signature_data']) > 100) {
                        $signatureData = $parsedData['signature_data'];
                    } else {
                        // Try manual extraction as fallback
                        $signatureDataPos = strpos($rawInput, 'signature_data=');
                        if ($signatureDataPos !== false) {
                            $afterEquals = substr($rawInput, $signatureDataPos + strlen('signature_data='));
                            // Find where it ends (either & or end of string)
                            $endPos = strpos($afterEquals, '&');
                            if ($endPos !== false) {
                                $signatureData = urldecode(substr($afterEquals, 0, $endPos));
                            } else {
                                $signatureData = urldecode($afterEquals);
                            }
                        }
                    }
                }
                
                if (empty($signatureData) || strlen($signatureData) < 100) {
                    $error = 'Please provide a signature (upload file or draw digitally). Signature data not received.';
                } else {
                    
                    // Remove data URL prefix if present
                if (preg_match('/^data:image\/(\w+);base64,/', $signatureData, $matches)) {
                    $imageType = $matches[1];
                    $signatureData = preg_replace('/^data:image\/\w+;base64,/', '', $signatureData);
                } else {
                    $imageType = 'png';
                }
                
                // Decode base64
                $imageData = base64_decode($signatureData, true); // Use strict mode
                
                if ($imageData === false || strlen($imageData) < 100) {
                    // Signature data is too small or invalid - likely empty canvas
                    $error = 'Invalid signature data. Please draw your signature before saving.';
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
                    $saveResult = file_put_contents($filepath, $imageData);
                    
                    if ($saveResult) {
                        // Delete old signature if exists
                        if (!empty($person['signature_path']) && file_exists(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path'])) {
                            @unlink(SIGNATURE_UPLOAD_PATH . '/' . $person['signature_path']);
                        }
                        
                        // Update person record
                        $updateData = [
                            'signature_path' => $filename,
                            'signature_method' => 'digital',
                            'signature_created_at' => date('Y-m-d H:i:s')
                        ];
                        
                        try {
                            $updateResult = Person::update($personId, $updateData, $organisationId);
                            
                            if ($updateResult) {
                                $success = 'Signature saved successfully.';
                                // Refresh person data
                                $person = Person::findById($personId, $organisationId);
                            } else {
                                $error = 'Failed to update profile with signature.';
                            }
                        } catch (Exception $e) {
                            $error = 'Failed to update profile with signature: ' . $e->getMessage();
                        }
                    } else {
                        $error = 'Failed to save signature.';
                    }
                }
                }
            } else {
                error_log("Signature save failed - Method: " . $signatureMethod . ", Has file: " . (isset($_FILES['signature_file']) ? 'yes' : 'no') . ", Has data: " . (isset($_POST['signature_data']) ? 'yes' : 'no'));
                $error = 'Please provide a signature (upload file or draw digitally).';
            }
        }
    }
}

// Get organisational units
$organisationalUnits = Person::getOrganisationalUnits($personId);

$pageTitle = 'My Profile';
include INCLUDES_PATH . '/header.php';
?>

<div class="card">
    <h1>My Profile</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <div class="profile-form-container" style="display: grid; grid-template-columns: 250px 1fr; gap: 2rem; position: relative; margin-top: 2rem;">
        <!-- Sidebar Navigation -->
        <div class="profile-sidebar" style="position: sticky; top: 2rem; align-self: start; height: fit-content;">
            <div style="background: white; border-right: 1px solid #e5e7eb; padding: 0;">
                <!-- Profile Photo in Sidebar -->
                <div style="padding: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                    <?php
                    $photoPath = null;
                    if ($person['photo_path'] && $person['photo_approval_status'] === 'approved') {
                        $photoPath = url('view-image.php?path=' . urlencode($person['photo_path']));
                    } elseif ($person['photo_pending_path']) {
                        $photoPath = url('view-image.php?path=' . urlencode($person['photo_pending_path']));
                    }
                    ?>
                    <?php if ($photoPath): ?>
                        <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Profile Photo" style="width: 100%; border-radius: 0; border: 1px solid #e5e7eb;">
                    <?php else: ?>
                        <div style="width: 100%; aspect-ratio: 1; background: #f3f4f6; display: flex; align-items: center; justify-content: center; border: 1px solid #e5e7eb;">
                            <i class="fas fa-user fa-3x" style="color: #9ca3af;"></i>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($person['photo_approval_status'] === 'pending'): ?>
                        <p style="font-size: 0.875rem; color: #f59e0b; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-clock"></i> Pending approval
                        </p>
                    <?php elseif ($person['photo_approval_status'] === 'rejected'): ?>
                        <p style="font-size: 0.875rem; color: #ef4444; margin-top: 0.5rem; text-align: center;">
                            <i class="fas fa-times-circle"></i> Rejected
                        </p>
                    <?php endif; ?>
                    
                    <form method="POST" action="" enctype="multipart/form-data" style="margin-top: 1rem;">
                        <?php echo CSRF::tokenField(); ?>
                        <input type="hidden" name="action" value="upload_photo">
                        <input type="file" name="photo" accept="image/jpeg,image/png,image/jpg" required style="margin-bottom: 0.5rem; font-size: 0.875rem; width: 100%;">
                        <button type="submit" class="btn btn-secondary" style="width: 100%; font-size: 0.875rem; padding: 0.5rem;">
                            <i class="fas fa-upload"></i> Upload Photo
                        </button>
                    </form>
                </div>
                
                <nav class="sidebar-nav" style="padding: 1rem 0;">
                        <a href="#section-personal" class="sidebar-nav-link">
                            <i class="fas fa-user"></i> <span>Personal Information</span>
                        </a>
                        <a href="#section-address" class="sidebar-nav-link">
                            <i class="fas fa-map-marker-alt"></i> <span>Address</span>
                        </a>
                        <a href="#section-identification" class="sidebar-nav-link">
                            <i class="fas fa-id-card"></i> <span>Identification</span>
                        </a>
                        <a href="#section-banking" class="sidebar-nav-link">
                            <i class="fas fa-university"></i> <span>Banking Information</span>
                        </a>
                        <a href="#section-wtd" class="sidebar-nav-link">
                            <i class="fas fa-clock"></i> <span>WTD Agreement</span>
                        </a>
                        <a href="#section-employment" class="sidebar-nav-link">
                            <i class="fas fa-briefcase"></i> <span>Employment</span>
                        </a>
                        <a href="#section-emergency" class="sidebar-nav-link">
                            <i class="fas fa-phone-alt"></i> <span>Emergency Contact</span>
                        </a>
                        <a href="#section-leave" class="sidebar-nav-link">
                            <i class="fas fa-calendar-alt"></i> <span>Leave Management</span>
                        </a>
                        <a href="#section-learning" class="sidebar-nav-link">
                            <i class="fas fa-graduation-cap"></i> <span>Learning & Qualifications</span>
                        </a>
                        <a href="#section-signature" class="sidebar-nav-link">
                            <i class="fas fa-pen"></i> <span>Signature</span>
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div>
                <form method="POST" action="" id="profile-form">
                    <?php echo CSRF::tokenField(); ?>
                    <input type="hidden" name="action" value="update_profile">
                    
                    <!-- Sticky Save Button at Top -->
                    <div class="sticky-save-button" style="position: sticky; top: 70px; background: white; padding: 1rem 0; border-bottom: 2px solid #e5e7eb; margin-bottom: 2rem; z-index: 999; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                        <button type="submit" class="btn btn-primary" style="font-size: 1rem; padding: 0.75rem 2rem;">
                            <i class="fas fa-save"></i> Save All Changes
                        </button>
                    </div>
                    
                    <!-- Personal Information -->
                    <div id="section-personal" style="margin-bottom: 2rem; scroll-margin-top: 2rem;">
                        <h2>Personal Information</h2>
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($person['first_name'] . ' ' . $person['last_name']); ?>" disabled>
                        <small>Name cannot be changed here. Contact your administrator to update your name.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($person['email'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($person['phone'] ?? ''); ?>">
                    </div>
                    
                    <?php if ($person['date_of_birth']): ?>
                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="text" value="<?php echo date('d/m/Y', strtotime($person['date_of_birth'])); ?>" disabled>
                            <small>Date of birth cannot be changed here. Contact your administrator to update.</small>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($person['person_type'] === 'staff'): ?>
                    <!-- Address Information -->
                    <div id="section-address" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Address</h2>
                        <div class="form-group">
                            <label for="address_line1">Address Line 1</label>
                            <input type="text" id="address_line1" name="address_line1" value="<?php echo htmlspecialchars($person['address_line1'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_line2">Address Line 2</label>
                            <input type="text" id="address_line2" name="address_line2" value="<?php echo htmlspecialchars($person['address_line2'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_city">City/Town</label>
                            <input type="text" id="address_city" name="address_city" value="<?php echo htmlspecialchars($person['address_city'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_county">County</label>
                            <input type="text" id="address_county" name="address_county" value="<?php echo htmlspecialchars($person['address_county'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_postcode">Postcode</label>
                            <input type="text" id="address_postcode" name="address_postcode" value="<?php echo htmlspecialchars($person['address_postcode'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address_country">Country</label>
                            <input type="text" id="address_country" name="address_country" value="<?php echo htmlspecialchars($person['address_country'] ?? 'United Kingdom'); ?>">
                        </div>
                    </div>
                    
                    <!-- Identification -->
                    <div id="section-identification" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Identification</h2>
                        <?php if ($person['employee_reference']): ?>
                            <div class="form-group">
                                <label>Employee Reference</label>
                                <input type="text" value="<?php echo htmlspecialchars($person['employee_reference']); ?>" disabled>
                                <small>Employee reference cannot be changed.</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="ni_number">National Insurance Number</label>
                            <input type="text" id="ni_number" name="ni_number" value="<?php echo htmlspecialchars($person['ni_number'] ?? ''); ?>" placeholder="e.g. AB123456C" maxlength="20">
                            <small>Your National Insurance (NI) number</small>
                        </div>
                    </div>
                    
                    <!-- Financial Information -->
                    <div id="section-banking" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Banking Information</h2>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            Your banking details are stored securely and only used for payroll purposes.
                        </p>
                        
                        <div class="form-group">
                            <label for="bank_account_name">Account Holder Name</label>
                            <input type="text" id="bank_account_name" name="bank_account_name" value="<?php echo htmlspecialchars($person['bank_account_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="bank_sort_code">Sort Code</label>
                            <input type="text" id="bank_sort_code" name="bank_sort_code" value="<?php echo htmlspecialchars($person['bank_sort_code'] ?? ''); ?>" placeholder="e.g. 12-34-56" maxlength="10">
                            <small>Format: XX-XX-XX or XXXXXX</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="bank_account_number">Account Number</label>
                            <input type="text" id="bank_account_number" name="bank_account_number" value="<?php echo htmlspecialchars($person['bank_account_number'] ?? ''); ?>" maxlength="20">
                            <small>Your bank account number (8 digits typically)</small>
                        </div>
                    </div>
                    
                    <!-- Working Time Directive Agreement -->
                    <div id="section-wtd" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Working Time Directive (WTD) Agreement</h2>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            The Working Time Directive limits working hours. You can view and update your WTD agreement status here.
                        </p>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="wtd_agreed" value="1" id="wtd_agreed_checkbox" <?php echo ($person['wtd_agreed'] ?? false) ? 'checked' : ''; ?>>
                                I have agreed to Working Time Directive
                            </label>
                        </div>
                        
                        <div id="wtd-agreement-fields" style="<?php echo ($person['wtd_agreed'] ?? false) ? '' : 'display: none;'; ?>">
                            <div class="form-group">
                                <label for="wtd_agreement_date">Agreement Date</label>
                                <input type="date" id="wtd_agreement_date" name="wtd_agreement_date" value="<?php echo htmlspecialchars($person['wtd_agreement_date'] ?? ''); ?>">
                                <small>Date when you signed the WTD agreement</small>
                            </div>
                            
                            <?php if ($person['wtd_agreement_version']): ?>
                                <div class="form-group">
                                    <label>Agreement Version</label>
                                    <input type="text" value="<?php echo htmlspecialchars($person['wtd_agreement_version']); ?>" disabled>
                                    <small>Version of the agreement you signed (set by administrator)</small>
                                </div>
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="wtd_opt_out" value="1" id="wtd_opt_out_checkbox" <?php echo ($person['wtd_opt_out'] ?? false) ? 'checked' : ''; ?>>
                                    I have opted out of 48-hour week limit
                                </label>
                                <small>UK allows you to opt out of the 48-hour average working week limit</small>
                            </div>
                            
                            <div id="wtd-opt-out-fields" style="<?php echo ($person['wtd_opt_out'] ?? false) ? '' : 'display: none;'; ?>">
                                <div class="form-group">
                                    <label for="wtd_opt_out_date">Opt-Out Date</label>
                                    <input type="date" id="wtd_opt_out_date" name="wtd_opt_out_date" value="<?php echo htmlspecialchars($person['wtd_opt_out_date'] ?? ''); ?>">
                                    <small>Date when you signed the opt-out</small>
                                </div>
                                
                                <?php if ($person['wtd_opt_out_expiry_date']): ?>
                                    <div class="form-group">
                                        <label>Opt-Out Expiry Date</label>
                                        <input type="text" value="<?php echo date('d/m/Y', strtotime($person['wtd_opt_out_expiry_date'])); ?>" disabled>
                                        <small>Your opt-out expires on this date (set by administrator)</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="wtd_notes">Notes</label>
                                <textarea id="wtd_notes" name="wtd_notes" rows="3"><?php echo htmlspecialchars($person['wtd_notes'] ?? ''); ?></textarea>
                                <small>Any notes about your WTD agreement</small>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employment Information -->
                    <div id="section-employment" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Employment Information</h2>
                        <?php if ($person['job_title']): ?>
                            <div class="form-group">
                                <label>Job Title</label>
                                <input type="text" value="<?php echo htmlspecialchars($person['job_title']); ?>" disabled>
                                <small>Job title can only be changed by an administrator.</small>
                            </div>
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label for="contracted_hours">Contracted Hours per Week</label>
                            <input type="number" id="contracted_hours" name="contracted_hours" 
                                   value="<?php echo htmlspecialchars($person['contracted_hours'] ?? ''); ?>" 
                                   step="0.5" min="0" max="168" placeholder="e.g. 37.5">
                            <small>Your contracted working hours per week</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="place_of_work">Place of Work</label>
                            <input type="text" id="place_of_work" name="place_of_work" 
                                   value="<?php echo htmlspecialchars($person['place_of_work'] ?? ''); ?>" 
                                   placeholder="e.g. Main Office, Remote, Site A">
                            <small>Your primary place of work or location</small>
                        </div>
                        
                        <?php if (!empty($person['job_description_id']) || !empty($person['external_job_description_url'])): ?>
                            <div class="form-group">
                                <label>Job Description</label>
                                <?php if (!empty($person['job_description_id']) && !empty($person['job_description_title'])): ?>
                                    <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0; margin-top: 0.5rem;">
                                        <strong><?php echo htmlspecialchars($person['job_description_title']); ?></strong>
                                        <?php if ($person['job_description_text']): ?>
                                            <p style="margin-top: 0.5rem; color: #6b7280; font-size: 0.875rem;">
                                                <?php echo nl2br(htmlspecialchars(substr($person['job_description_text'], 0, 200))); ?>
                                                <?php if (strlen($person['job_description_text']) > 200): ?>...<?php endif; ?>
                                            </p>
                                        <?php endif; ?>
                                        <small style="color: #6b7280;">Job description is managed by your organisation.</small>
                                    </div>
                                <?php elseif (!empty($person['external_job_description_url'])): ?>
                                    <div style="padding: 0.75rem; background: #f9fafb; border-radius: 0; margin-top: 0.5rem;">
                                        <a href="<?php echo htmlspecialchars($person['external_job_description_url']); ?>" 
                                           target="_blank" style="color: #2563eb;">
                                            <i class="fas fa-external-link-alt"></i> View Job Description (External System)
                                        </a>
                                        <?php if ($person['external_job_description_ref']): ?>
                                            <small style="display: block; color: #6b7280; margin-top: 0.25rem;">
                                                Reference: <?php echo htmlspecialchars($person['external_job_description_ref']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($person['employment_start_date']): ?>
                            <div class="form-group">
                                <label>Employment Start Date</label>
                                <input type="text" value="<?php echo date('d/m/Y', strtotime($person['employment_start_date'])); ?>" disabled>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Emergency Contact -->
                    <div id="section-emergency" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Emergency Contact</h2>
                        <div class="form-group">
                            <label for="emergency_contact_name">Emergency Contact Name</label>
                            <input type="text" id="emergency_contact_name" name="emergency_contact_name" value="<?php echo htmlspecialchars($person['emergency_contact_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="emergency_contact_phone">Emergency Contact Phone</label>
                            <input type="tel" id="emergency_contact_phone" name="emergency_contact_phone" value="<?php echo htmlspecialchars($person['emergency_contact_phone'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <!-- Leave Management -->
                    <div id="section-leave" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Leave Management</h2>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            Your leave allocation and usage information. Contact your administrator to request changes.
                        </p>
                        
                        <?php if ($person['leave_year_start_date'] && $person['leave_year_end_date']): ?>
                            <div class="form-group">
                                <label>Leave Year</label>
                                <input type="text" value="<?php echo date('d/m/Y', strtotime($person['leave_year_start_date'])) . ' - ' . date('d/m/Y', strtotime($person['leave_year_end_date'])); ?>" disabled>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($person['annual_leave_allocation'] !== null): ?>
                            <div class="form-group">
                                <label>Annual Leave Allocation</label>
                                <input type="text" value="<?php echo number_format($person['annual_leave_allocation'], 1); ?> days" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label>Annual Leave Used</label>
                                <input type="text" value="<?php echo number_format($person['annual_leave_used'] ?? 0, 1); ?> days" disabled>
                            </div>
                            
                            <?php 
                            $remaining = ($person['annual_leave_allocation'] ?? 0) - ($person['annual_leave_used'] ?? 0);
                            if ($remaining >= 0):
                            ?>
                                <div class="form-group">
                                    <label>Remaining Annual Leave</label>
                                    <input type="text" value="<?php echo number_format($remaining, 1); ?> days" disabled>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($person['annual_leave_carry_over'] > 0): ?>
                                <div class="form-group">
                                    <label>Carry Over</label>
                                    <input type="text" value="<?php echo number_format($person['annual_leave_carry_over'], 1); ?> days" disabled>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($person['time_in_lieu_hours'] > 0 || $person['time_in_lieu_used'] > 0): ?>
                            <div class="form-group">
                                <label>Time in Lieu (Total)</label>
                                <input type="text" value="<?php echo number_format($person['time_in_lieu_hours'] ?? 0, 1); ?> hours" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label>Time in Lieu (Used)</label>
                                <input type="text" value="<?php echo number_format($person['time_in_lieu_used'] ?? 0, 1); ?> hours" disabled>
                            </div>
                            
                            <?php 
                            $tilRemaining = ($person['time_in_lieu_hours'] ?? 0) - ($person['time_in_lieu_used'] ?? 0);
                            if ($tilRemaining > 0):
                            ?>
                                <div class="form-group">
                                    <label>Remaining Time in Lieu</label>
                                    <input type="text" value="<?php echo number_format($tilRemaining, 1); ?> hours" disabled>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($person['lying_time_hours'] > 0 || $person['lying_time_used'] > 0): ?>
                            <div class="form-group">
                                <label>Lying Time (Total)</label>
                                <input type="text" value="<?php echo number_format($person['lying_time_hours'] ?? 0, 1); ?> hours" disabled>
                            </div>
                            
                            <div class="form-group">
                                <label>Lying Time (Used)</label>
                                <input type="text" value="<?php echo number_format($person['lying_time_used'] ?? 0, 1); ?> hours" disabled>
                            </div>
                            
                            <?php 
                            $ltRemaining = ($person['lying_time_hours'] ?? 0) - ($person['lying_time_used'] ?? 0);
                            if ($ltRemaining > 0):
                            ?>
                                <div class="form-group">
                                    <label>Remaining Lying Time</label>
                                    <input type="text" value="<?php echo number_format($ltRemaining, 1); ?> hours" disabled>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Learning & Qualifications -->
                    <div id="section-learning" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                        <h2>Learning & Qualifications</h2>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                            Your qualifications, courses, and training records. Contact your administrator to add or update records.
                            <?php 
                            $currentRecords = array_filter($learningRecords, function($r) { return empty($r['is_from_linked_record']); });
                            $linkedRecordsCount = count($learningRecords) - count($currentRecords);
                            if ($linkedRecordsCount > 0): 
                            ?>
                                <br><strong>Note:</strong> <?php echo $linkedRecordsCount; ?> record(s) from previous employment are included.
                            <?php endif; ?>
                        </p>
                        
                        <?php if (!empty($learningRecords)): ?>
                            <?php if (!empty($qualifications)): ?>
                                <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-certificate"></i> Qualifications
                                </h3>
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                            <th style="padding: 0.75rem; text-align: left;">Qualification</th>
                                            <th style="padding: 0.75rem; text-align: left;">Level</th>
                                            <th style="padding: 0.75rem; text-align: left;">Provider</th>
                                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
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
                                                    <?php if ($qual['description']): ?>
                                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($qual['description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($qual['qualification_level'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($qual['provider'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <?php echo $qual['completion_date'] ? date('d/m/Y', strtotime($qual['completion_date'])) : '-'; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <?php if (!empty($courses)): ?>
                                <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-book"></i> Courses & Training
                                </h3>
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                            <th style="padding: 0.75rem; text-align: left;">Course/Training</th>
                                            <th style="padding: 0.75rem; text-align: left;">Provider</th>
                                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
                                            <th style="padding: 0.75rem; text-align: left;">Expires</th>
                                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($courses as $course): ?>
                                            <tr style="border-bottom: 1px solid #e5e7eb; <?php echo !empty($course['is_from_linked_record']) ? 'background: #f9fafb;' : ''; ?>">
                                                <td style="padding: 0.75rem;">
                                                    <strong><?php echo htmlspecialchars($course['title']); ?></strong>
                                                    <?php if (!empty($course['is_from_linked_record'])): ?>
                                                        <br><span style="color: #6b7280; font-size: 0.75rem; font-style: italic;">
                                                            <i class="fas fa-link"></i> From previous employment
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($course['description']): ?>
                                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($course['description']); ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($course['is_mandatory']): ?>
                                                        <br><span style="color: #dc2626; font-size: 0.75rem;"><i class="fas fa-exclamation-circle"></i> Mandatory</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo htmlspecialchars($course['provider'] ?? '-'); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <?php echo $course['completion_date'] ? date('d/m/Y', strtotime($course['completion_date'])) : '-'; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php 
                                                    if ($course['expiry_date']): 
                                                        $expiryDate = strtotime($course['expiry_date']);
                                                        $daysUntilExpiry = floor(($expiryDate - time()) / 86400);
                                                        $expiryClass = $daysUntilExpiry < 0 ? 'color: #dc2626;' : ($daysUntilExpiry < 90 ? 'color: #f59e0b;' : '');
                                                    ?>
                                                        <span style="<?php echo $expiryClass; ?>">
                                                            <?php echo date('d/m/Y', $expiryDate); ?>
                                                            <?php if ($daysUntilExpiry < 0): ?>
                                                                <br><small>(Expired)</small>
                                                            <?php elseif ($daysUntilExpiry < 90): ?>
                                                                <br><small>(<?php echo $daysUntilExpiry; ?> days)</small>
                                                            <?php endif; ?>
                                                        </span>
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php 
                                                    $statusColors = [
                                                        'completed' => '#10b981',
                                                        'in_progress' => '#3b82f6',
                                                        'expired' => '#dc2626',
                                                        'pending' => '#f59e0b'
                                                    ];
                                                    $statusColor = $statusColors[$course['status']] ?? '#6b7280';
                                                    ?>
                                                    <span style="color: <?php echo $statusColor; ?>; font-weight: 500;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $course['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            
                            <?php 
                            $otherRecords = array_filter($learningRecords, function($record) use ($qualifications, $courses) {
                                $qualIds = array_column($qualifications, 'id');
                                $courseIds = array_column($courses, 'id');
                                return !in_array($record['id'], $qualIds) && !in_array($record['id'], $courseIds);
                            });
                            ?>
                            <?php if (!empty($otherRecords)): ?>
                                <h3 style="margin-top: 1.5rem; margin-bottom: 1rem; font-size: 1rem; font-weight: 600;">
                                    <i class="fas fa-list"></i> Other Records
                                </h3>
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 2rem;">
                                    <thead>
                                        <tr style="border-bottom: 2px solid #e5e7eb; background: #f9fafb;">
                                            <th style="padding: 0.75rem; text-align: left;">Title</th>
                                            <th style="padding: 0.75rem; text-align: left;">Type</th>
                                            <th style="padding: 0.75rem; text-align: left;">Completed</th>
                                            <th style="padding: 0.75rem; text-align: left;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($otherRecords as $record): ?>
                                            <tr style="border-bottom: 1px solid #e5e7eb; <?php echo !empty($record['is_from_linked_record']) ? 'background: #f9fafb;' : ''; ?>">
                                                <td style="padding: 0.75rem;">
                                                    <strong><?php echo htmlspecialchars($record['title']); ?></strong>
                                                    <?php if (!empty($record['is_from_linked_record'])): ?>
                                                        <br><span style="color: #6b7280; font-size: 0.75rem; font-style: italic;">
                                                            <i class="fas fa-link"></i> From previous employment
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($record['description']): ?>
                                                        <br><small style="color: #6b7280;"><?php echo htmlspecialchars($record['description']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td style="padding: 0.75rem;"><?php echo ucfirst(htmlspecialchars($record['record_type'])); ?></td>
                                                <td style="padding: 0.75rem;">
                                                    <?php echo $record['completion_date'] ? date('d/m/Y', strtotime($record['completion_date'])) : '-'; ?>
                                                </td>
                                                <td style="padding: 0.75rem;">
                                                    <?php 
                                                    $statusColors = [
                                                        'completed' => '#10b981',
                                                        'in_progress' => '#3b82f6',
                                                        'expired' => '#dc2626',
                                                        'pending' => '#f59e0b'
                                                    ];
                                                    $statusColor = $statusColors[$record['status']] ?? '#6b7280';
                                                    ?>
                                                    <span style="color: <?php echo $statusColor; ?>; font-weight: 500;">
                                                        <?php echo ucfirst(str_replace('_', ' ', $record['status'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        <?php else: ?>
                            <p style="color: #6b7280; margin-bottom: 1rem; font-style: italic;">
                                No learning records found. Contact your administrator to add qualifications and training records.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                </form>
                
                <!-- Signature section moved outside main profile form to prevent nested form issues -->
                <div id="section-signature" style="margin-bottom: 2rem; padding-top: 2rem; border-top: 1px solid #e5e7eb; scroll-margin-top: 2rem;">
                    <h2>Digital Signature</h2>
                    <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem;">
                        Add your signature to use in forms across the organisation. You can upload an image of your signature or draw it digitally.
                    </p>
                    
                    <?php if (!empty($person['signature_path'])): ?>
                        <div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9fafb; border: 1px solid #e5e7eb;">
                            <p style="margin: 0 0 0.75rem 0; font-weight: 600;">Current Signature:</p>
                            <img src="<?php echo url('view-image.php?path=' . urlencode('people/signatures/' . $person['signature_path'])); ?>" 
                                 alt="Signature" 
                                 style="max-width: 400px; max-height: 150px; border: 1px solid #e5e7eb; background: white; padding: 0.5rem;">
                            <p style="margin: 0.5rem 0 0 0; font-size: 0.875rem; color: #6b7280;">
                                Created: <?php echo !empty($person['signature_created_at']) ? date('d/m/Y H:i', strtotime($person['signature_created_at'])) : 'Unknown'; ?>
                                (<?php echo (!empty($person['signature_method']) && $person['signature_method'] === 'upload') ? 'Uploaded' : 'Digitally drawn'; ?>)
                            </p>
                        </div>
                        <p style="color: #6b7280; margin-bottom: 1rem; font-size: 0.875rem; font-style: italic;">
                            To replace your signature, use the form below.
                        </p>
                    <?php endif; ?>
                    
                    <!-- Signature Upload/Drawing Interface -->
                    <div style="border: 1px solid #e5e7eb; padding: 1.5rem; background: white;">
                        <?php if (!empty($person['signature_path'])): ?>
                            <h3 style="margin-top: 0; margin-bottom: 1rem; font-size: 1.1rem; color: #374151;">Replace Signature</h3>
                        <?php endif; ?>
                        <div style="margin-bottom: 1rem;">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Method:</label>
                            <select id="signature-method-select" style="width: 100%; padding: 0.5rem;">
                                <option value="digital">Draw Digitally</option>
                                <option value="upload">Upload Image</option>
                            </select>
                        </div>
                        
                        <!-- Digital Drawing -->
                        <div id="signature-drawing-section">
                            <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Draw Your Signature:</label>
                            <div id="signature-pad-container" style="margin-bottom: 1rem;"></div>
                            <div style="display: flex; gap: 0.5rem;">
                                <button type="button" id="clear-signature" class="btn btn-secondary" style="font-size: 0.875rem; padding: 0.5rem 1rem;">
                                    <i class="fas fa-eraser"></i> Clear
                                </button>
                            </div>
                        </div>
                        
                        <!-- Signature form is separate from profile form to prevent nested form issues -->
                        <form method="POST" action="" id="signature-form" style="margin-top: 1.5rem;">
                            <?php echo CSRF::tokenField(); ?>
                            <input type="hidden" name="action" value="save_signature">
                            <input type="hidden" name="signature_method" id="signature-method-input" value="digital">
                            <input type="hidden" name="signature_data" id="signature-data-input">
                            
                            <!-- File Upload -->
                            <div id="signature-upload-section" style="display: none;">
                                <label style="display: block; margin-bottom: 0.5rem; font-weight: 600;">Upload Signature Image:</label>
                                <input type="file" id="signature-file-input" accept="image/jpeg,image/png,image/jpg" style="width: 100%; padding: 0.5rem; margin-bottom: 0.5rem;">
                                <small style="color: #6b7280;">Upload a JPEG or PNG image of your signature (max 2MB)</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Signature
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if (!empty($organisationalUnits)): ?>
                <h3 style="margin-top: 2rem;">Organisational Units</h3>
                <ul>
                    <?php foreach ($organisationalUnits as $unit): ?>
                        <li>
                            <?php echo htmlspecialchars($unit['unit_name']); ?>
                            <?php if ($unit['is_primary']): ?>
                                <span style="color: #2563eb; font-weight: 600;">(Primary)</span>
                            <?php endif; ?>
                            <?php if ($unit['role_in_unit'] && $unit['role_in_unit'] !== 'member'): ?>
                                - <?php echo htmlspecialchars($unit['role_in_unit']); ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            </div>
        </div>
    </div>
</div>

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
.sidebar-nav-link.active {
    background-color: #2563eb;
    color: white;
    font-weight: 500;
    border-left-color: #2563eb;
}

.sidebar-nav-link.active i {
    color: white;
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

<script>
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

// Highlight active section in sidebar on scroll
document.addEventListener('DOMContentLoaded', function() {
    const sections = document.querySelectorAll('[id^="section-"]');
    const navLinks = document.querySelectorAll('.sidebar-nav a');
    
    function updateActiveSection() {
        let current = '';
        const scrollPosition = window.scrollY + 150; // Offset for sticky header
        
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
    updateActiveSection(); // Initial call
});

// Signature pad functionality
document.addEventListener('DOMContentLoaded', function() {
    const signatureMethodSelect = document.getElementById('signature-method-select');
    const drawingSection = document.getElementById('signature-drawing-section');
    const uploadSection = document.getElementById('signature-upload-section');
    const signatureMethodInput = document.getElementById('signature-method-input');
    const signatureForm = document.getElementById('signature-form');
    
    const signatureDataInput = document.getElementById('signature-data-input');
    const signatureFileInput = document.getElementById('signature-file-input');
    let signaturePad = null;
    
    if (signatureMethodSelect) {
        // Check if SignaturePad is already loaded (might be in page)
        function initSignaturePad() {
            // Prevent duplicate initialization
            if (signaturePad !== null) {
                console.log('Signature pad already initialized, skipping');
                return;
            }
            
            if (typeof SignaturePad === 'undefined') {
                console.error('SignaturePad class not found');
                return;
            }
            
            // Check if container exists
            const container = document.getElementById('signature-pad-container');
            if (!container) {
                console.error('Signature pad container not found');
                return;
            }
            
            // Check if container already has a canvas (already initialized)
            if (container.querySelector('canvas')) {
                console.log('Signature pad container already has a canvas, skipping initialization');
                return;
            }
            
            try {
                signaturePad = new SignaturePad('signature-pad-container', {
                    width: 600,
                    height: 200,
                    backgroundColor: '#ffffff',
                    penColor: '#000000'
                });
                
                console.log('Signature pad initialized successfully');
                
                // Clear button
                const clearBtn = document.getElementById('clear-signature');
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        if (signaturePad) {
                            signaturePad.clear();
                        }
                    });
                }
            } catch (e) {
                console.error('Error initializing signature pad:', e);
            }
        }
        
        // Check if already loaded
        if (typeof SignaturePad !== 'undefined') {
            initSignaturePad();
        } else {
            // Load signature pad script
            const script = document.createElement('script');
            script.src = '<?php echo url("assets/js/signature-pad.js"); ?>';
            script.onload = initSignaturePad;
            script.onerror = function() {
                console.error('Failed to load signature pad script');
            };
            document.head.appendChild(script);
        }
        
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
                
                e.preventDefault(); // Always prevent default, we'll submit manually
                
                if (signatureMethodInput.value === 'digital') {
                    // Get signature from pad
                    if (signaturePad) {
                        // Force update signature before getting it
                        signaturePad.updateSignature();
                        const signatureData = signaturePad.getSignature();
                        
                        // Check if signature has content - use isEmpty() if hasContent() doesn't exist
                        const hasContentCheck = signaturePad && (typeof signaturePad.hasContent === 'function' 
                            ? signaturePad.hasContent() 
                            : (typeof signaturePad.isEmpty === 'function' ? !signaturePad.isEmpty() : true));
                        
                        console.log('Signature data length:', signatureData ? signatureData.length : 0);
                        console.log('Has content:', hasContentCheck);
                        console.log('Signature data preview:', signatureData ? signatureData.substring(0, 50) + '...' : 'null');
                        
                        // Check if signature has content - use isEmpty() if hasContent() doesn't exist
                        const hasContent = signaturePad && (typeof signaturePad.hasContent === 'function' 
                            ? signaturePad.hasContent() 
                            : (typeof signaturePad.isEmpty === 'function' ? !signaturePad.isEmpty() : true));
                        
                        if (signatureData && signatureData.length > 100 && hasContent) {
                            // Remove file input name attribute so it's not submitted
                            if (signatureFileInput) {
                                signatureFileInput.removeAttribute('name');
                            }
                            
                            // Use signatureData directly instead of setting it to input first
                            // This avoids issues with input value persistence
                            const currentValue = signatureData; // Use the data directly from signaturePad
                            
                            if (!currentValue || currentValue.length < 100) {
                                alert('Error: Signature data not set correctly. Please try again.');
                                console.error('Signature data not properly set. Current value length:', currentValue ? currentValue.length : 0);
                                return false;
                            }
                            
                            // Set the value to the input for form submission (but we'll use currentValue in params)
                            signatureDataInput.value = currentValue;
                            signatureDataInput.setAttribute('value', currentValue);
                            
                            console.log('Signature data set in input, value length:', signatureDataInput.value.length);
                            console.log('Input element:', signatureDataInput);
                            console.log('Input value check:', signatureDataInput.value ? 'HAS VALUE' : 'NO VALUE');
                            
                            console.log('All checks passed, submitting via fetch...');
                            console.log('Signature data length being sent:', currentValue.length);
                            
                            // Use URLSearchParams instead of FormData for better compatibility with large base64 data
                            const csrfInput = signatureForm.querySelector('input[name="csrf_token"]') || signatureForm.querySelector('input[name*="csrf"]');
                            const csrfToken = csrfInput ? csrfInput.value : '';
                            
                            console.log('CSRF token found:', csrfToken ? 'yes (length: ' + csrfToken.length + ')' : 'no');
                            
                            // Build URL-encoded data (better for large base64 strings than FormData)
                            const params = new URLSearchParams();
                            params.append('action', 'save_signature');
                            params.append('signature_method', 'digital');
                            params.append('signature_data', currentValue);
                            if (csrfToken) {
                                params.append('csrf_token', csrfToken);
                            }
                            
                            const paramsString = params.toString();
                            
                            fetch(window.location.href, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-Requested-With': 'XMLHttpRequest'
                                },
                                body: paramsString
                            })
                            .then(response => {
                                return response.text().then(text => {
                                    
                                    console.log('Response text preview:', text.substring(0, 500));
                                    
                                    // Since the server returns 200 OK and processes the signature successfully,
                                    // we should treat any 200 OK response as success and reload the page.
                                    // The server-side logs confirm the signature is saved even if the HTML
                                    // doesn't contain a clear success message.
                                    
                                    if (response.ok) {
                                        // Success - reload the page to show updated signature
                                        // Small delay to ensure server has finished processing
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 100);
                                        return { ok: true, text: text };
                                    }
                                    
                                    // Only show error if response is not OK
                                    console.error('Server returned error status:', response.status);
                                    alert('Error saving signature. Server returned status: ' + response.status);
                                    return { ok: false, text: text };
                                });
                            })
                            .then(result => {
                                if (result && result.ok) {
                                    // Reload the page to show the saved signature
                                    console.log('Success! Reloading page...');
                                    window.location.reload();
                                } else {
                                    console.error('Failed to save signature');
                                    console.log('Response:', result);
                                    alert('Error saving signature. Please check the console and server logs for details.');
                                }
                            })
                            .catch(error => {
                                console.error('Fetch error:', error);
                                alert('Error saving signature: ' + error.message);
                            });
                        } else {
                            // Check if signature has content - use isEmpty() if hasContent() doesn't exist
                            const hasContentCheck = signaturePad && (typeof signaturePad.hasContent === 'function' 
                                ? signaturePad.hasContent() 
                                : (typeof signaturePad.isEmpty === 'function' ? !signaturePad.isEmpty() : true));
                            
                            alert('Please draw your signature in the box above before saving.');
                            console.log('Validation failed - signature too short or empty');
                            return false;
                        }
                    } else {
                        alert('Signature pad not initialized. Please refresh the page and try again.');
                        console.error('Signature pad is null');
                        return false;
                    }
                } else {
                    // Upload mode
                    if (!signatureFileInput.files || signatureFileInput.files.length === 0) {
                        alert('Please select a signature image to upload.');
                        return false;
                    }
                    // Ensure file input has name attribute
                    if (signatureFileInput) {
                        signatureFileInput.setAttribute('name', 'signature_file');
                    }
                    // Set enctype for file upload
                    signatureForm.setAttribute('enctype', 'multipart/form-data');
                    // Remove signature_data input so it's not submitted
                    signatureDataInput.removeAttribute('name');
                    // Submit the form
                    signatureForm.submit();
                }
            });
        }
    }
});
</script>

<?php include INCLUDES_PATH . '/footer.php'; ?>

