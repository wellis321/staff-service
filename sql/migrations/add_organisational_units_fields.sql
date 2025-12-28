-- Migration: Add missing fields to organisational_units table
-- These fields are required by the shared-auth OrganisationalUnits class
-- The class uses a simplified schema approach with unit_type as a string rather than unit_type_id

-- Note: Run these commands one at a time. If a column already exists, you'll get an error - that's okay, just skip it.

-- Add is_active column
ALTER TABLE organisational_units 
ADD COLUMN is_active BOOLEAN DEFAULT TRUE;

-- Add metadata column
ALTER TABLE organisational_units 
ADD COLUMN metadata JSON NULL;

-- Add manager_user_id column
ALTER TABLE organisational_units 
ADD COLUMN manager_user_id INT NULL;

-- Add foreign key for manager_user_id
ALTER TABLE organisational_units 
ADD CONSTRAINT fk_organisational_units_manager 
    FOREIGN KEY (manager_user_id) REFERENCES users(id) ON DELETE SET NULL;

-- Add display_order column
ALTER TABLE organisational_units 
ADD COLUMN display_order INT DEFAULT 0;

-- Add unit_type as string (in addition to unit_type_id)
-- This allows the OrganisationalUnits class to work with both approaches
ALTER TABLE organisational_units 
ADD COLUMN unit_type VARCHAR(100) NULL;

-- Add index for is_active (if it doesn't exist, you may get an error - that's okay)
ALTER TABLE organisational_units 
ADD INDEX idx_is_active (is_active);

-- Add index for manager_user_id (if it doesn't exist, you may get an error - that's okay)
ALTER TABLE organisational_units 
ADD INDEX idx_manager_user (manager_user_id);

