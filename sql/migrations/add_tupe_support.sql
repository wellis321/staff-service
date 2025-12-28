-- Migration: Add TUPE (Transfer of Undertakings Protection of Employment) support
-- TUPE applies when staff transfer between organisations and their original terms must be preserved

-- Add TUPE fields to staff_profiles
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

-- Add index for TUPE queries
ALTER TABLE staff_profiles 
ADD INDEX idx_is_tupe (is_tupe);

