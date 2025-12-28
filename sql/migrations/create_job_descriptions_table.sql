-- Migration: Create job_descriptions table
-- This table stores job descriptions that can be referenced by staff profiles
-- Can be managed in this app or synced from external recruitment/HR systems

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

-- Create job description documents table for attachments
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

-- Add foreign key constraint for job_description_id in staff_profiles
-- Run this after both tables are created
-- ALTER TABLE staff_profiles ADD CONSTRAINT fk_job_description FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE SET NULL;

