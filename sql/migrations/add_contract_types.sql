-- Add contract type and special employment status fields to staff_profiles

ALTER TABLE staff_profiles 
ADD COLUMN contract_type VARCHAR(50) NULL COMMENT 'Contract type: permanent, fixed_term, zero_hours, bank, apprentice, agency, etc.',
ADD COLUMN is_bank_staff BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is bank/casual staff',
ADD COLUMN is_apprentice BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member is an apprentice',
ADD COLUMN has_visa BOOLEAN DEFAULT FALSE COMMENT 'Indicates if staff member requires a visa',
ADD COLUMN visa_type VARCHAR(100) NULL COMMENT 'Type of visa (e.g., Tier 2, Skilled Worker, etc.)',
ADD COLUMN visa_number VARCHAR(100) NULL COMMENT 'Visa reference number',
ADD COLUMN visa_issue_date DATE NULL COMMENT 'Visa issue date',
ADD COLUMN visa_expiry_date DATE NULL COMMENT 'Visa expiry date',
ADD COLUMN visa_sponsor VARCHAR(255) NULL COMMENT 'Visa sponsor organisation',
ADD COLUMN apprenticeship_start_date DATE NULL COMMENT 'Apprenticeship start date',
ADD COLUMN apprenticeship_end_date DATE NULL COMMENT 'Apprenticeship expected end date',
ADD COLUMN apprenticeship_level VARCHAR(50) NULL COMMENT 'Apprenticeship level (e.g., Level 2, Level 3, etc.)',
ADD COLUMN apprenticeship_provider VARCHAR(255) NULL COMMENT 'Apprenticeship training provider';

-- Create indexes for contract type queries
ALTER TABLE staff_profiles ADD INDEX idx_contract_type (contract_type);
ALTER TABLE staff_profiles ADD INDEX idx_bank_staff (is_bank_staff);
ALTER TABLE staff_profiles ADD INDEX idx_visa_expiry (visa_expiry_date);
ALTER TABLE staff_profiles ADD INDEX idx_apprenticeship (is_apprentice);

