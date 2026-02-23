-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               8.4.3 - MySQL Community Server - GPL
-- Server OS:                    Win64
-- HeidiSQL Version:             12.8.0.6908
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Dumping database structure for neasaqsd
CREATE DATABASE IF NOT EXISTS `neasaqsd` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `neasaqsd`;

-- Dumping structure for table neasaqsd.accomplishment_reports
CREATE TABLE IF NOT EXISTS `accomplishment_reports` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` int NOT NULL,
  `reporting_period_start` date NOT NULL,
  `reporting_period_end` date NOT NULL,
  `reviewed_by` int DEFAULT NULL,
  `status` enum('draft','submitted','reviewed','approved') DEFAULT 'draft',
  `review_date` datetime DEFAULT NULL,
  `review_comments` text,
  `approved_date` datetime DEFAULT NULL,
  `scanned_file` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `reviewed_by` (`reviewed_by`),
  KEY `idx_employee_period` (`employee_id`,`reporting_period_start`,`reporting_period_end`),
  KEY `idx_status` (`status`),
  KEY `idx_report_employee` (`employee_id`),
  KEY `idx_report_status` (`status`),
  KEY `idx_report_period` (`reporting_period_start`,`reporting_period_end`),
  CONSTRAINT `accomplishment_reports_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  CONSTRAINT `accomplishment_reports_ibfk_2` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.accomplishment_reports: ~2 rows (approximately)
DELETE FROM `accomplishment_reports`;

-- Dumping structure for table neasaqsd.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `entity_type` varchar(100) DEFAULT NULL,
  `entity_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.audit_logs: ~12 rows (approximately)
DELETE FROM `audit_logs`;
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `entity_type`, `entity_id`, `old_values`, `new_values`, `ip_address`, `created_at`) VALUES
	(1, 4, 'UPLOAD', 'storage', 1, NULL, NULL, '127.0.0.1', '2026-02-09 01:00:11'),
	(2, 4, 'CREATE', 'user', 5, NULL, NULL, '127.0.0.1', '2026-02-09 01:03:17'),
	(3, NULL, 'UPLOAD', 'storage', 2, NULL, NULL, '127.0.0.1', '2026-02-09 01:04:40'),
	(4, 4, 'UPDATE_STATUS', 'report', 1, NULL, NULL, '::1', '2026-02-13 05:11:18'),
	(5, 4, 'DELETE', 'user', 2, NULL, NULL, '::1', '2026-02-13 05:22:53'),
	(6, 4, 'CREATE', 'user', 6, NULL, NULL, '::1', '2026-02-13 05:23:31'),
	(7, 6, 'CREATE', 'report', 2, NULL, NULL, '::1', '2026-02-13 05:23:55'),
	(8, 6, 'CREATE', 'project', 2, NULL, NULL, '::1', '2026-02-13 05:24:24'),
	(9, 4, 'UPDATE_STATUS', 'report', 2, NULL, NULL, '::1', '2026-02-13 05:26:09'),
	(10, 4, 'RESET_PASSWORD', 'user', 5, NULL, NULL, '::1', '2026-02-13 05:26:17'),
	(11, 4, 'DELETE', 'user', 3, NULL, NULL, '::1', '2026-02-13 05:26:25'),
	(12, 4, 'RESET_PASSWORD', 'user', 6, NULL, NULL, '::1', '2026-02-13 05:26:37'),
	(13, 4, 'RESET_PASSWORD', 'user', 6, NULL, NULL, '::1', '2026-02-23 02:02:27'),
	(14, 6, 'CREATE', 'report', 3, NULL, NULL, '::1', '2026-02-23 02:02:51'),
	(15, 6, 'DELETE', 'report', 3, NULL, NULL, '::1', '2026-02-23 02:07:30'),
	(16, 6, 'CREATE', 'report', 4, NULL, NULL, '::1', '2026-02-23 02:07:38'),
	(17, 6, 'CREATE', 'project', 3, NULL, NULL, '::1', '2026-02-23 02:08:30'),
	(18, 6, 'UPDATE', 'project', 3, NULL, NULL, '::1', '2026-02-23 02:17:05'),
	(19, 6, 'UPDATE', 'project', 3, NULL, NULL, '::1', '2026-02-23 02:17:28'),
	(20, 6, 'UPDATE', 'project', 3, NULL, NULL, '::1', '2026-02-23 02:21:19'),
	(21, 4, 'RESET_PASSWORD', 'user', 5, NULL, NULL, '::1', '2026-02-23 05:13:44'),
	(22, 4, 'DELETE', 'report', 4, NULL, NULL, '::1', '2026-02-23 05:14:32'),
	(23, 4, 'DELETE', 'report', 2, NULL, NULL, '::1', '2026-02-23 05:14:35'),
	(24, 4, 'DELETE', 'user', 5, NULL, NULL, '::1', '2026-02-23 05:16:18'),
	(25, 4, 'DELETE', 'user', 1, NULL, NULL, '::1', '2026-02-23 05:16:21');

-- Dumping structure for table neasaqsd.categories
CREATE TABLE IF NOT EXISTS `categories` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.categories: ~7 rows (approximately)
DELETE FROM `categories`;
INSERT INTO `categories` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'Document', NULL, '2026-02-09 00:46:39', '2026-02-09 00:46:39'),
	(2, 'Report', NULL, '2026-02-09 00:46:39', '2026-02-09 00:46:39'),
	(3, 'Template', NULL, '2026-02-09 00:46:39', '2026-02-09 00:46:39'),
	(4, 'Policy', NULL, '2026-02-09 00:46:39', '2026-02-09 00:46:39'),
	(5, 'Trainings', NULL, '2026-02-09 00:46:39', '2026-02-09 00:57:09'),
	(6, 'Other', NULL, '2026-02-09 00:46:39', '2026-02-09 00:46:39'),
	(19, 'TEST INSERTION', NULL, '2026-02-09 01:00:11', '2026-02-09 01:00:11'),
	(20, 'TEST INSERTION from EMPLOYEE', NULL, '2026-02-09 01:04:40', '2026-02-09 01:04:40');

-- Dumping structure for table neasaqsd.database_storage
CREATE TABLE IF NOT EXISTS `database_storage` (
  `id` int NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_size` int NOT NULL,
  `uploaded_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `database_storage_ibfk_1` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.database_storage: ~2 rows (approximately)
DELETE FROM `database_storage`;
INSERT INTO `database_storage` (`id`, `title`, `category`, `file_name`, `file_size`, `uploaded_by`, `created_at`, `updated_at`) VALUES
	(1, 'Test OTHERS Implementation for the INSERTION into the DATABASE ', 'TEST INSERTION', '1770598811_e5f7cd14-ddcb-4ec4-94d3-0c5436d09da0.jpg', 130253, 4, '2026-02-09 01:00:11', '2026-02-09 01:00:11');

-- Dumping structure for table neasaqsd.performance_ratings
CREATE TABLE IF NOT EXISTS `performance_ratings` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `overall_quality` int DEFAULT NULL,
  `overall_efficiency` int DEFAULT NULL,
  `overall_timeliness` int DEFAULT NULL,
  `average_rating` decimal(3,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_report_rating` (`report_id`),
  CONSTRAINT `performance_ratings_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `accomplishment_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.performance_ratings: ~0 rows (approximately)
DELETE FROM `performance_ratings`;

-- Dumping structure for table neasaqsd.projects_activities
CREATE TABLE IF NOT EXISTS `projects_activities` (
  `id` int NOT NULL AUTO_INCREMENT,
  `report_id` int NOT NULL,
  `project_name` varchar(255) NOT NULL,
  `description` text,
  `success_indicators` text,
  `actual_accomplishment` text,
  `quantity` int DEFAULT NULL,
  `efficiency` int DEFAULT NULL COMMENT 'Rating 1-5 or percentage',
  `timeliness` int DEFAULT NULL COMMENT 'Rating 1-5 or percentage',
  `quality` int DEFAULT NULL COMMENT 'Rating 1-5 or percentage',
  `remarks` text,
  `attachment` varchar(500) DEFAULT NULL COMMENT 'File path for uploaded attachment',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_report_id` (`report_id`),
  CONSTRAINT `projects_activities_ibfk_1` FOREIGN KEY (`report_id`) REFERENCES `accomplishment_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.projects_activities: ~0 rows (approximately)
DELETE FROM `projects_activities`;

-- Dumping structure for table neasaqsd.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employee_id` varchar(50) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `supervisor_id` int DEFAULT NULL,
  `role` enum('employee','supervisor','admin') DEFAULT 'employee',
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `profile_photo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `employee_id` (`employee_id`),
  UNIQUE KEY `email` (`email`),
  KEY `supervisor_id` (`supervisor_id`),
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`supervisor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Dumping data for table neasaqsd.users: ~4 rows (approximately)
DELETE FROM `users`;
INSERT INTO `users` (`id`, `employee_id`, `first_name`, `last_name`, `email`, `department`, `position`, `supervisor_id`, `role`, `password`, `created_at`, `updated_at`, `profile_photo`) VALUES
	(4, 'admin', 'System', 'Administrator', 'admin@saqsd.com', 'Administration', 'System Administrator', NULL, 'admin', '$2y$10$jyFhAaflVCJkZ4cqA/xhvOHW9TxUSGRw1jyHu4hezc.92dnbrQOd6', '2026-02-06 08:36:06', '2026-02-06 08:36:35', 'profile_4_1770366995.jpg'),
	(6, 'josh', 'Josh McDowell', 'Trapal', 'joshmcdowelltrapal@gmail.com', 'Systems Audit and Quality Standards Division', 'Senior Internal Control Officer B', NULL, 'employee', '$2y$10$yrOluxLWA4m3t22OoT3NQ.n8iV1bGdaNRfcqwa3O24/r/oiNSN57O', '2026-02-13 05:23:31', '2026-02-23 02:02:27', NULL);

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
