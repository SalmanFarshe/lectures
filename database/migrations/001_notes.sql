-- Migration 001: Database-backed notes
-- Stores lecture notes in MySQL. A chapter can have multiple notes.
-- Each note is either pasted/typed HTML (type=html) or an uploaded PDF (type=pdf).
-- Original PHP note files remain on disk until migration is verified.

CREATE TABLE IF NOT EXISTS notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chapter_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    content_type ENUM('html','pdf') NOT NULL DEFAULT 'html',
    -- For type=html: sanitized HTML body (CSS/JS/MathJax preserved).
    html_content MEDIUMTEXT NULL,
    -- For type=pdf: relative path under uploads/notes/ (e.g. uploads/notes/xyz.pdf).
    pdf_path VARCHAR(500) NULL,
    -- Optional original scanned PHP file reference (kept for migration audit).
    source_file VARCHAR(500) NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_note_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters(id)
        ON DELETE CASCADE,
    INDEX idx_notes_chapter (chapter_id, is_active),
    INDEX idx_notes_sort (chapter_id, sort_order)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
