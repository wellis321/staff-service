-- Registration notification tracking + renewal_submitted_at column
-- Run AFTER the staff_registrations table already exists
-- (created via public/migrate-staff-registrations.php).
--
-- Note: uses INFORMATION_SCHEMA pattern for ADD COLUMN because MySQL (unlike
-- MariaDB) does not support ALTER TABLE ... ADD COLUMN IF NOT EXISTS.

-- Track when staff submitted their renewal application to the registering body
SET @dbname = DATABASE();
SET @preparedStatement = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname
       AND TABLE_NAME   = 'staff_registrations'
       AND COLUMN_NAME  = 'renewal_submitted_at') > 0,
    'SELECT 1 -- column already exists',
    'ALTER TABLE staff_registrations
     ADD COLUMN renewal_submitted_at DATE NULL
         COMMENT ''Date the renewal was submitted to the registering body''
         AFTER renewal_date'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Notification log: one row per (registration, threshold, recipient).
-- Prevents duplicate emails if the cron runs more than once per day.
-- threshold_key: 90/60/30/14/7/0 = days before expiry; -7/-14/-21/-28 = days after.
CREATE TABLE IF NOT EXISTS registration_notifications (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    registration_id  INT          NOT NULL,
    threshold_key    INT          NOT NULL,
    recipient_type   ENUM('staff','manager','org_admin') NOT NULL,
    recipient_email  VARCHAR(255) NOT NULL,
    sent_at          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (registration_id) REFERENCES staff_registrations(id) ON DELETE CASCADE,
    UNIQUE  KEY unique_notification (registration_id, threshold_key, recipient_type),
    INDEX idx_registration (registration_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
