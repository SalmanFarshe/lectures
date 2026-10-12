-- Migration 005: account_requests
-- Stores public registration requests and password-reset requests submitted
-- from the login page. An admin reviews (approve/reject) them on the Students
-- page. Approving a registration creates the student account; approving a
-- reset applies the requested password hash to the existing account.

CREATE TABLE IF NOT EXISTS account_requests (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    type ENUM('register','password_reset') NOT NULL DEFAULT 'register',
    full_name VARCHAR(120) DEFAULT NULL,
    username VARCHAR(50) NOT NULL,
    class_id INT UNSIGNED DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    admin_note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,
    reviewed_by INT UNSIGNED DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_ar_status (status),
    KEY idx_ar_username (username),
    KEY idx_ar_class (class_id),
    CONSTRAINT fk_ar_class FOREIGN KEY (class_id)
        REFERENCES classes (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
