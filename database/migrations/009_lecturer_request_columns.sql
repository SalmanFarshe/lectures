-- Migration 009: Add lecturer-specific fields to account_requests
-- These columns are nullable and only populated when type = 'register' and the
-- applicant is registering as a lecturer. No data is dropped or modified.

ALTER TABLE account_requests
    ADD COLUMN employee_id VARCHAR(50) NULL AFTER class_id,
    ADD COLUMN department VARCHAR(100) NULL AFTER employee_id,
    ADD COLUMN qualification VARCHAR(200) NULL AFTER department;