-- Add national_id and national_id_photo columns to workers table
-- Run this SQL command in PostgreSQL with appropriate privileges

ALTER TABLE workers ADD COLUMN IF NOT EXISTS national_id VARCHAR(50);
ALTER TABLE workers ADD COLUMN IF NOT EXISTS national_id_photo VARCHAR(255);

-- Verify columns were added
SELECT column_name, data_type 
FROM information_schema.columns 
WHERE table_name = 'workers' 
AND column_name IN ('national_id', 'national_id_photo');
