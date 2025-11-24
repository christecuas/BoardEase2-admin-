-- Migration script to add '4_days_before' to existing payment_reminder_logs table
-- Run this if you already created the table without the 4_days_before option

-- First, check if the table exists and needs updating
-- This will modify the enum to include '4_days_before'

ALTER TABLE `payment_reminder_logs` 
MODIFY COLUMN `reminder_type` enum('5_days_before','4_days_before','3_days_before','2_days_before','1_day_before','due_date') NOT NULL COMMENT 'Type of reminder';


