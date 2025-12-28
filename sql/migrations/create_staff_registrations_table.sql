-- Create staff_registrations table for tracking professional registrations and certifications
-- Important for social care staff who need to maintain registrations to work

CREATE TABLE IF NOT EXISTS staff_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL COMMENT 'Reference to staff_profiles.person_id',
    organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
    registration_type VARCHAR(100) NOT NULL COMMENT 'Type of registration (e.g., Social Services, HCPC, NMC, etc.)',
    registration_number VARCHAR(100) NULL COMMENT 'Registration number or reference',
    registration_body VARCHAR(255) NULL COMMENT 'Issuing body (e.g., Social Care Wales, HCPC, etc.)',
    issue_date DATE NULL COMMENT 'Date registration was issued',
    expiry_date DATE NOT NULL COMMENT 'Date registration expires',
    renewal_date DATE NULL COMMENT 'Date registration should be renewed',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this registration is currently active',
    is_required_for_role BOOLEAN DEFAULT TRUE COMMENT 'Whether this registration is required for their role',
    notes TEXT NULL COMMENT 'Additional notes about the registration',
    document_path VARCHAR(500) NULL COMMENT 'Path to uploaded registration document',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    
    INDEX idx_person_id (person_id),
    INDEX idx_organisation_id (organisation_id),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_registration_type (registration_type),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracks professional registrations and certifications for staff members';

