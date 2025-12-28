-- Create person_relationships table to link old and new person records
-- This allows learning records to persist across employment periods when staff rejoin with different employee numbers

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

