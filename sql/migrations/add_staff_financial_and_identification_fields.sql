-- Migration: Add financial and identification fields to staff_profiles
-- These fields allow staff to view and update their own information
-- Note: If a column already exists, you'll get an error - that's okay, just skip that command

ALTER TABLE staff_profiles 
ADD COLUMN ni_number VARCHAR(20) NULL COMMENT 'National Insurance number (UK)';

ALTER TABLE staff_profiles 
ADD COLUMN bank_sort_code VARCHAR(10) NULL COMMENT 'Bank sort code';

ALTER TABLE staff_profiles 
ADD COLUMN bank_account_number VARCHAR(20) NULL COMMENT 'Bank account number';

ALTER TABLE staff_profiles 
ADD COLUMN bank_account_name VARCHAR(255) NULL COMMENT 'Account holder name';

ALTER TABLE staff_profiles 
ADD COLUMN address_line1 VARCHAR(255) NULL COMMENT 'Address line 1';

ALTER TABLE staff_profiles 
ADD COLUMN address_line2 VARCHAR(255) NULL COMMENT 'Address line 2';

ALTER TABLE staff_profiles 
ADD COLUMN address_city VARCHAR(100) NULL COMMENT 'City/Town';

ALTER TABLE staff_profiles 
ADD COLUMN address_county VARCHAR(100) NULL COMMENT 'County/State';

ALTER TABLE staff_profiles 
ADD COLUMN address_postcode VARCHAR(20) NULL COMMENT 'Postcode/ZIP';

ALTER TABLE staff_profiles 
ADD COLUMN address_country VARCHAR(100) NULL DEFAULT 'United Kingdom' COMMENT 'Country';

-- Add index for NI number lookups (if it doesn't exist, you may get an error - that's okay)
ALTER TABLE staff_profiles ADD INDEX idx_ni_number (ni_number);

