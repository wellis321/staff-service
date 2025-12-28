<?php
/**
 * Export Staff Data
 * CSV or JSON export of staff data
 */

require_once dirname(__DIR__, 2) . '/config/config.php';

Auth::requireLogin();
RBAC::requireAdmin();

$organisationId = Auth::getOrganisationId();
$format = $_GET['format'] ?? 'csv';

// Get all staff
$staff = Person::getStaffByOrganisation($organisationId, false); // Include inactive

if ($format === 'json') {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="staff-export-' . date('Y-m-d') . '.json"');
    
    // Include organisational units
    foreach ($staff as &$member) {
        $units = Person::getOrganisationalUnits($member['id']);
        $member['organisational_units'] = $units;
    }
    
    echo json_encode($staff, JSON_PRETTY_PRINT);
} else {
    // CSV export
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="staff-export-' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV headers
    fputcsv($output, [
        'ID',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Employee Reference',
        'Job Title',
        'Employment Start Date',
        'Employment End Date',
        'Line Manager',
        'Emergency Contact Name',
        'Emergency Contact Phone',
        'Status',
        'Organisational Units'
    ]);
    
    // CSV rows
    foreach ($staff as $member) {
        $lineManager = null;
        if ($member['line_manager_id']) {
            $lm = Person::findById($member['line_manager_id'], $organisationId);
            $lineManager = $lm ? $lm['first_name'] . ' ' . $lm['last_name'] : '';
        }
        
        $units = Person::getOrganisationalUnits($member['id']);
        $unitsList = implode('; ', array_map(function($u) {
            $str = $u['unit_name'];
            if ($u['is_primary']) {
                $str .= ' (Primary)';
            }
            if ($u['role_in_unit'] && $u['role_in_unit'] !== 'member') {
                $str .= ' - ' . $u['role_in_unit'];
            }
            return $str;
        }, $units));
        
        fputcsv($output, [
            $member['id'],
            $member['first_name'],
            $member['last_name'],
            $member['email'] ?? $member['user_email'] ?? '',
            $member['phone'] ?? '',
            $member['employee_reference'] ?? '',
            $member['job_title'] ?? '',
            $member['employment_start_date'] ?? '',
            $member['employment_end_date'] ?? '',
            $lineManager ?? '',
            $member['emergency_contact_name'] ?? '',
            $member['emergency_contact_phone'] ?? '',
            $member['is_active'] ? 'Active' : 'Inactive',
            $unitsList
        ]);
    }
    
    fclose($output);
}

exit;

