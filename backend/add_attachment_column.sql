-- Add attachment column to projects_activities table
-- Run this script to enable file attachments for accomplishment reports

ALTER TABLE projects_activities 
ADD COLUMN IF NOT EXISTS attachment VARCHAR(500) NULL AFTER remarks;

-- If the above doesn't work (older MySQL), use this:
-- ALTER TABLE projects_activities ADD COLUMN attachment VARCHAR(500) NULL AFTER remarks;
