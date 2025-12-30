-- Combined Migration: Add all missing profile fields to staff_profiles
-- This includes TUPE, WTD, contract types, visa, and apprenticeship fields
-- Run this on production to restore full profile functionality
--
-- NOTE: MySQL doesn't support IF NOT EXISTS for ADD COLUMN.
-- If a column already exists, you'll get an error - just ignore it and continue.
-- Alternatively, check each column first using: SHOW COLUMNS FROM staff_profiles LIKE 'column_name';

-- ============================================================================
-- PART 1: TUPE (Transfer of Undertakings Protection of Employment) Support
-- ============================================================================

ALTER TABLE staff_profiles 
ADD COLUMN is_tupe BOOLEAN DEFAULT FALSE COMMENT 'Whether this staff member has a TUPE contract';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_transfer_date DATE NULL COMMENT 'Date of TUPE transfer';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_previous_organisation VARCHAR(255) NULL COMMENT 'Previous organisation name';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_previous_employer_ref VARCHAR(100) NULL COMMENT 'Reference/ID from previous employer';

-- TUPE override fields (these override job post terms)
ALTER TABLE staff_profiles 
ADD COLUMN tupe_contract_type VARCHAR(50) NULL COMMENT 'Contract type under TUPE (overrides job post)';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_hours_per_week DECIMAL(5,2) NULL COMMENT 'Hours per week under TUPE (overrides job post)';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_salary DECIMAL(10,2) NULL COMMENT 'Salary under TUPE (overrides job post)';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_salary_currency VARCHAR(3) DEFAULT 'GBP' COMMENT 'Currency for TUPE salary';

ALTER TABLE staff_profiles 
ADD COLUMN tupe_notes TEXT NULL COMMENT 'Additional TUPE-related notes';

-- Add index for TUPE queries (only if it doesn't exist)
-- Note: MySQL doesn't support IF NOT EXISTS for indexes, so check manually or ignore errors
ALTER TABLE staff_profiles ADD INDEX idx_is_tupe (is_tupe);

-- ============================================================================
-- PART 2: Working Time Directive (WTD) Support
-- ============================================================================

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
ALTER TABLE staff_profiles ADD INDEX idx_wtd_agreed (wtd_agreed);
ALTER TABLE staff_profiles ADD INDEX idx_wtd_opt_out (wtd_opt_out);
ALTER TABLE staff_profiles ADD INDEX idx_wtd_opt_out_expiry (wtd_opt_out_expiry_date);

-- ============================================================================
-- PART 3: Contract Types, Visa, and Apprenticeship Support
-- ============================================================================

ALTER TABLE staff_profiles 
ADD COLUMN contract_type VARCHAR(50) NULL COMMENT 'Contract type: permanent, fixed_term, zero_hours, bank, apprentice, agency, etc.';

ALTER TABLE staff_profiles 
ADD COLUMN is_bank_staff BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is bank/casual staff';

ALTER TABLE staff_profiles 
ADD COLUMN is_apprentice BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is an apprentice';

ALTER TABLE staff_profiles 
ADD COLUMN has_visa BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member requires a visa';

ALTER TABLE staff_profiles 
ADD COLUMN visa_type VARCHAR(100) NULL COMMENT 'Type of visa (e.g., Tier 2, Skilled Worker, etc.)';

ALTER TABLE staff_profiles 
ADD COLUMN visa_number VARCHAR(100) NULL COMMENT 'Visa reference number';

ALTER TABLE staff_profiles 
ADD COLUMN visa_issue_date DATE NULL COMMENT 'Visa issue date';

ALTER TABLE staff_profiles 
ADD COLUMN visa_expiry_date DATE NULL COMMENT 'Visa expiry date';

ALTER TABLE staff_profiles 
ADD COLUMN visa_sponsor VARCHAR(255) NULL COMMENT 'Visa sponsor organisation';

ALTER TABLE staff_profiles 
ADD COLUMN apprenticeship_start_date DATE NULL COMMENT 'Apprenticeship start date';

ALTER TABLE staff_profiles 
ADD COLUMN apprenticeship_end_date DATE NULL COMMENT 'Apprenticeship expected end date';

ALTER TABLE staff_profiles 
ADD COLUMN apprenticeship_level VARCHAR(50) NULL COMMENT 'Apprenticeship level (e.g., Level 2, Level 3, etc.)';

ALTER TABLE staff_profiles 
ADD COLUMN apprenticeship_provider VARCHAR(255) NULL COMMENT 'Apprenticeship training provider';

-- Create indexes for contract type queries
ALTER TABLE staff_profiles ADD INDEX idx_contract_type (contract_type);
ALTER TABLE staff_profiles ADD INDEX idx_bank_staff (is_bank_staff);
ALTER TABLE staff_profiles ADD INDEX idx_visa_expiry (visa_expiry_date);
ALTER TABLE staff_profiles ADD INDEX idx_apprenticeship (is_apprentice);

