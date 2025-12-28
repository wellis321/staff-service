-- Add leave management fields to staff_profiles
-- Annual leave allocation, usage, and carry-over tracking

ALTER TABLE staff_profiles 
ADD COLUMN annual_leave_allocation DECIMAL(5,2) NULL COMMENT 'Annual leave allocation in days from job post',
ADD COLUMN annual_leave_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave used in days',
ADD COLUMN annual_leave_carry_over DECIMAL(5,2) DEFAULT 0 COMMENT 'Annual leave carried over from previous year',
ADD COLUMN time_in_lieu_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours accrued',
ADD COLUMN time_in_lieu_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Time in lieu hours used',
ADD COLUMN lying_time_hours DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours accrued',
ADD COLUMN lying_time_used DECIMAL(5,2) DEFAULT 0 COMMENT 'Lying time hours used',
ADD COLUMN leave_year_start_date DATE NULL COMMENT 'Start date of current leave year',
ADD COLUMN leave_year_end_date DATE NULL COMMENT 'End date of current leave year';

-- Create index for leave year queries
ALTER TABLE staff_profiles ADD INDEX idx_leave_year (leave_year_start_date, leave_year_end_date);

