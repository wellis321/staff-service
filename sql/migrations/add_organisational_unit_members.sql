-- Migration: Add organisational_unit_members table
-- This table is required by the shared-auth OrganisationalUnits class
-- It links users to organisational units (separate from person_organisational_units which links people)

CREATE TABLE IF NOT EXISTS organisational_unit_members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    user_id INT NOT NULL,
    
    -- Role within this specific unit (flexible, not constrained)
    role VARCHAR(100) DEFAULT 'member',  -- e.g., "member", "lead", "manager", "coordinator", "admin"
    
    -- Timestamps
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (unit_id) REFERENCES organisational_units(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_unit_member (unit_id, user_id),
    INDEX idx_unit (unit_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

