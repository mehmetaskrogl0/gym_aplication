CREATE DATABASE IF NOT EXISTS fitbalance;
USE fitbalance;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS calorie_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    meal_name VARCHAR(120) NOT NULL,
    calories INT UNSIGNED NOT NULL,
    notes VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_calorie_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user_log_date (user_id, log_date)
);

-- User Profiles Table for Physical Assessment
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    gender ENUM('male', 'female', 'other') NOT NULL,
    age INT UNSIGNED NOT NULL,
    height_cm DECIMAL(5, 2) NOT NULL COMMENT 'Height in centimeters',
    weight_kg DECIMAL(5, 2) NOT NULL COMMENT 'Weight in kilograms',
    body_fat_percentage DECIMAL(5, 2) NULL COMMENT 'Body fat percentage',
    target_physique ENUM('lean', 'athletic', 'bodybuilder', 'endurance') DEFAULT 'athletic',
    target_muscles JSON NULL COMMENT 'Selected target muscle groups as JSON array',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_user_profiles_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
);

-- Physique Logs Table for Progress Tracking
CREATE TABLE IF NOT EXISTS physique_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    log_date DATE NOT NULL,
    weight_kg DECIMAL(5, 2) NOT NULL,
    chest_cm DECIMAL(5, 2) NULL,
    waist_cm DECIMAL(5, 2) NULL,
    bicep_cm DECIMAL(5, 2) NULL,
    thigh_cm DECIMAL(5, 2) NULL,
    body_fat_percentage DECIMAL(5, 2) NULL,
    photo_front_path VARCHAR(255) NULL COMMENT 'Front view photo path',
    photo_side_path VARCHAR(255) NULL COMMENT 'Side view photo path',
    photo_back_path VARCHAR(255) NULL COMMENT 'Back view photo path',
    notes VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_physique_logs_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_user_log_date (user_id, log_date),
    UNIQUE KEY unique_user_log_date (user_id, log_date)
);
