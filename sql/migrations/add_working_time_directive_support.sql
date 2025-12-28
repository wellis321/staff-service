-- Migration: Add Working Time Directive (WTD) agreement support
-- Tracks whether staff have agreed to WTD and opt-out status

-- Add WTD agreement fields to staff_profiles
ALTER TABLE staff_profiles 
ADD COLUMN wtd_agreed BOOLEAN DEFAULT FALSE COMMENT 'Whether staff member has agreed to Working Time Directive';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_agreement_date DATE NULL COMMENT 'Date when WTD agreement was signed';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_agreement_version VARCHAR(50) NULL COMMENT 'Version of WTD agreement document';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_opt_out BOOLEAN DEFAULT FALSE COMMENT 'Whether staff member has opted out of 48-hour week limit';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_opt_out_date DATE NULL COMMENT 'Date when opt-out was signed';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_opt_out_expiry_date DATE NULL COMMENT 'Date when opt-out expires (if applicable)';

ALTER TABLE staff_profiles 
ADD COLUMN wtd_notes TEXT NULL COMMENT 'Additional notes about WTD agreement';

-- Add indexes for WTD queries
ALTER TABLE staff_profiles 
ADD INDEX idx_wtd_agreed (wtd_agreed);

ALTER TABLE staff_profiles 
ADD INDEX idx_wtd_opt_out (wtd_opt_out);

ALTER TABLE staff_profiles 
ADD INDEX idx_wtd_opt_out_expiry (wtd_opt_out_expiry_date);

