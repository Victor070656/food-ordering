-- Add screenshot column to payments table
ALTER TABLE payments ADD COLUMN screenshot VARCHAR(255) DEFAULT NULL AFTER notes;

-- Add index for faster queries
ALTER TABLE payments ADD INDEX idx_screenshot (screenshot);
