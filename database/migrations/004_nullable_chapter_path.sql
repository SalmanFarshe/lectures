-- Migration 004: allow manually created chapters without a scanned PHP file.
-- Chapters created by Admin for DB-backed notes have no file on disk, so
-- file_path must be nullable. The UNIQUE constraint is kept, which still
-- prevents duplicate scanned paths; MySQL permits multiple NULLs.
-- Existing scanned rows are untouched.
ALTER TABLE chapters MODIFY file_path VARCHAR(500) NULL;
