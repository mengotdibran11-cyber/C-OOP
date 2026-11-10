CREATE DATABASE IF NOT EXISTS php_labs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_labs;

CREATE TABLE IF NOT EXISTS loans (
    loan_id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    student_id     VARCHAR(20)  NOT NULL,
    isbn           VARCHAR(13)  NOT NULL,
    book_title     VARCHAR(200) NOT NULL,
    borrow_date    DATE         NOT NULL,
    due_date       DATE         NOT NULL,
    loan_days      SMALLINT UNSIGNED NOT NULL,
    purpose        VARCHAR(255) NULL,
    rules_accepted TINYINT(1)   NOT NULL DEFAULT 0,
    status         VARCHAR(10)  NOT NULL DEFAULT 'Active',
    active_flag    TINYINT      NULL DEFAULT 1,
    created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (loan_id),
    UNIQUE KEY uq_loans_one_active (student_id, isbn, active_flag),
    CONSTRAINT chk_loans_dates CHECK (due_date > borrow_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
