<?php
// Helper functions for the application

/**
 * Sanitize user input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Escape string for database query
 */
function escape($data, $conn) {
    return $conn->real_escape_string($data);
}

/**
 * Get user info by ID
 */
function getUserById($userId, $conn) {
    $query = "SELECT * FROM users WHERE id = " . intval($userId);
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

/**
 * Get accomplishment report by ID
 */
function getReportById($reportId, $conn) {
    $query = "SELECT ar.*, ar.employee_id as user_id, u.first_name, u.last_name, u.employee_id as emp_code, u.department, u.position 
              FROM accomplishment_reports ar 
              JOIN users u ON ar.employee_id = u.id 
              WHERE ar.id = " . intval($reportId);
    $result = $conn->query($query);
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

/**
 * Get all projects/activities for a report
 */
function getProjectsByReportId($reportId, $conn) {
    $query = "SELECT * FROM projects_activities WHERE report_id = " . intval($reportId) . " ORDER BY id ASC";
    $result = $conn->query($query);
    $projects = [];
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    return $projects;
}

/**
 * Get performance rating for a report
 */
function getPerformanceRating($reportId, $conn) {
    $query = "SELECT * FROM performance_ratings WHERE report_id = " . intval($reportId);
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

/**
 * Create new accomplishment report
 */
function createReport($employeeId, $periodStart, $periodEnd, $conn) {
    $employeeId = intval($employeeId);
    $periodStart = $conn->real_escape_string($periodStart);
    $periodEnd = $conn->real_escape_string($periodEnd);
    
    $query = "INSERT INTO accomplishment_reports (employee_id, reporting_period_start, reporting_period_end) 
              VALUES ($employeeId, '$periodStart', '$periodEnd')";
    
    if ($conn->query($query)) {
        return $conn->insert_id;
    } else {
        // Log the error for debugging
        @file_put_contents('debug_log.txt', date('Y-m-d H:i:s') . " - createReport Error: " . $conn->error . " - Query: $query\n\n", FILE_APPEND);
        return false;
    }
}

/**
 * Update report status
 */
function updateReportStatus($reportId, $status, $conn) {
    $reportId = intval($reportId);
    $status = escape($status, $conn);
    
    $query = "UPDATE accomplishment_reports SET status = '$status' WHERE id = $reportId";
    return $conn->query($query);
}

/**
 * Calculate average performance rating
 */
function calculateAverageRating($quality, $efficiency, $timeliness) {
    return round(($quality + $efficiency + $timeliness) / 3, 2);
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    $classes = [
        'draft' => 'bg-gray-200 text-gray-800',
        'submitted' => 'bg-blue-200 text-blue-800',
        'reviewed' => 'bg-yellow-200 text-yellow-800',
        'approved' => 'bg-green-200 text-green-800'
    ];
    return $classes[$status] ?? 'bg-gray-200 text-gray-800';
}

/**
 * Log user action
 */
function logAction($userId, $action, $entityType, $entityId, $conn, $oldValues = null, $newValues = null) {
    $userId = intval($userId);
    $action = escape($action, $conn);
    $entityType = escape($entityType, $conn);
    $entityId = intval($entityId);
    $oldValues = $oldValues ? "'" . $conn->real_escape_string(json_encode($oldValues)) . "'" : 'NULL';
    $newValues = $newValues ? "'" . $conn->real_escape_string(json_encode($newValues)) . "'" : 'NULL';
    $ipAddress = escape($_SERVER['REMOTE_ADDR'], $conn);
    
    $query = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address) 
              VALUES ($userId, '$action', '$entityType', $entityId, $oldValues, $newValues, '$ipAddress')";
    
    return $conn->query($query);
}

/**
 * Get reports for dashboard
 */
function getDashboardStats($userId, $conn, $userRole = 'employee') {
    $userId = intval($userId);
    
    if ($userRole === 'admin') {
        // Admin sees all reports
        $totalQuery = "SELECT COUNT(*) as total FROM accomplishment_reports";
        $draftQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE status = 'draft'";
        $submittedQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE status = 'submitted'";
        $approvedQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE status = 'approved'";
    } else {
        // Employee sees only their own
        $totalQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE employee_id = $userId";
        $draftQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE employee_id = $userId AND status = 'draft'";
        $submittedQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE employee_id = $userId AND status = 'submitted'";
        $approvedQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE employee_id = $userId AND status = 'approved'";
    }
    
    return [
        'total' => $conn->query($totalQuery)->fetch_assoc()['total'],
        'draft' => $conn->query($draftQuery)->fetch_assoc()['total'],
        'submitted' => $conn->query($submittedQuery)->fetch_assoc()['total'],
        'approved' => $conn->query($approvedQuery)->fetch_assoc()['total']
    ];
}

/**
 * Get recent reports
 */
function getRecentReports($conn, $limit = 5, $userId = null, $userRole = 'employee') {
    $limit = intval($limit);
    $userId = intval($userId);
    
    if ($userRole === 'admin') {
        $query = "SELECT ar.*, u.first_name, u.last_name, u.employee_id 
                  FROM accomplishment_reports ar 
                  JOIN users u ON ar.employee_id = u.id 
                  ORDER BY ar.created_at DESC 
                  LIMIT $limit";
    } else {
        $query = "SELECT ar.*, u.first_name, u.last_name, u.employee_id 
                  FROM accomplishment_reports ar 
                  JOIN users u ON ar.employee_id = u.id 
                  WHERE ar.employee_id = $userId
                  ORDER BY ar.created_at DESC 
                  LIMIT $limit";
    }
    
    $result = $conn->query($query);
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
    return $reports;
}

/**
 * Update user profile information
 */
function updateUserProfile($userId, $data, $conn) {
    $employeeId = $conn->real_escape_string($data['employee_id']);
    $firstName = $conn->real_escape_string($data['first_name']);
    $lastName = $conn->real_escape_string($data['last_name']);
    $email = $conn->real_escape_string($data['email']);
    $department = $conn->real_escape_string($data['department']);
    $position = $conn->real_escape_string($data['position']);
    
    $query = "UPDATE users SET 
              employee_id = '$employeeId',
              first_name = '$firstName',
              last_name = '$lastName',
              email = '$email',
              department = '$department',
              position = '$position',
              updated_at = NOW()
              WHERE id = " . intval($userId);
    
    if ($conn->query($query) === TRUE) {
        return array('success' => true, 'message' => 'Profile updated successfully!');
    } else {
        return array('success' => false, 'message' => 'Error updating profile: ' . $conn->error);
    }
}

/**
 * Update user password
 */
function updateUserPassword($userId, $oldPassword, $newPassword, $conn) {
    // Get current user password
    $user = getUserById($userId, $conn);
    
    if (!$user) {
        return array('success' => false, 'message' => 'User not found');
    }
    
    // Verify old password
    if (!password_verify($oldPassword, $user['password'])) {
        return array('success' => false, 'message' => 'Current password is incorrect');
    }
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $query = "UPDATE users SET 
              password = '$hashedPassword',
              updated_at = NOW()
              WHERE id = " . intval($userId);
    
    if ($conn->query($query) === TRUE) {
        return array('success' => true, 'message' => 'Password updated successfully!');
    } else {
        return array('success' => false, 'message' => 'Error updating password: ' . $conn->error);
    }
}
