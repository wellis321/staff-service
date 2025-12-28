-- Add indexes to staff_learning_records for efficient employee reference searches
-- This allows learning records to be searched by employee_reference through joins with people table

-- Composite index for organisation and person_id
-- This index helps with queries that filter by organisation and person_id together
-- Note: If the index already exists, you'll get an error - that's fine, just ignore it
-- To check if index exists: SHOW INDEX FROM staff_learning_records WHERE Key_name = 'idx_org_person';

CREATE INDEX idx_org_person ON staff_learning_records(organisation_id, person_id);

-- Ensure people table has index on employee_reference (should already exist from schema)
-- This is verified in the main schema, but we'll document it here
-- The schema already has: INDEX idx_employee_reference (employee_reference)

