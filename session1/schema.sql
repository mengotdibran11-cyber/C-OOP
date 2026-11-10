CREATE DATABASE IF NOT EXISTS php_labs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_labs;

CREATE TABLE IF NOT EXISTS students (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(80)  NOT NULL,
    email           VARCHAR(150) NOT NULL,
    telephone       CHAR(9)      NOT NULL,
    date_of_birth   DATE         NOT NULL,
    programme       VARCHAR(60)  NOT NULL,
    level           VARCHAR(20)  NOT NULL,
    gender          VARCHAR(10)  NULL,
    policy_accepted TINYINT(1)   NOT NULL DEFAULT 0,
    registered_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_students_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
