-- Staff Appraisals and Supervisions
-- Safe to run multiple times

CREATE TABLE IF NOT EXISTS staff_appraisals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    organisation_id INT NOT NULL,
    appraisal_date DATE NOT NULL,
    due_date DATE NULL,
    appraiser_name VARCHAR(255) NULL,
    appraiser_person_id INT NULL,
    appraisal_type ENUM('annual', 'probationary', 'interim', 'return_to_work', 'other') NOT NULL DEFAULT 'annual',
    outcome ENUM('outstanding', 'exceeds_expectations', 'meets_expectations', 'requires_improvement', 'unsatisfactory', 'not_completed', 'pending') NOT NULL DEFAULT 'pending',
    next_due_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    INDEX idx_appraisals_person (person_id),
    INDEX idx_appraisals_org (organisation_id),
    INDEX idx_appraisals_next_due (next_due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS staff_supervisions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    person_id INT NOT NULL,
    organisation_id INT NOT NULL,
    supervision_date DATE NOT NULL,
    due_date DATE NULL,
    supervisor_name VARCHAR(255) NULL,
    supervisor_person_id INT NULL,
    supervision_type ENUM('individual', 'group', 'peer', 'other') NOT NULL DEFAULT 'individual',
    duration_minutes INT NULL,
    outcome TEXT NULL,
    next_due_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    INDEX idx_supervisions_person (person_id),
    INDEX idx_supervisions_org (organisation_id),
    INDEX idx_supervisions_next_due (next_due_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
