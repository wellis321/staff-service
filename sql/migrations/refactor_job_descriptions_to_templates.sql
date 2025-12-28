-- Migration: Refactor job_descriptions to be generic templates
-- Remove position-specific fields (location, hours, salary, etc.) from job_descriptions
-- These will move to a new job_posts table

-- First, create job_posts table for specific positions
CREATE TABLE IF NOT EXISTS job_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    organisation_id INT NOT NULL,
    job_description_id INT NOT NULL COMMENT 'Reference to generic job description template',
    title VARCHAR(255) NOT NULL COMMENT 'Specific post title (can override job description title)',
    code VARCHAR(100) NULL COMMENT 'Post code/reference',
    
    -- Position-specific details
    location VARCHAR(255) NULL COMMENT 'Specific location for this post',
    place_of_work VARCHAR(255) NULL COMMENT 'Specific place of work',
    hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours for this specific post',
    contract_type VARCHAR(50) NULL COMMENT 'e.g. Permanent, Temporary, Contract, Part-time, Full-time',
    salary_range_min DECIMAL(10,2) NULL COMMENT 'Minimum salary for this post',
    salary_range_max DECIMAL(10,2) NULL COMMENT 'Maximum salary for this post',
    salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency code',
    
    -- Reporting and management
    reporting_to VARCHAR(255) NULL COMMENT 'Reports to position',
    manager_user_id INT NULL COMMENT 'Specific manager for this post',
    department VARCHAR(255) NULL COMMENT 'Department for this post',
    
    -- Post-specific requirements (in addition to job description requirements)
    additional_requirements TEXT NULL COMMENT 'Additional requirements specific to this post',
    specific_attributes TEXT NULL COMMENT 'Specific attributes needed (e.g. gender, language skills)',
    
    -- External system integration
    external_system VARCHAR(100) NULL COMMENT 'External system name if synced',
    external_id VARCHAR(100) NULL COMMENT 'ID in external system',
    external_url VARCHAR(500) NULL COMMENT 'URL in external system',
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is currently active/open',
    is_open BOOLEAN DEFAULT TRUE COMMENT 'Whether this post is open for applications',
    
    -- Tracking
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

-- Now update staff_profiles to reference job_posts instead of job_descriptions
-- First, add job_post_id column
ALTER TABLE staff_profiles 
ADD COLUMN job_post_id INT NULL COMMENT 'Reference to job_posts table';

-- Add foreign key constraint
ALTER TABLE staff_profiles 
ADD CONSTRAINT fk_job_post 
FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE SET NULL;

-- Note: job_description_id in staff_profiles can remain for backward compatibility
-- but new assignments should use job_post_id

