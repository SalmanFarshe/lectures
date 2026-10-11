-- Migration 002: Question Bank
-- Stores reusable questions tied to Class/Subject/Chapter.
-- Supports MCQ (with options + correct answer) and written (short/essay) types.

CREATE TABLE IF NOT EXISTS questions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NULL,
    chapter_id INT UNSIGNED NULL,
    question_type ENUM('mcq','written') NOT NULL DEFAULT 'mcq',
    question_text MEDIUMTEXT NOT NULL,
    -- MCQ options (A-D). NULL for written questions.
    option_a VARCHAR(500) NULL,
    option_b VARCHAR(500) NULL,
    option_c VARCHAR(500) NULL,
    option_d VARCHAR(500) NULL,
    -- For MCQ: 'a'|'b'|'c'|'d'. For written: model/acceptable answer reference.
    correct_answer VARCHAR(500) NULL,
    -- Marks for this question (objective auto-scored; written needs manual grade).
    marks DECIMAL(6,2) NOT NULL DEFAULT 1.00,
    explanation TEXT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_question_class
        FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_question_subject
        FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    CONSTRAINT fk_question_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters(id) ON DELETE SET NULL,
    CONSTRAINT fk_question_author
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_questions_scope (class_id, subject_id, chapter_id),
    INDEX idx_questions_type (question_type, is_active)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
