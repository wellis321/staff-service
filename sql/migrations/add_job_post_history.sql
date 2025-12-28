-- Migration: Add job post history tracking
-- Track changes to job posts over time (e.g., salary changes, hours changes)

CREATE TABLE IF NOT EXISTS job_post_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_post_id INT NOT NULL,
    changed_by INT NULL COMMENT 'User who made the change',
    change_type VARCHAR(50) DEFAULT 'update' COMMENT 'Type of change: create, update, activate, deactivate',
    
    -- Snapshot of job post fields at time of change
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
    
    -- Change details
    changed_fields JSON NULL COMMENT 'JSON array of field names that changed',
    change_notes TEXT NULL COMMENT 'Notes about this change',
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (job_post_id) REFERENCES job_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_job_post (job_post_id),
    INDEX idx_created_at (created_at),
    INDEX idx_change_type (change_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

