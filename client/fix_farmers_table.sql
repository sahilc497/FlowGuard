-- ============================================
-- FIX: Add missing columns to farmers table
-- ============================================

-- Add email column
ALTER TABLE farmers ADD COLUMN IF NOT EXISTS email VARCHAR(255) UNIQUE;

-- Add full_name column
ALTER TABLE farmers ADD COLUMN IF NOT EXISTS full_name VARCHAR(255);

-- Add phone column
ALTER TABLE farmers ADD COLUMN IF NOT EXISTS phone VARCHAR(20);

-- Add role column with default
ALTER TABLE farmers ADD COLUMN IF NOT EXISTS role VARCHAR(20) DEFAULT 'farmer';

-- Add status column with default
ALTER TABLE farmers ADD COLUMN IF NOT EXISTS status VARCHAR(20) DEFAULT 'active';

-- Verify the changes
SELECT column_name, data_type, column_default 
FROM information_schema.columns 
WHERE table_name = 'farmers' 
ORDER BY ordinal_position;
