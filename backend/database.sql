-- Create Database
CREATE DATABASE IF NOT EXISTS accomplishment_report_system;
USE accomplishment_report_system;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id VARCHAR(50) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    department VARCHAR(100) NOT NULL,
    position VARCHAR(100) NOT NULL,
    supervisor_id INT,
    role ENUM('employee', 'supervisor', 'admin') DEFAULT 'employee',
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (supervisor_id) REFERENCES users(id)
);

-- Accomplishment Reports Table
CREATE TABLE IF NOT EXISTS accomplishment_reports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    reporting_period_start DATE NOT NULL,
    reporting_period_end DATE NOT NULL,
    reviewed_by INT,
    status ENUM('draft', 'submitted', 'reviewed', 'approved') DEFAULT 'draft',
    review_date DATETIME,
    review_comments TEXT,
    approved_date DATETIME,
    scanned_file VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES users(id),
    FOREIGN KEY (reviewed_by) REFERENCES users(id),
    INDEX idx_employee_period (employee_id, reporting_period_start, reporting_period_end),
    INDEX idx_status (status)
);

-- Projects/Activities Table
CREATE TABLE IF NOT EXISTS projects_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    project_name VARCHAR(255) NOT NULL,
    description TEXT,
    success_indicators TEXT,
    actual_accomplishment TEXT,
    quantity INT,
    efficiency INT COMMENT 'Rating 1-5 or percentage',
    timeliness INT COMMENT 'Rating 1-5 or percentage',
    quality INT COMMENT 'Rating 1-5 or percentage',
    remarks TEXT,
    attachment VARCHAR(500) NULL COMMENT 'File path for uploaded attachment',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES accomplishment_reports(id) ON DELETE CASCADE,
    INDEX idx_report_id (report_id)
);

-- Performance Ratings Table
CREATE TABLE IF NOT EXISTS performance_ratings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    report_id INT NOT NULL,
    overall_quality INT,
    overall_efficiency INT,
    overall_timeliness INT,
    average_rating DECIMAL(3,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (report_id) REFERENCES accomplishment_reports(id) ON DELETE CASCADE,
    UNIQUE KEY unique_report_rating (report_id)
);

-- Audit Log Table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    entity_type VARCHAR(100),
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_created_at (created_at),
    INDEX idx_user_id (user_id)
);
-- Database Storage Table
CREATE TABLE IF NOT EXISTS database_storage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
);
-- Add indexes for common queries
CREATE INDEX idx_report_employee ON accomplishment_reports(employee_id);
CREATE INDEX idx_report_status ON accomplishment_reports(status);
CREATE INDEX idx_report_period ON accomplishment_reports(reporting_period_start, reporting_period_end);
