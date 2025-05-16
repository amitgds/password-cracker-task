CREATE DATABASE IF NOT EXISTS password_cracker;
USE password_cracker;

CREATE TABLE IF NOT EXISTS not_so_smart_users (
    user_id INT PRIMARY KEY,
    password VARCHAR(32) NOT NULL
);

CREATE TABLE IF NOT EXISTS password_hashes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    password VARCHAR(6) NOT NULL,
    hash VARCHAR(32) NOT NULL,
    type ENUM('medium_lowercase', 'hard_mixed') NOT NULL,
    INDEX idx_hash (hash),
    INDEX idx_type (type)
);