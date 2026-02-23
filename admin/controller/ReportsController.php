<?php
/**
 * Reports Controller
 * Handles all report-related actions (CRUD operations, status updates)
 */

session_start();
require_once '../../backend/config.php';
require_once '../../backend/includes/functions.php';

// Set JSON header for AJAX responses
header('Content-Type: application/json');

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId, $conn);

// Check if user is admin
if ($user['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin only.']);
    exit();
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

switch ($action) {
    case 'get':
        getReport($conn);
        break;
    case 'list':
        listReports($conn);
        break;
    case 'update_status':
        updateStatus($conn, $userId);
        break;
    case 'delete':
        deleteReport($conn, $userId);
        break;
    case 'get_statistics':
        getStatistics($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get a single report with all details
 */
function getReport($conn) {
    $reportId = isset($_GET['report_id']) ? intval($_GET['report_id']) : 0;
    
    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }
    
    // Get report with user and reviewer details
    $query = "SELECT ar.*, 
              u.first_name, u.last_name, u.employee_id as emp_id, u.department, u.position, u.email,
              reviewer.first_name as reviewer_first, reviewer.last_name as reviewer_last,
              pr.average_rating, pr.overall_quality, pr.overall_efficiency, pr.overall_timeliness
              FROM accomplishment_reports ar 
              JOIN users u ON ar.employee_id = u.id 
              LEFT JOIN users reviewer ON ar.reviewed_by = reviewer.id
              LEFT JOIN performance_ratings pr ON ar.id = pr.report_id
              WHERE ar.id = $reportId";
    
    $result = $conn->query($query);
    
    if ($result && $result->num_rows > 0) {
        $report = $result->fetch_assoc();
        
        // Get projects/activities for this report
        $projectsQuery = "SELECT * FROM projects_activities WHERE report_id = $reportId ORDER BY id ASC";
        $projectsResult = $conn->query($projectsQuery);
        $projects = [];
        while ($row = $projectsResult->fetch_assoc()) {
            $projects[] = $row;
        }
        $report['projects'] = $projects;
        
        echo json_encode(['success' => true, 'report' => $report]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
    }
}

/**
 * List all reports with pagination and filtering
 */
function listReports($conn) {
    $itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
        $itemsPerPage = 10;
    }

    $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($currentPage < 1) $currentPage = 1;

    // Get filter parameters
    $filterStatus = isset($_GET['status']) ? $conn->real_escape_string($_GET['status']) : '';
    $filterEmployee = isset($_GET['employee']) ? intval($_GET['employee']) : 0;

    // Build count query
    $countQuery = "SELECT COUNT(*) as total FROM accomplishment_reports ar 
              JOIN users u ON ar.employee_id = u.id 
              WHERE 1=1";

    if (!empty($filterStatus)) {
        $countQuery .= " AND ar.status = '$filterStatus'";
    }

    if ($filterEmployee > 0) {
        $countQuery .= " AND ar.employee_id = $filterEmployee";
    }

    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $totalReports = $countRow['total'];

    // Calculate pagination
    $totalPages = $itemsPerPage > 0 ? ceil($totalReports / $itemsPerPage) : 1;
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }

    // Build data query with pagination
    $offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
    $limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";

    $query = "SELECT ar.*, u.first_name, u.last_name, u.employee_id as emp_id, u.department, pr.average_rating
              FROM accomplishment_reports ar 
              JOIN users u ON ar.employee_id = u.id 
              LEFT JOIN performance_ratings pr ON ar.id = pr.report_id
              WHERE 1=1";

    if (!empty($filterStatus)) {
        $query .= " AND ar.status = '$filterStatus'";
    }

    if ($filterEmployee > 0) {
        $query .= " AND ar.employee_id = $filterEmployee";
    }

    $query .= " ORDER BY ar.created_at DESC $limitClause";

    $reportsResult = $conn->query($query);
    $reports = [];
    while ($row = $reportsResult->fetch_assoc()) {
        $reports[] = $row;
    }

    echo json_encode([
        'success' => true,
        'reports' => $reports,
        'pagination' => [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalReports' => $totalReports,
            'itemsPerPage' => $itemsPerPage
        ]
    ]);
}

/**
 * Update report status
 */
function updateStatus($conn, $adminId) {
    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $status = isset($_POST['status']) ? $conn->real_escape_string($_POST['status']) : '';
    $reviewComments = isset($_POST['review_comments']) ? $conn->real_escape_string($_POST['review_comments']) : '';
    
    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }
    
    // Validate status
    $validStatuses = ['draft', 'submitted', 'reviewed', 'approved'];
    if (!in_array($status, $validStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        return;
    }
    
    // Build update query
    $updateFields = "status = '$status', review_comments = '$reviewComments'";
    
    // Set review date and reviewer if status is being changed to reviewed or approved
    if ($status === 'reviewed' || $status === 'approved') {
        $updateFields .= ", reviewed_by = $adminId, review_date = NOW()";
    }
    
    // Set approved date if status is being changed to approved
    if ($status === 'approved') {
        $updateFields .= ", approved_date = NOW()";
    }
    
    $query = "UPDATE accomplishment_reports SET $updateFields, updated_at = NOW() WHERE id = $reportId";
    
    if ($conn->query($query)) {
        // Log the action
        logAction($adminId, 'UPDATE_STATUS', 'report', $reportId, $conn);
        echo json_encode(['success' => true, 'message' => 'Report status updated successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating status: ' . $conn->error]);
    }
}

/**
 * Delete a report
 */
function deleteReport($conn, $adminId) {
    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    
    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }
    
    // Check if report exists
    $checkQuery = "SELECT id, scanned_file FROM accomplishment_reports WHERE id = $reportId";
    $checkResult = $conn->query($checkQuery);
    
    if ($checkResult->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        return;
    }
    
    $reportData = $checkResult->fetch_assoc();
    
    // Delete scanned file if exists
    if (!empty($reportData['scanned_file'])) {
        $filePath = '../../uploads/reports/' . $reportData['scanned_file'];
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
    
    // Note: projects_activities and performance_ratings will be deleted automatically 
    // due to ON DELETE CASCADE foreign key constraint
    
    $query = "DELETE FROM accomplishment_reports WHERE id = $reportId";
    
    if ($conn->query($query)) {
        // Log the action
        logAction($adminId, 'DELETE', 'report', $reportId, $conn);
        echo json_encode(['success' => true, 'message' => 'Report deleted successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error deleting report: ' . $conn->error]);
    }
}

/**
 * Get statistics for dashboard
 */
function getStatistics($conn) {
    $stats = [];
    
    // Total reports
    $totalQuery = "SELECT COUNT(*) as count FROM accomplishment_reports";
    $totalResult = $conn->query($totalQuery);
    $stats['total'] = $totalResult->fetch_assoc()['count'];
    
    // Reports by status
    $statusQuery = "SELECT status, COUNT(*) as count FROM accomplishment_reports GROUP BY status";
    $statusResult = $conn->query($statusQuery);
    $stats['by_status'] = [];
    while ($row = $statusResult->fetch_assoc()) {
        $stats['by_status'][$row['status']] = $row['count'];
    }
    
    // Reports by department
    $deptQuery = "SELECT u.department, COUNT(*) as count 
                  FROM accomplishment_reports ar 
                  JOIN users u ON ar.employee_id = u.id 
                  GROUP BY u.department 
                  ORDER BY count DESC 
                  LIMIT 10";
    $deptResult = $conn->query($deptQuery);
    $stats['by_department'] = [];
    while ($row = $deptResult->fetch_assoc()) {
        $stats['by_department'][] = $row;
    }
    
    // Average rating
    $ratingQuery = "SELECT AVG(average_rating) as avg_rating FROM performance_ratings WHERE average_rating IS NOT NULL";
    $ratingResult = $conn->query($ratingQuery);
    $stats['average_rating'] = round($ratingResult->fetch_assoc()['avg_rating'] ?? 0, 2);
    
    // Reports this month
    $monthQuery = "SELECT COUNT(*) as count FROM accomplishment_reports 
                   WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
                   AND YEAR(created_at) = YEAR(CURRENT_DATE())";
    $monthResult = $conn->query($monthQuery);
    $stats['this_month'] = $monthResult->fetch_assoc()['count'];
    
    // Pending reviews (submitted status)
    $pendingQuery = "SELECT COUNT(*) as count FROM accomplishment_reports WHERE status = 'submitted'";
    $pendingResult = $conn->query($pendingQuery);
    $stats['pending_review'] = $pendingResult->fetch_assoc()['count'];
    
    echo json_encode(['success' => true, 'statistics' => $stats]);
}
