-- ============================================================================
-- Complete People Management Service Production Database Schema
-- ============================================================================
-- This file contains the complete database schema for production deployment.
-- You can paste this entire file into phpMyAdmin SQL tab and execute it.
-- 
-- UK English spelling used throughout
-- All tables use InnoDB engine with utf8mb4 charset for full Unicode support
-- ============================================================================

-- ============================================================================
-- PART 1: Core Authentication Tables (from shared-auth package)
-- ============================================================================

-- Organisations table - Multi-tenant organisations
CREATE TABLE IF NOT EXISTS organisations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    seats_allocated INT NOT NULL DEFAULT 0,
    seats_used INT NOT NULL DEFAULT 0,
    person_singular VARCHAR(100) DEFAULT 'person',
    person_plural VARCHAR(100) DEFAULT 'people',
    entra_enabled BOOLEAN DEFAULT FALSE COMMENT 'Enable Microsoft Entra/365 integration',
    entra_tenant_id VARCHAR(255) NULL COMMENT 'Microsoft Entra Tenant ID',
    entra_client_id VARCHAR(255) NULL COMMENT 'Microsoft Entra Application (Client) ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_domain (domain)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles table - Role definitions
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users table - User accounts with organisation association
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255) NULL,
    verification_token_expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_email (email),
    INDEX idx_organisation (organisation_id),
    INDEX idx_email (email),
    INDEX idx_verification_token (verification_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User roles table - User-role assignments
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user (user_id),
    INDEX idx_role (role_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT IGNORE INTO roles (name, description) VALUES
('superadmin', 'Super administrator with full system access'),
('organisation_admin', 'Organisation administrator with full access to their organisation'),
('staff', 'Standard staff member');

-- ============================================================================
-- PART 2: Organisational Units Tables (from shared-auth package)
-- ============================================================================

-- Organisational unit types table
CREATE TABLE IF NOT EXISTS organisational_unit_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    display_name VARCHAR(255) NOT NULL,
    level_order INT NOT NULL DEFAULT 1,
    parent_type_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_type_id) REFERENCES organisational_unit_types(id) ON DELETE SET NULL,
    INDEX idx_organisation (organisation_id),
    INDEX idx_parent_type (parent_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organisational units table
CREATE TABLE IF NOT EXISTS organisational_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    unit_type_id INT NULL,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(100) NULL,
    description TEXT NULL,
    parent_unit_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_type_id) REFERENCES organisational_unit_types(id) ON DELETE SET NULL,
    FOREIGN KEY (parent_unit_id) REFERENCES organisational_units(id) ON DELETE SET NULL,
    INDEX idx_organisation (organisation_id),
    INDEX idx_unit_type (unit_type_id),
    INDEX idx_parent_unit (parent_unit_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 3: People Management Service Core Tables
-- ============================================================================

-- People table - Unified table for all person types (staff, people we support, etc.)
CREATE TABLE IF NOT EXISTS people (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    person_type ENUM('staff', 'person_we_support') NOT NULL DEFAULT 'staff',
    user_id INT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    date_of_birth DATE NULL,
    employee_reference VARCHAR(100) NULL,
    nhs_number VARCHAR(20) NULL,
    is_active BOOLEAN DEFAULT TRUE,
    photo_path VARCHAR(255) NULL,
    photo_approval_status ENUM('approved', 'pending', 'rejected') DEFAULT 'approved',
    photo_pending_path VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_org_employee_ref (organisation_id, employee_reference),
    INDEX idx_organisation (organisation_id),
    INDEX idx_user (user_id),
    INDEX idx_person_type (person_type),
    INDEX idx_employee_reference (employee_reference),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff profiles table - Staff-specific data
CREATE TABLE IF NOT EXISTS staff_profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    job_title VARCHAR(255) NULL,
    employment_start_date DATE NULL,
    employment_end_date DATE NULL,
    line_manager_id INT NULL,
    emergency_contact_name VARCHAR(255) NULL,
    emergency_contact_phone VARCHAR(50) NULL,
    notes TEXT NULL,
    -- Employment details fields
    contracted_hours DECIMAL(5,2) NULL COMMENT 'Contracted hours per week',
    place_of_work VARCHAR(255) NULL COMMENT 'Primary place of work/location',
    job_description_id INT NULL COMMENT 'Reference to job_descriptions table',
    job_post_id INT NULL COMMENT 'Reference to job_posts table',
    external_job_description_url VARCHAR(500) NULL COMMENT 'URL to job description in external system',
    external_job_description_ref VARCHAR(100) NULL COMMENT 'Reference ID for job description in external system',
    -- Financial and identification fields
    ni_number VARCHAR(20) NULL COMMENT 'National Insurance number (UK)',
    bank_sort_code VARCHAR(10) NULL COMMENT 'Bank sort code',
    bank_account_number VARCHAR(20) NULL COMMENT 'Bank account number',
    bank_account_name VARCHAR(255) NULL COMMENT 'Account holder name',
    address_line1 VARCHAR(255) NULL COMMENT 'Address line 1',
    address_line2 VARCHAR(255) NULL COMMENT 'Address line 2',
    address_city VARCHAR(100) NULL COMMENT 'City/Town',
    address_county VARCHAR(100) NULL COMMENT 'County/State',
    address_postcode VARCHAR(20) NULL COMMENT 'Postcode/ZIP',
    address_country VARCHAR(100) NULL DEFAULT 'United Kingdom' COMMENT 'Country',
    -- Leave management fields
    annual_leave_allocation DECIMAL(5,2) NULL COMMENT 'Annual leave allocation in days from job post',
    annual_leave_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave used in days',
    annual_leave_carry_over DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave carried over from previous year',
    time_in_lieu_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours accrued',
    time_in_lieu_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours used',
    lying_time_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours accrued',
    lying_time_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours used',
    leave_year_start_date DATE NULL COMMENT 'Start date of current leave year',
    leave_year_end_date DATE NULL COMMENT 'End date of current leave year',
    -- Signature support
    signature_path VARCHAR(500) NULL COMMENT 'Path to uploaded signature image file',
    signature_created_at DATETIME NULL COMMENT 'When the signature was created/uploaded',
    signature_method ENUM('upload', 'digital') NULL COMMENT 'Method used to create signature (upload or digital drawing)',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (line_manager_id) REFERENCES people(id) ON DELETE SET NULL,
    UNIQUE KEY unique_person (person_id),
    INDEX idx_line_manager (line_manager_id),
    INDEX idx_ni_number (ni_number),
    INDEX idx_leave_year (leave_year_start_date, leave_year_end_date),
    INDEX idx_signature_path (signature_path)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Person organisational units table - Many-to-many relationship
CREATE TABLE IF NOT EXISTS person_organisational_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    organisational_unit_id INT NOT NULL,
    role_in_unit VARCHAR(100) DEFAULT 'member',
    is_primary BOOLEAN DEFAULT FALSE,
    start_date DATE NULL,
    end_date DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisational_unit_id) REFERENCES organisational_units(id) ON DELETE CASCADE,
    UNIQUE KEY unique_person_unit (person_id, organisational_unit_id),
    INDEX idx_person (person_id),
    INDEX idx_organisational_unit (organisational_unit_id),
    INDEX idx_is_primary (is_primary)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 4: Job Descriptions and Posts
-- ============================================================================

-- Job descriptions table - Generic job description templates
CREATE TABLE IF NOT EXISTS job_descriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    title VARCHAR(255) NOT NULL COMMENT 'Job title',
    code VARCHAR(100) NULL COMMENT 'Job code/reference',
    description TEXT NULL COMMENT 'Full job description',
    responsibilities TEXT NULL COMMENT 'Key responsibilities',
    requirements TEXT NULL COMMENT 'Required qualifications/skills',
    salary_range_min DECIMAL(10,2) NULL COMMENT 'Minimum salary',
    salary_range_max DECIMAL(10,2) NULL COMMENT 'Maximum salary',
    salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency code',
    hours_per_week DECIMAL(5,2) NULL COMMENT 'Standard hours per week',
    contract_type VARCHAR(50) NULL COMMENT 'e.g. Permanent, Temporary, Contract',
    location VARCHAR(255) NULL COMMENT 'Standard location',
    department VARCHAR(255) NULL COMMENT 'Department',
    reporting_to VARCHAR(255) NULL COMMENT 'Reports to position',
    external_system VARCHAR(100) NULL COMMENT 'External system name if synced',
    external_id VARCHAR(100) NULL COMMENT 'ID in external system',
    external_url VARCHAR(500) NULL COMMENT 'URL in external system',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this job description is currently active',
    version INT DEFAULT 1 COMMENT 'Version number for tracking changes',
    created_by INT NULL COMMENT 'User who created this',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_organisation (organisation_id),
    INDEX idx_title (title),
    INDEX idx_code (code),
    INDEX idx_external (external_system, external_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job description documents table for attachments
CREATE TABLE IF NOT EXISTS job_description_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_description_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(100) NULL,
    file_size INT NULL COMMENT 'Size in bytes',
    description TEXT NULL,
    uploaded_by INT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job_description (job_description_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job posts table - Specific positions based on job descriptions
CREATE TABLE IF NOT EXISTS job_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    job_description_id INT NOT NULL COMMENT 'Reference to generic job description template',
    title VARCHAR(255) NOT NULL COMMENT 'Specific post title (can override job description title)',
    code VARCHAR(100) NULL COMMENT 'Post code/reference',
    location VARCHAR(255) NULL COMMENT 'Specific location for this post',
    place_of_work VARCHAR(255) NULL COMMENT 'Specific place of work',
    hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours for this specific post',
    contract_type VARCHAR(50) NULL COMMENT 'e.g. Permanent, Temporary, Contract, Part-time, Full-time',
    salary_range_min DECIMAL(10,2) NULL COMMENT 'Minimum salary for this post',
    salary_range_max DECIMAL(10,2) NULL COMMENT 'Maximum salary for this post',
    salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency code',
    reporting_to VARCHAR(255) NULL COMMENT 'Reports to position',
    manager_user_id INT NULL COMMENT 'Specific manager for this post',
    department VARCHAR(255) NULL COMMENT 'Department for this post',
    additional_requirements TEXT NULL COMMENT 'Additional requirements specific to this post',
    specific_attributes TEXT NULL COMMENT 'Specific attributes needed (e.g. gender, language skills)',
    external_system VARCHAR(100) NULL COMMENT 'External system name if synced',
    external_id VARCHAR(100) NULL COMMENT 'ID in external system',
    external_url VARCHAR(500) NULL COMMENT 'URL in external system',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is currently active/open',
    is_open BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is open for applications',
    created_by INT NULL COMMENT 'User who created this post',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE RESTRICT,
    FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_organisation (organisation_id),
    INDEX idx_job_description (job_description_id),
    INDEX idx_location (location),
    INDEX idx_is_active (is_active),
    INDEX idx_is_open (is_open),
    INDEX idx_external (external_system, external_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Job post history table - Track changes to job posts over time
CREATE TABLE IF NOT EXISTS job_post_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_post_id INT NOT NULL,
    changed_by INT NULL COMMENT 'User who made the change',
    change_type VARCHAR(50) DEFAULT 'update' COMMENT 'Type of change: create, update, activate, deactivate',
    title VARCHAR(255) NULL,
    code VARCHAR(100) NULL,
    location VARCHAR(255) NULL,
    place_of_work VARCHAR(255) NULL,
    hours_per_week DECIMAL(5,2) NULL,
    contract_type VARCHAR(50) NULL,
    salary_range_min DECIMAL(10,2) NULL,
    salary_range_max DECIMAL(10,2) NULL,
    salary_currency VARCHAR(3) NULL,
    reporting_to VARCHAR(255) NULL,
    manager_user_id INT NULL,
    department VARCHAR(255) NULL,
    additional_requirements TEXT NULL,
    specific_attributes TEXT NULL,
    is_active BOOLEAN NULL,
    is_open BOOLEAN NULL,
    changed_fields JSON NULL COMMENT 'JSON array of field names that changed',
    change_notes TEXT NULL COMMENT 'Notes about this change',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job_post (job_post_id),
    INDEX idx_created_at (created_at),
    INDEX idx_change_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 5: Staff History and Relationships
-- ============================================================================

-- Staff role history table - Track role changes and salary history
CREATE TABLE IF NOT EXISTS staff_role_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL COMMENT 'Reference to people.id',
    organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
    job_post_id INT NULL COMMENT 'Reference to job_posts.id - the role/position',
    job_title VARCHAR(255) NULL COMMENT 'Job title (can be different from job_post title if customized)',
    start_date DATE NOT NULL COMMENT 'Date role started',
    end_date DATE NULL COMMENT 'Date role ended (NULL if current role)',
    is_current BOOLEAN DEFAULT FALSE COMMENT 'Whether this is the current active role',
    salary DECIMAL(10,2) NULL COMMENT 'Salary for this role',
    salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Salary currency',
    hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours per week for this role',
    contract_type VARCHAR(50) NULL COMMENT 'Contract type for this role',
    line_manager_id INT NULL COMMENT 'Line manager for this role',
    place_of_work VARCHAR(255) NULL COMMENT 'Place of work for this role',
    notes TEXT NULL COMMENT 'Notes about this role assignment',
    created_by INT NULL COMMENT 'User who created this record',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL,
    FOREIGN KEY (line_manager_id) REFERENCES people(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_person_id (person_id),
    INDEX idx_organisation_id (organisation_id),
    INDEX idx_job_post_id (job_post_id),
    INDEX idx_start_date (start_date),
    INDEX idx_end_date (end_date),
    INDEX idx_is_current (is_current),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracks role history and salary changes for staff members over time';

-- Person relationships table - Link old and new person records
CREATE TABLE IF NOT EXISTS person_relationships (
    id INT AUTO_INCREMENT PRIMARY KEY,
    primary_person_id INT NOT NULL COMMENT 'The current/active person record',
    linked_person_id INT NOT NULL COMMENT 'The old/inactive person record',
    organisation_id INT NOT NULL COMMENT 'Organisation for security',
    relationship_type ENUM('previous_employment', 'merged', 'linked') NOT NULL DEFAULT 'previous_employment' COMMENT 'Type of relationship',
    linked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When the relationship was created',
    linked_by INT NULL COMMENT 'User who created the link',
    notes TEXT NULL COMMENT 'Optional notes about the relationship',
    FOREIGN KEY (primary_person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (linked_person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (linked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_person_relationship (primary_person_id, linked_person_id),
    INDEX idx_primary_person (primary_person_id),
    INDEX idx_linked_person (linked_person_id),
    INDEX idx_organisation (organisation_id),
    INDEX idx_relationship_type (relationship_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Links person records to allow learning records to persist across employment periods';

-- ============================================================================
-- PART 6: Learning, Registrations, and Documents
-- ============================================================================

-- Staff learning records table - Track qualifications and learning
CREATE TABLE IF NOT EXISTS staff_learning_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL COMMENT 'Reference to people.id',
    organisation_id INT NOT NULL COMMENT 'Reference to organisations.id',
    record_type VARCHAR(50) NOT NULL COMMENT 'Type: qualification, course, training, certification, etc.',
    title VARCHAR(255) NOT NULL COMMENT 'Title of qualification/course/learning',
    description TEXT NULL COMMENT 'Description of the learning/qualification',
    provider VARCHAR(255) NULL COMMENT 'Provider/institution (e.g., university, training company)',
    qualification_level VARCHAR(100) NULL COMMENT 'Level (e.g., Level 2, Level 3, Degree, Masters)',
    subject_area VARCHAR(255) NULL COMMENT 'Subject or field of study',
    completion_date DATE NULL COMMENT 'Date completed/achieved',
    expiry_date DATE NULL COMMENT 'Expiry date if applicable (e.g., for certifications)',
    grade VARCHAR(50) NULL COMMENT 'Grade or result achieved',
    credits DECIMAL(5,2) NULL COMMENT 'Credits or hours (if applicable)',
    certificate_number VARCHAR(100) NULL COMMENT 'Certificate or qualification number',
    certificate_path VARCHAR(500) NULL COMMENT 'Path to uploaded certificate document',
    external_url VARCHAR(500) NULL COMMENT 'URL to external record (e.g., LMS link)',
    source_system VARCHAR(50) NULL COMMENT 'Source system: recruitment, lms, hr, manual',
    external_id VARCHAR(100) NULL COMMENT 'External ID from source system',
    is_mandatory BOOLEAN DEFAULT FALSE COMMENT 'Whether this is mandatory training',
    is_required_for_role BOOLEAN DEFAULT FALSE COMMENT 'Whether required for their current role',
    status VARCHAR(50) DEFAULT 'completed' COMMENT 'Status: completed, in_progress, expired, pending',
    notes TEXT NULL COMMENT 'Additional notes',
    created_by INT NULL COMMENT 'User who created this record',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_synced_at TIMESTAMP NULL COMMENT 'Last time synced from external system',
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_person_id (person_id),
    INDEX idx_organisation_id (organisation_id),
    INDEX idx_record_type (record_type),
    INDEX idx_completion_date (completion_date),
    INDEX idx_expiry_date (expiry_date),
    INDEX idx_status (status),
    INDEX idx_source_system (source_system),
    INDEX idx_external_id (external_id),
    INDEX idx_is_mandatory (is_mandatory),
    INDEX idx_employee_ref (person_id, organisation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Tracks qualifications and learning records for staff members, including from recruitment, LMS, and manual entries';

-- Staff registrations table - Professional registrations and certifications
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

-- Staff documents table - References to documents related to staff members
CREATE TABLE IF NOT EXISTS staff_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    document_type VARCHAR(100) NOT NULL COMMENT 'e.g. contract, offer_letter, id_verification, training_certificate',
    title VARCHAR(255) NOT NULL,
    description TEXT NULL,
    filename VARCHAR(255) NULL COMMENT 'If stored locally',
    file_path VARCHAR(500) NULL COMMENT 'Path to local file',
    external_url VARCHAR(500) NULL COMMENT 'URL if stored in external system',
    external_system VARCHAR(100) NULL COMMENT 'External system name',
    external_id VARCHAR(100) NULL COMMENT 'ID in external system',
    file_type VARCHAR(100) NULL COMMENT 'MIME type',
    file_size INT NULL COMMENT 'Size in bytes',
    expiry_date DATE NULL COMMENT 'If document has expiry date',
    uploaded_by INT NULL COMMENT 'User who uploaded/created reference',
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_person (person_id),
    INDEX idx_document_type (document_type),
    INDEX idx_external (external_system, external_id),
    INDEX idx_expiry_date (expiry_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 7: API Integration and External Systems
-- ============================================================================

-- API Keys table - For external system authentication
CREATE TABLE IF NOT EXISTS api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    organisation_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    api_key_hash VARCHAR(64) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    expires_at TIMESTAMP NULL,
    last_used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    INDEX idx_api_key_hash (api_key_hash),
    INDEX idx_organisation (organisation_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Webhook subscriptions table - For real-time updates to external systems
CREATE TABLE IF NOT EXISTS webhook_subscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    url VARCHAR(500) NOT NULL,
    events JSON NOT NULL,
    secret VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_triggered_at TIMESTAMP NULL,
    last_success_at TIMESTAMP NULL,
    last_failure_at TIMESTAMP NULL,
    failure_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    INDEX idx_organisation (organisation_id),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- External system sync tracking table
CREATE TABLE IF NOT EXISTS external_system_sync (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    system_name VARCHAR(100) NOT NULL,
    system_type ENUM('entra', 'hr', 'finance', 'lms', 'recruitment', 'other') NOT NULL,
    last_sync_at TIMESTAMP NULL,
    last_successful_sync_at TIMESTAMP NULL,
    sync_status ENUM('active', 'pending', 'failed', 'disabled') DEFAULT 'pending',
    sync_error TEXT NULL,
    sync_metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_org_system (organisation_id, system_name),
    INDEX idx_system_type (system_type),
    INDEX idx_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Entra sync table - Microsoft Entra/365 integration
CREATE TABLE IF NOT EXISTS entra_sync (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    organisation_id INT NOT NULL,
    entra_user_id VARCHAR(255) NOT NULL,
    last_synced_at TIMESTAMP NULL,
    sync_status ENUM('active', 'pending', 'failed', 'disabled') DEFAULT 'pending',
    sync_error TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    UNIQUE KEY unique_entra_user (entra_user_id),
    INDEX idx_person (person_id),
    INDEX idx_organisation (organisation_id),
    INDEX idx_sync_status (sync_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Recruitment imports table - Log imports from recruitment systems
CREATE TABLE IF NOT EXISTS recruitment_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    imported_by INT NOT NULL,
    source_system VARCHAR(100) NULL,
    import_type ENUM('csv', 'json', 'api') NOT NULL,
    filename VARCHAR(255) NULL,
    total_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    import_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    error_log TEXT NULL,
    imported_data JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE RESTRICT,
    INDEX idx_organisation (organisation_id),
    INDEX idx_import_status (import_status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 8: System and Security Tables
-- ============================================================================

-- Rate limits table - For rate limiting functionality
CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rate_key VARCHAR(255) NOT NULL,
    attempts INT NOT NULL DEFAULT 1,
    first_attempt_at DATETIME NOT NULL,
    reset_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_key (rate_key),
    INDEX idx_reset_at (reset_at),
    UNIQUE KEY unique_rate_key (rate_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Organisation requests table - Stores requests from organisations wanting to use the service
CREATE TABLE IF NOT EXISTS organisation_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_name VARCHAR(255) NOT NULL COMMENT 'Name of the requesting organisation',
    organisation_domain VARCHAR(255) NOT NULL COMMENT 'Email domain for the organisation',
    contact_name VARCHAR(255) NOT NULL COMMENT 'Name of the person making the request',
    contact_email VARCHAR(255) NOT NULL COMMENT 'Email address of the contact person',
    contact_phone VARCHAR(50) NULL COMMENT 'Phone number of the contact person',
    seats_requested INT NOT NULL COMMENT 'Number of seats requested',
    description TEXT NULL COMMENT 'Description of the organisation',
    use_case TEXT NULL COMMENT 'Intended use case for the service',
    status ENUM('pending', 'approved', 'rejected', 'contacted') DEFAULT 'pending' COMMENT 'Status of the request',
    reviewed_by INT NULL COMMENT 'User ID of the superadmin who reviewed this',
    reviewed_at TIMESTAMP NULL COMMENT 'When the request was reviewed',
    review_notes TEXT NULL COMMENT 'Notes from the reviewer',
    ip_address VARCHAR(45) NULL COMMENT 'IP address of the requester',
    submitted_from VARCHAR(255) NULL COMMENT 'Host/domain the request was submitted from',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_organisation_domain (organisation_domain),
    INDEX idx_contact_email (contact_email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- PART 9: Add Foreign Key Constraints (after all tables are created)
-- ============================================================================

-- Add foreign key constraints for staff_profiles that reference tables created later
-- These are added here to avoid dependency order issues

-- Add foreign key for job_description_id in staff_profiles
ALTER TABLE staff_profiles 
ADD CONSTRAINT fk_staff_profiles_job_description 
FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE SET NULL;

-- Add foreign key for job_post_id in staff_profiles
ALTER TABLE staff_profiles 
ADD CONSTRAINT fk_staff_profiles_job_post 
FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL;

-- ============================================================================
-- END OF SCHEMA
-- ============================================================================
-- 
-- Schema creation complete!
-- 
-- Next steps:
-- 1. Create your first organisation via the registration page
-- 2. Create your first user account
-- 3. Set up API keys if needed for external integrations
-- 4. Configure Microsoft Entra integration if needed
-- 
-- ============================================================================

