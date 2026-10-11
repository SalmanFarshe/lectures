-- Migration 003: Quiz Generator
-- Quizzes reference Question Bank questions, can be assigned to students or
-- groups (by class), and track attempts/answers/scores with availability and
-- attempt limits.

CREATE TABLE IF NOT EXISTS quizzes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    class_id INT UNSIGNED NOT NULL,
    subject_id INT UNSIGNED NULL,
    instructions TEXT NULL,
    duration_minutes INT UNSIGNED NOT NULL DEFAULT 30,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    -- Availability window (NULL = open-ended).
    available_from DATETIME NULL,
    available_until DATETIME NULL,
    max_attempts TINYINT UNSIGNED NOT NULL DEFAULT 1,
    -- 1 = show result/score right after submit; 0 = admin publishes later.
    show_result TINYINT(1) NOT NULL DEFAULT 1,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_quiz_class FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    CONSTRAINT fk_quiz_subject FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE SET NULL,
    CONSTRAINT fk_quiz_author FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_quiz_class (class_id, is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_questions (
    quiz_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    position INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (quiz_id, question_id),
    CONSTRAINT fk_qq_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_qq_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Assignments: an assignment is either to a single student (user_id) or to a
-- whole group/class (group_class_id) when not targeted individually.
CREATE TABLE IF NOT EXISTS quiz_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NULL,
    group_class_id INT UNSIGNED NULL,
    assigned_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_qa_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_qa_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_qa_group FOREIGN KEY (group_class_id) REFERENCES classes(id) ON DELETE CASCADE,
    INDEX idx_qa_quiz_user (quiz_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    attempt_number TINYINT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('in_progress','submitted','graded') NOT NULL DEFAULT 'in_progress',
    started_at DATETIME NOT NULL,
    submitted_at DATETIME NULL,
    total_score DECIMAL(6,2) NOT NULL DEFAULT 0,
    total_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    INDEX idx_attempt_quiz_user (quiz_id, user_id),
    CONSTRAINT fk_att_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    CONSTRAINT fk_att_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT UNSIGNED NOT NULL,
    question_id INT UNSIGNED NOT NULL,
    answer_text TEXT NULL,
    awarded_marks DECIMAL(6,2) NOT NULL DEFAULT 0,
    is_correct TINYINT(1) NULL,
    UNIQUE KEY uq_answer (attempt_id, question_id),
    CONSTRAINT fk_ans_attempt FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    CONSTRAINT fk_ans_question FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
