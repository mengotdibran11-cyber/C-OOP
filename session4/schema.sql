CREATE DATABASE IF NOT EXISTS php_labs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE php_labs;

CREATE TABLE IF NOT EXISTS products (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    name         VARCHAR(100)  NOT NULL,
    sku          VARCHAR(40)   NOT NULL,
    category     VARCHAR(40)   NOT NULL,
    unit_price   DECIMAL(12,2) NOT NULL,
    quantity     INT UNSIGNED  NOT NULL DEFAULT 0,
    description  VARCHAR(1000) NULL,
    image_path   VARCHAR(60)   NULL,
    is_available TINYINT(1)    NOT NULL DEFAULT 0,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    CONSTRAINT chk_products_price CHECK (unit_price > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
