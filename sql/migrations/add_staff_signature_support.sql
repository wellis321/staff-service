-- Migration: Add staff signature support
-- Allows staff to upload or digitally draw their signature for use in forms
-- 
-- NOTE: MySQL does not support IF NOT EXISTS for ALTER TABLE ADD COLUMN.
-- If you get "Duplicate column" errors, the columns already exist and you can skip those statements.
-- Alternatively, use the PHP migration script: public/migrate-staff-signatures.php
-- which automatically checks for existing columns.

-- Check existing columns first (run this to see what exists):
-- DESCRIBE staff_profiles;

-- Add signature field to staff_profiles (skip if column already exists)
ALTER TABLE staff_profiles 
ADD COLUMN signature_path VARCHAR(500) NULL COMMENT 'Path to uploaded signature image file';

-- Add signature created timestamp (skip if column already exists)
ALTER TABLE staff_profiles 
ADD COLUMN signature_created_at DATETIME NULL COMMENT 'When the signature was created/uploaded';

-- Add signature method (skip if column already exists)
ALTER TABLE staff_profiles 
ADD COLUMN signature_method ENUM('upload', 'digital') NULL COMMENT 'Method used to create signature (upload or digital drawing)';

-- Add index for signature queries (skip if index already exists)
-- Check existing indexes first: SHOW INDEX FROM staff_profiles WHERE Key_name = 'idx_signature_path';
ALTER TABLE staff_profiles 
ADD INDEX idx_signature_path (signature_path);

