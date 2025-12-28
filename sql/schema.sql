-- People Management Service Database Schema
-- UK English spelling used throughout

-- IMPORTANT: This schema assumes core authentication tables already exist.
-- If you haven't run the shared-auth migrations yet, use complete_schema.sql instead.
-- 
-- To set up the database:
-- 1. Run: shared-auth/migrations/core_schema.sql (creates organisations, users, roles, user_roles)
-- 2. Then run: sql/schema.sql (creates people management tables)
-- 
-- OR use the combined file: sql/complete_schema.sql (creates everything in order)

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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (line_manager_id) REFERENCES people(id) ON DELETE SET NULL,
    UNIQUE KEY unique_person (person_id),
    INDEX idx_line_manager (line_manager_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Person organisational units table - Many-to-many relationship
-- Links people to organisational units (from shared-auth)
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

-- Future: People we support profiles table (for when extending to support service users)
-- CREATE TABLE IF NOT EXISTS people_we_support_profiles (
--     id INT AUTO_INCREMENT PRIMARY KEY,
--     person_id INT NOT NULL,
--     care_plan_path VARCHAR(255) NULL,
--     guardian_name VARCHAR(255) NULL,
--     guardian_contact VARCHAR(255) NULL,
--     medical_conditions TEXT NULL,
--     allergies TEXT NULL,
--     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--     updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--     FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
--     UNIQUE KEY unique_person (person_id)
-- ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

