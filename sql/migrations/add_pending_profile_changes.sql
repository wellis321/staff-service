-- Pending Profile Changes
-- Approval queue for staff self-service edits.
-- Staff submit changes; their line manager (or HR at the top of the chain) approves or rejects.
-- One row per field changed, allowing partial approval (e.g. approve new address, reject photo).
-- Safe to run multiple times.

CREATE TABLE IF NOT EXISTS pending_profile_changes (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Multi-tenancy
    organisation_id INT NOT NULL,

    -- Whose record is being changed
    person_id INT NOT NULL,

    -- The user account that submitted the change (the staff member themselves)
    submitted_by_user_id INT NOT NULL,

    -- Which table and field this change targets
    -- Using ENUM keeps it constrained to the two tables staff can edit
    table_name ENUM('people', 'staff_profiles') NOT NULL,
    field_name VARCHAR(100) NOT NULL COMMENT 'Column name in the target table',
    field_label VARCHAR(150) NOT NULL COMMENT 'Human-readable field name shown in the approval UI',
    field_type ENUM('text', 'date', 'file_path', 'boolean', 'number') NOT NULL DEFAULT 'text'
        COMMENT 'How to interpret and display the value',

    -- The change itself
    current_value TEXT NULL COMMENT 'Snapshot of the live value at time of submission',
    proposed_value TEXT NULL COMMENT 'The value the staff member wants it changed to',

    -- For file fields (photo, signature): path to the pending file on disk
    -- NULL for non-file fields
    pending_file_path VARCHAR(500) NULL COMMENT 'Staging path for uploaded files pending approval',

    -- Review outcome
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    reviewer_id INT NULL COMMENT 'User ID of the manager or HR person who reviewed this',
    rejection_reason TEXT NULL COMMENT 'Reason given to the staff member when rejected',

    -- Timestamps
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (organisation_id) REFERENCES organisations(id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    FOREIGN KEY (submitted_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_organisation (organisation_id),
    INDEX idx_person (person_id),
    INDEX idx_status (status),
    INDEX idx_submitted_by (submitted_by_user_id),
    INDEX idx_reviewer (reviewer_id),
    INDEX idx_submitted_at (submitted_at),

    -- Prevent duplicate pending changes for the same field on the same person
    -- (a staff member can't have two pending changes for e.g. their bank account at once)
    UNIQUE KEY unique_pending_field (person_id, table_name, field_name, status)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Approval queue for staff self-service profile edits';
