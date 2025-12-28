-- Migration: Add employment details fields to staff_profiles
-- These fields include contracted hours, place of work, and job description references

ALTER TABLE staff_profiles 
ADD COLUMN contracted_hours DECIMAL(5,2) NULL COMMENT 'Contracted hours per week';

ALTER TABLE staff_profiles 
ADD COLUMN place_of_work VARCHAR(255) NULL COMMENT 'Primary place of work/location';

ALTER TABLE staff_profiles 
ADD COLUMN job_description_id INT NULL COMMENT 'Reference to job_descriptions table';

ALTER TABLE staff_profiles 
ADD COLUMN external_job_description_url VARCHAR(500) NULL COMMENT 'URL to job description in external system';

ALTER TABLE staff_profiles 
ADD COLUMN external_job_description_ref VARCHAR(100) NULL COMMENT 'Reference ID for job description in external system';

-- Add foreign key for job_description_id (will be added after job_descriptions table is created)
-- ALTER TABLE staff_profiles ADD CONSTRAINT fk_job_description FOREIGN KEY (job_description_id) REFERENCES job_descriptions(id) ON DELETE SET NULL;

