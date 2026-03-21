-- Add connected_app to api_keys
-- Tags each key with the system it was created for, making the admin UI
-- and audit trail much clearer as more integrations are added over time.
-- Safe to run multiple times.

SET @dbname = DATABASE();
SET @tablename = 'api_keys';
SET @columnname = 'connected_app';
SET @preparedStatement = (SELECT IF(
    (
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE (TABLE_SCHEMA = @dbname)
          AND (TABLE_NAME  = @tablename)
          AND (COLUMN_NAME = @columnname)
    ) > 0,
    'SELECT 1',
    'ALTER TABLE api_keys
     ADD COLUMN connected_app VARCHAR(100) NULL DEFAULT NULL
         COMMENT ''The external system this key was created for (e.g. Digital ID, Finance System)''
     AFTER name'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;
