-- Add MOA document columns to work_schedules table
-- This allows HR to upload MOA documents when creating work schedules

ALTER TABLE `work_schedules` 
ADD COLUMN `moa_document_path` VARCHAR(500) DEFAULT NULL AFTER `formatted_schedule`,
ADD COLUMN `moa_document_name` VARCHAR(255) DEFAULT NULL AFTER `moa_document_path`;
