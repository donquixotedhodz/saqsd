<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? sanitize($_GET['action']) : '';

// Parse JSON input for POST/PUT requests
if ($method === 'POST' || $method === 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_GET;
}

// Authentication check (basic - should be improved)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        // Create new report
        case 'create_report':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $employeeId = isset($input['employee_id']) ? intval($input['employee_id']) : $userId;
            $periodStart = isset($input['period_start']) ? $input['period_start'] : '';
            $periodEnd = isset($input['period_end']) ? $input['period_end'] : '';
            
            if (!$periodStart || !$periodEnd) {
                throw new Exception('Period dates are required');
            }
            
            $reportId = createReport($employeeId, $periodStart, $periodEnd, $conn);
            
            if (!$reportId) {
                throw new Exception('Failed to create report');
            }
            
            logAction($userId, 'CREATE', 'accomplishment_report', $reportId, $conn, null, 
                     ['employee_id' => $employeeId, 'period_start' => $periodStart, 'period_end' => $periodEnd]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Report created successfully',
                'report_id' => $reportId
            ]);
            break;
        
        // Get report details
        case 'get_report':
            $reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
            
            if (!$reportId) {
                throw new Exception('Report ID is required');
            }
            
            $report = getReportById($reportId, $conn);
            
            if (!$report) {
                throw new Exception('Report not found');
            }
            
            $projects = getProjectsByReportId($reportId, $conn);
            $performance = getPerformanceRating($reportId, $conn);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'report' => $report,
                    'projects' => $projects,
                    'performance' => $performance
                ]
            ]);
            break;
        
        // Add project/activity
        case 'add_project':
            if ($method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
            $projectName = isset($input['project_name']) ? escape($input['project_name'], $conn) : '';
            $description = isset($input['description']) ? escape($input['description'], $conn) : '';
            $successIndicators = isset($input['success_indicators']) ? escape($input['success_indicators'], $conn) : '';
            $actualAccomplishment = isset($input['actual_accomplishment']) ? escape($input['actual_accomplishment'], $conn) : '';
            $quantity = isset($input['quantity']) ? intval($input['quantity']) : 0;
            $efficiency = isset($input['efficiency']) ? intval($input['efficiency']) : 0;
            $timeliness = isset($input['timeliness']) ? intval($input['timeliness']) : 0;
            $quality = isset($input['quality']) ? intval($input['quality']) : 0;
            $remarks = isset($input['remarks']) ? escape($input['remarks'], $conn) : '';
            
            if (!$reportId || !$projectName) {
                throw new Exception('Report ID and project name are required');
            }
            
            $query = "INSERT INTO projects_activities (report_id, project_name, description, success_indicators, 
                      actual_accomplishment, quantity, efficiency, timeliness, quality, remarks)
                      VALUES ($reportId, '$projectName', '$description', '$successIndicators', '$actualAccomplishment',
                      $quantity, $efficiency, $timeliness, $quality, '$remarks')";
            
            if (!$conn->query($query)) {
                throw new Exception('Failed to add project');
            }
            
            $projectId = $conn->insert_id;
            
            logAction($userId, 'CREATE', 'project_activity', $projectId, $conn, null, $input);
            
            echo json_encode([
                'success' => true,
                'message' => 'Project added successfully',
                'project_id' => $projectId
            ]);
            break;
        
        // Update project/activity
        case 'update_project':
            if ($method !== 'PUT' && $method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $projectId = isset($input['project_id']) ? intval($input['project_id']) : 0;
            
            if (!$projectId) {
                throw new Exception('Project ID is required');
            }
            
            $updateFields = [];
            $oldValues = [];
            $newValues = [];
            
            // Get old values
            $oldQuery = "SELECT * FROM projects_activities WHERE id = $projectId";
            $oldResult = $conn->query($oldQuery);
            if ($oldResult) {
                $oldValues = $oldResult->fetch_assoc();
            }
            
            if (isset($input['project_name'])) {
                $projectName = escape($input['project_name'], $conn);
                $updateFields[] = "project_name = '$projectName'";
                $newValues['project_name'] = $input['project_name'];
            }
            if (isset($input['description'])) {
                $description = escape($input['description'], $conn);
                $updateFields[] = "description = '$description'";
                $newValues['description'] = $input['description'];
            }
            if (isset($input['success_indicators'])) {
                $successIndicators = escape($input['success_indicators'], $conn);
                $updateFields[] = "success_indicators = '$successIndicators'";
                $newValues['success_indicators'] = $input['success_indicators'];
            }
            if (isset($input['actual_accomplishment'])) {
                $actualAccomplishment = escape($input['actual_accomplishment'], $conn);
                $updateFields[] = "actual_accomplishment = '$actualAccomplishment'";
                $newValues['actual_accomplishment'] = $input['actual_accomplishment'];
            }
            if (isset($input['quantity'])) {
                $quantity = intval($input['quantity']);
                $updateFields[] = "quantity = $quantity";
                $newValues['quantity'] = $quantity;
            }
            if (isset($input['efficiency'])) {
                $efficiency = intval($input['efficiency']);
                $updateFields[] = "efficiency = $efficiency";
                $newValues['efficiency'] = $efficiency;
            }
            if (isset($input['timeliness'])) {
                $timeliness = intval($input['timeliness']);
                $updateFields[] = "timeliness = $timeliness";
                $newValues['timeliness'] = $timeliness;
            }
            if (isset($input['quality'])) {
                $quality = intval($input['quality']);
                $updateFields[] = "quality = $quality";
                $newValues['quality'] = $quality;
            }
            if (isset($input['remarks'])) {
                $remarks = escape($input['remarks'], $conn);
                $updateFields[] = "remarks = '$remarks'";
                $newValues['remarks'] = $input['remarks'];
            }
            
            if (empty($updateFields)) {
                throw new Exception('No fields to update');
            }
            
            $query = "UPDATE projects_activities SET " . implode(', ', $updateFields) . " WHERE id = $projectId";
            
            if (!$conn->query($query)) {
                throw new Exception('Failed to update project');
            }
            
            logAction($userId, 'UPDATE', 'project_activity', $projectId, $conn, $oldValues, $newValues);
            
            echo json_encode([
                'success' => true,
                'message' => 'Project updated successfully'
            ]);
            break;
        
        // Delete project/activity
        case 'delete_project':
            if ($method !== 'DELETE' && $method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $projectId = isset($input['project_id']) ? intval($input['project_id']) : 0;
            
            if (!$projectId) {
                throw new Exception('Project ID is required');
            }
            
            // Get old values for logging
            $oldQuery = "SELECT * FROM projects_activities WHERE id = $projectId";
            $oldResult = $conn->query($oldQuery);
            $oldValues = $oldResult->fetch_assoc();
            
            $query = "DELETE FROM projects_activities WHERE id = $projectId";
            
            if (!$conn->query($query)) {
                throw new Exception('Failed to delete project');
            }
            
            logAction($userId, 'DELETE', 'project_activity', $projectId, $conn, $oldValues, null);
            
            echo json_encode([
                'success' => true,
                'message' => 'Project deleted successfully'
            ]);
            break;
        
        // Update report status
        case 'update_report_status':
            if ($method !== 'PUT' && $method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
            $status = isset($input['status']) ? sanitize($input['status']) : '';
            
            if (!$reportId || !$status) {
                throw new Exception('Report ID and status are required');
            }
            
            $allowedStatuses = ['draft', 'submitted', 'reviewed', 'approved'];
            if (!in_array($status, $allowedStatuses)) {
                throw new Exception('Invalid status');
            }
            
            $query = "UPDATE accomplishment_reports SET status = '$status' WHERE id = $reportId";
            
            if (!$conn->query($query)) {
                throw new Exception('Failed to update report status');
            }
            
            logAction($userId, 'UPDATE_STATUS', 'accomplishment_report', $reportId, $conn, ['status' => 'previous'], ['status' => $status]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Report status updated successfully'
            ]);
            break;
        
        // Update performance rating
        case 'update_performance':
            if ($method !== 'PUT' && $method !== 'POST') {
                throw new Exception('Invalid request method');
            }
            
            $reportId = isset($input['report_id']) ? intval($input['report_id']) : 0;
            $quality = isset($input['quality']) ? intval($input['quality']) : 0;
            $efficiency = isset($input['efficiency']) ? intval($input['efficiency']) : 0;
            $timeliness = isset($input['timeliness']) ? intval($input['timeliness']) : 0;
            
            if (!$reportId) {
                throw new Exception('Report ID is required');
            }
            
            $avgRating = calculateAverageRating($quality, $efficiency, $timeliness);
            
            // Check if rating exists
            $checkQuery = "SELECT id FROM performance_ratings WHERE report_id = $reportId";
            $checkResult = $conn->query($checkQuery);
            
            if ($checkResult->num_rows > 0) {
                $query = "UPDATE performance_ratings SET overall_quality = $quality, overall_efficiency = $efficiency, 
                         overall_timeliness = $timeliness, average_rating = $avgRating WHERE report_id = $reportId";
            } else {
                $query = "INSERT INTO performance_ratings (report_id, overall_quality, overall_efficiency, overall_timeliness, average_rating)
                         VALUES ($reportId, $quality, $efficiency, $timeliness, $avgRating)";
            }
            
            if (!$conn->query($query)) {
                throw new Exception('Failed to update performance rating');
            }
            
            logAction($userId, 'UPDATE', 'performance_rating', $reportId, $conn, null, 
                     ['quality' => $quality, 'efficiency' => $efficiency, 'timeliness' => $timeliness]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Performance rating updated successfully',
                'average_rating' => $avgRating
            ]);
            break;
        
        default:
            throw new Exception('Unknown action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
