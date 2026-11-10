CREATE DATABASE IF NOT EXISTS php_labs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_labs;

CREATE TABLE IF NOT EXISTS appointments (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    patient_name     VARCHAR(80)  NOT NULL,
    email            VARCHAR(150) NULL,
    phone            CHAR(9)      NOT NULL,
    department       VARCHAR(40)  NOT NULL,
    doctor           VARCHAR(60)  NOT NULL,
    appointment_date DATE         NOT NULL,
    time_slot        VARCHAR(11)  NOT NULL,
    reason           VARCHAR(500) NOT NULL,
    consent_given    TINYINT(1)   NOT NULL DEFAULT 0,
    status           VARCHAR(12)  NOT NULL DEFAULT 'Pending',
    created_at       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_doctor_date_slot (doctor, appointment_date, time_slot)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
