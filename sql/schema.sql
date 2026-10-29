-- ===================================================
-- VISITOR LOG MANAGEMENT SYSTEM (VLMS) DATABASE SCHEMA
-- ===================================================

CREATE DATABASE IF NOT EXISTS `vlms_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `vlms_db`;

-- Drop existing tables if recreating
DROP TABLE IF EXISTS `visitor_logs`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

-- 1. Users Table
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `phone` VARCHAR(30) NULL,
    `role` ENUM('admin', 'security', 'receptionist') NOT NULL DEFAULT 'receptionist',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `avatar` VARCHAR(255) NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `last_login` DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. System Settings Table
CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(60) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Visitor Logs Table
CREATE TABLE `visitor_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `badge_number` VARCHAR(30) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `contact_number` VARCHAR(30) NOT NULL,
    `email` VARCHAR(100) NULL,
    `company_or_address` VARCHAR(150) NULL,
    `id_type` VARCHAR(50) NULL,
    `id_number` VARCHAR(50) NULL,
    `purpose_of_visit` VARCHAR(100) NOT NULL,
    `person_to_visit` VARCHAR(100) NOT NULL,
    `department_to_visit` VARCHAR(100) NULL,
    `time_in` DATETIME NOT NULL,
    `time_out` DATETIME NULL,
    `status` ENUM('Inside', 'Completed') NOT NULL DEFAULT 'Inside',
    `remarks` TEXT NULL,
    `registered_by` INT NULL,
    `checked_out_by` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_time_in` (`time_in`),
    INDEX `idx_full_name` (`full_name`),
    INDEX `idx_purpose` (`purpose_of_visit`),
    CONSTRAINT `fk_reg_user` FOREIGN KEY (`registered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_out_user` FOREIGN KEY (`checked_out_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================================================
-- SEED DATA
-- ===================================================

-- Initial Users (Password for all is <role>123 e.g., admin123, security123, reception123)
INSERT INTO `users` (`username`, `password_hash`, `full_name`, `email`, `phone`, `role`, `status`) VALUES
('admin', 'James12', 'James Edward (Chief Administrator)', 'admin@aegiscenter.com', '+1 (555) 012-3456', 'admin', 'active'),
('security', 'Edward12', 'Edward Ravelo (Head of Security)', 'security@aegiscenter.com', '+1 (555) 012-7890', 'security', 'active'),
('receptionist', 'Ravelo12', 'James Ravelo (Lead Receptionist)', 'reception@aegiscenter.com', '+1 (555) 012-4411', 'receptionist', 'active');

-- Initial System Settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`) VALUES
('facility_name', 'Aegis Corporate Center & Technology Park'),
('facility_address', 'Tower 4, Silicon Boulevard, Innovation District, CA 94025'),
('contact_phone', '+1 (555) 019-2834'),
('contact_email', 'security@aegiscenter.com'),
('badge_prefix', 'VIS'),
('max_visitor_capacity', '250'),
('operating_hours', '07:00 AM - 09:00 PM'),
('require_id', '1'),
('auto_refresh_seconds', '30');
