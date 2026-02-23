-- Accomplishment Report System - Demo User Setup
-- Run these queries in phpMyAdmin SQL tab to set up demo users

-- Demo Employee User
INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password) 
VALUES ('EMP001', 'John', 'Doe', 'employee@company.com', 'Systems Audit', 'Senior Internal Control Officer', 'employee', MD5('demo'));

-- Demo Supervisor User  
INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password, supervisor_id) 
VALUES ('SUP001', 'Jane', 'Smith', 'supervisor@company.com', 'Systems Audit', 'Supervisor', 'supervisor', MD5('demo'), 1);

-- Demo Admin User
INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password) 
VALUES ('ADM001', 'Admin', 'User', 'admin@company.com', 'Administration', 'Administrator', 'admin', MD5('demo'));

-- Demo Sample Report (Optional - for testing)
INSERT INTO accomplishment_reports (employee_id, reporting_period_start, reporting_period_end, status) 
VALUES (1, '2025-12-01', '2025-12-31', 'draft');

-- Demo Sample Project (Optional - for testing)
INSERT INTO projects_activities (report_id, project_name, success_indicators, actual_accomplishment, quantity, efficiency, timeliness, quality, remarks)
VALUES (
    1,
    'EC Manual of Approvals Review',
    'All approvals reviewed and documented',
    '5 documents gathered and evaluated with 100% accuracy',
    5,
    95,
    100,
    98,
    'Successfully completed on schedule'
);

-- Verify Users Created
SELECT id, employee_id, first_name, last_name, email, role FROM users;

-- Login Information:
-- Employee: email=employee@company.com, password=demo
-- Supervisor: email=supervisor@company.com, password=demo  
-- Admin: email=admin@company.com, password=demo

-- Note: If using bcrypt hashing instead of MD5, use:
-- password = '$2y$10$N9qo8uLOickgx2ZMRZoxyeIVKq9.SEwdLx0EAWQNXlf7rvQDdBK0O' (password is 'demo')
-- And update login.php to use password_verify() instead of MD5 comparison
