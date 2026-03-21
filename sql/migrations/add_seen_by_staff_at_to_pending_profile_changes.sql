-- Add seen_by_staff_at to pending_profile_changes
-- Tracks whether the staff member has seen the outcome of a reviewed change.
-- NULL = not yet seen. Set when the staff member next loads their profile page.
-- Safe to run multiple times.

SET @dbname = DATABASE();
SET @tablename = 'pending_profile_changes';
SET @columnname = 'seen_by_staff_at';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE (TABLE_SCHEMA = @dbname)
          AND (TABLE_NAME  = @tablename)
          AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    'ALTER TABLE pending_profile_changes
     ADD COLUMN seen_by_staff_at TIMESTAMP NULL DEFAULT NULL
         COMMENT ''When the staff member acknowledged this reviewed change (clears the badge)''
     AFTER reviewed_at'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
