<?php
/**
 * Reports Controller
 * Handles all report-related AJAX operations
 */

require_once '../../backend/config.php';
require_once '../../backend/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId, $conn);

// Set content type for JSON response
header('Content-Type: application/json');

// Get the action
$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';

switch ($action) {
    case 'create_report':
        createNewReport();
        break;

    case 'add_project':
        addProject();
        break;

    case 'get_report':
        getReport();
        break;

    case 'delete_report':
        deleteReport();
        break;

    case 'delete_project':
        deleteProject();
        break;

    case 'update_project':
        updateProject();
        break;

    case 'update_status':
        updateStatus();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Get report with projects
 */
function getReport()
{
    global $conn, $userId, $userInfo;

    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }

    // Get report
    $report = getReportById($reportId, $conn);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        return;
    }

    // Check if user can view (own report or admin/supervisor)
    $reportOwnerId = intval($report['user_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($reportOwnerId !== $currentUserId && $userRole !== 'admin' && $userRole !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to view this report']);
        return;
    }

    // Get projects
    $projects = getProjectsByReportId($reportId, $conn);

    echo json_encode([
        'success' => true,
        'report' => $report,
        'projects' => $projects
    ]);
}

/**
 * Delete a project/accomplishment
 */
function deleteProject()
{
    global $conn, $userId, $userInfo;

    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;

    if ($projectId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        return;
    }

    // Get project to find report
    $query = "SELECT pa.*, ar.employee_id, ar.status FROM projects_activities pa 
              JOIN accomplishment_reports ar ON pa.report_id = ar.id 
              WHERE pa.id = $projectId";
    $result = $conn->query($query);
    $project = $result->fetch_assoc();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        return;
    }

    // Check if user can delete (own report or admin)
    $projectOwnerId = intval($project['employee_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($projectOwnerId !== $currentUserId && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this project']);
        return;
    }

    // Only allow deletion if report is still draft
    if ($project['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Cannot delete projects from submitted reports']);
        return;
    }

    // Delete the project
    $deleteQuery = "DELETE FROM projects_activities WHERE id = $projectId";

    if ($conn->query($deleteQuery)) {
        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'DELETE', 'project', $projectId, $conn);
        }

        echo json_encode(['success' => true, 'message' => 'Accomplishment deleted successfully!']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete project']);
    }
}

/**
 * Update an existing project/accomplishment
 */
function updateProject()
{
    global $conn, $userId, $userInfo;

    $projectId = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
    $projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';

    // Handle bulk success indicators and accomplishments (arrays)
    $successIndicators = isset($_POST['success_indicators']) ? $_POST['success_indicators'] : [];
    $actualAccomplishments = isset($_POST['actual_accomplishments']) ? $_POST['actual_accomplishments'] : [];

    // If old format (single values), convert to arrays
    if (!is_array($successIndicators)) {
        $successIndicators = [$successIndicators];
    }
    if (!is_array($actualAccomplishments)) {
        $actualAccomplishments = [$actualAccomplishments];
    }

    // Validation
    if ($projectId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid project ID']);
        return;
    }

    // Get project to find report and verify ownership
    $query = "SELECT pa.*, ar.employee_id, ar.status FROM projects_activities pa 
              JOIN accomplishment_reports ar ON pa.report_id = ar.id 
              WHERE pa.id = $projectId";
    $result = $conn->query($query);
    $project = $result->fetch_assoc();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        return;
    }

    // Check if user can update (own report or admin)
    $projectOwnerId = intval($project['employee_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($projectOwnerId !== $currentUserId && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to modify this project']);
        return;
    }

    // Only allow update if report is still draft
    if ($project['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Cannot update projects in submitted reports']);
        return;
    }

    if (empty($projectName)) {
        echo json_encode(['success' => false, 'message' => 'Project/Activity name is required']);
        return;
    }

    // Success indicators and accomplishments are now optional
    // Ensure arrays have matching lengths, pad if necessary
    $maxCount = max(count($successIndicators), count($actualAccomplishments));
    $successIndicators = array_pad($successIndicators, $maxCount, '');
    $actualAccomplishments = array_pad($actualAccomplishments, $maxCount, '');

    // Handle file upload (optional)
    $attachmentPath = $project['attachment']; // Keep existing attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['attachment'], $project['report_id']);
        if ($uploadResult['success']) {
            $attachmentPath = $uploadResult['path'];
        }
        else {
            echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
            return;
        }
    }

    // Combine arrays into formatted strings using \n---\n delimiter
    $indicatorsFormatted = [];
    $accomplishmentsFormatted = [];

    foreach ($successIndicators as $index => $indicator) {
        $indicatorsFormatted[] = trim($indicator);
        $accomplishmentsFormatted[] = trim($actualAccomplishments[$index] ?? '');
    }

    // Join with \n---\n delimiter so each item displays in its own row
    $successIndicatorsStr = $conn->real_escape_string(implode("\n---\n", $indicatorsFormatted));
    $actualAccomplishmentStr = $conn->real_escape_string(implode("\n---\n", $accomplishmentsFormatted));
    $projectNameEscaped = $conn->real_escape_string($projectName);
    $attachmentPathEscaped = $attachmentPath ? $conn->real_escape_string($attachmentPath) : null;

    // Update project
    $updateQuery = "UPDATE projects_activities SET 
                    project_name = '$projectNameEscaped',
                    success_indicators = '$successIndicatorsStr',
                    actual_accomplishment = '$actualAccomplishmentStr'" .
        ($attachmentPath ? ", attachment = '$attachmentPathEscaped'" : "") . "
                    WHERE id = $projectId";

    if ($conn->query($updateQuery)) {
        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'UPDATE', 'project', $projectId, $conn);
        }

        echo json_encode(['success' => true, 'message' => 'Project updated successfully!']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to update project: ' . $conn->error]);
    }
}

/**
 * Create a new report
 */
function createNewReport()
{
    global $conn, $userId;

    $periodStart = isset($_POST['period_start']) ? $_POST['period_start'] : '';
    $periodEnd = isset($_POST['period_end']) ? $_POST['period_end'] : '';

    // Validation
    if (empty($periodStart) || empty($periodEnd)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in both start and end dates']);
        return;
    }

    if (strtotime($periodStart) > strtotime($periodEnd)) {
        echo json_encode(['success' => false, 'message' => 'Start date must be before end date']);
        return;
    }

    // Check if report already exists for this period
    $checkQuery = "SELECT id FROM accomplishment_reports 
                   WHERE employee_id = $userId 
                   AND reporting_period_start = '" . escape($periodStart, $conn) . "' 
                   AND reporting_period_end = '" . escape($periodEnd, $conn) . "'";
    $checkResult = $conn->query($checkQuery);

    if ($checkResult && $checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'A report already exists for this period']);
        return;
    }

    // Create the report
    $reportId = createReport($userId, $periodStart, $periodEnd, $conn);

    if ($reportId) {
        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'CREATE', 'report', $reportId, $conn);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Report created successfully!',
            'report_id' => $reportId,
            'period_start' => date('M d, Y', strtotime($periodStart)),
            'period_end' => date('M d, Y', strtotime($periodEnd))
        ]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to create report. Please try again.']);
    }
}

/**
 * Add a project/accomplishment to a report
 */
function addProject()
{
    global $conn, $userId, $userInfo;

    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $projectName = isset($_POST['project_name']) ? trim($_POST['project_name']) : '';

    // Handle bulk success indicators and accomplishments (arrays)
    $successIndicators = isset($_POST['success_indicators']) ? $_POST['success_indicators'] : [];
    $actualAccomplishments = isset($_POST['actual_accomplishments']) ? $_POST['actual_accomplishments'] : [];

    // If old format (single values), convert to arrays
    if (!is_array($successIndicators)) {
        $successIndicators = [$successIndicators];
    }
    if (!is_array($actualAccomplishments)) {
        $actualAccomplishments = [$actualAccomplishments];
    }

    // Validation
    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }

    // Get report to verify ownership
    $report = getReportById($reportId, $conn);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        return;
    }

    // Check if user owns this report (employee can modify their own reports)
    $reportOwnerId = intval($report['user_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($reportOwnerId !== $currentUserId && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to modify this report']);
        return;
    }

    if (empty($projectName)) {
        echo json_encode(['success' => false, 'message' => 'Project/Activity name is required']);
        return;
    }

    // Success indicators and accomplishments are now optional
    // Ensure arrays have matching lengths, pad if necessary
    $maxCount = max(count($successIndicators), count($actualAccomplishments));
    $successIndicators = array_pad($successIndicators, $maxCount, '');
    $actualAccomplishments = array_pad($actualAccomplishments, $maxCount, '');

    // Handle file upload
    $attachmentPath = null;
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = handleFileUpload($_FILES['attachment'], $reportId);
        if ($uploadResult['success']) {
            $attachmentPath = $uploadResult['path'];
        }
        else {
            echo json_encode(['success' => false, 'message' => $uploadResult['message']]);
            return;
        }
    }

    // Combine arrays into formatted strings using \n---\n delimiter
    $indicatorsFormatted = [];
    $accomplishmentsFormatted = [];

    foreach ($successIndicators as $index => $indicator) {
        $indicatorsFormatted[] = trim($indicator);
        $accomplishmentsFormatted[] = trim($actualAccomplishments[$index] ?? '');
    }

    // Join with \n---\n delimiter so each item displays in its own row
    $successIndicatorsStr = $conn->real_escape_string(implode("\n---\n", $indicatorsFormatted));
    $actualAccomplishmentStr = $conn->real_escape_string(implode("\n---\n", $accomplishmentsFormatted));
    $projectNameEscaped = $conn->real_escape_string($projectName);
    $attachmentPathEscaped = $attachmentPath ? $conn->real_escape_string($attachmentPath) : null;

    // Insert project
    $query = "INSERT INTO projects_activities (report_id, project_name, success_indicators, actual_accomplishment, quantity, efficiency, timeliness, quality, remarks" . ($attachmentPath ? ", attachment" : "") . ")
             VALUES ($reportId, '$projectNameEscaped', '$successIndicatorsStr', '$actualAccomplishmentStr', 0, 0, 0, 0, ''" . ($attachmentPath ? ", '$attachmentPathEscaped'" : "") . ")";

    if ($conn->query($query)) {
        $projectId = $conn->insert_id;

        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'CREATE', 'project', $projectId, $conn);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Project/Activity added successfully!',
            'project_id' => $projectId,
            'items_count' => count($indicatorsFormatted)
        ]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to add project. ' . $conn->error]);
    }
}

/**
 * Handle file upload
 */
function handleFileUpload($file, $reportId)
{
    $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg', 'image/png', 'image/gif'];
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif'];
    $maxSize = 10 * 1024 * 1024; // 10MB

    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File size exceeds 10MB limit'];
    }

    // Check file extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG, GIF'];
    }

    // Create upload directory if it doesn't exist
    $uploadDir = dirname(dirname(__DIR__)) . '/uploads/reports/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Generate unique filename
    $filename = 'report_' . $reportId . '_' . time() . '_' . uniqid() . '.' . $extension;
    $targetPath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'path' => 'uploads/reports/' . $filename];
    }
    else {
        return ['success' => false, 'message' => 'Failed to upload file'];
    }
}

/**
 * Delete a report
 */
function deleteReport()
{
    global $conn, $userId, $userInfo;

    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;

    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }

    // Get report to verify ownership
    $report = getReportById($reportId, $conn);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        return;
    }

    // Check if user can delete (own report or admin)
    $reportOwnerId = intval($report['user_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($reportOwnerId !== $currentUserId && $userRole !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to delete this report']);
        return;
    }

    // Only allow deletion of draft reports
    if ($report['status'] !== 'draft') {
        echo json_encode(['success' => false, 'message' => 'Only draft reports can be deleted']);
        return;
    }

    // Delete associated projects/activities first
    $deleteProjects = "DELETE FROM projects_activities WHERE report_id = $reportId";
    $conn->query($deleteProjects);

    // Delete the report
    $deleteQuery = "DELETE FROM accomplishment_reports WHERE id = $reportId";

    if ($conn->query($deleteQuery)) {
        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'DELETE', 'report', $reportId, $conn);
        }

        echo json_encode(['success' => true, 'message' => 'Report deleted successfully!']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete report']);
    }
}

/**
 * Update report status
 */
function updateStatus()
{
    global $conn, $userId, $userInfo;

    $reportId = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
    $status = isset($_POST['status']) ? sanitize($_POST['status']) : '';

    if ($reportId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid report ID']);
        return;
    }

    $allowedStatuses = ['draft', 'submitted', 'reviewed', 'approved'];
    if (!in_array($status, $allowedStatuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        return;
    }

    // Get report
    $report = getReportById($reportId, $conn);

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Report not found']);
        return;
    }

    // Check permissions (employee can submit their own, admin/supervisor can update any)
    $reportOwnerId = intval($report['user_id']);
    $currentUserId = intval($userId);
    $userRole = $userInfo['role'] ?? 'employee';

    if ($reportOwnerId !== $currentUserId && $userRole !== 'admin' && $userRole !== 'supervisor') {
        echo json_encode(['success' => false, 'message' => 'You do not have permission to update this report']);
        return;
    }

    // Update status
    if (updateReportStatus($reportId, $status, $conn)) {
        // Log the action
        if (function_exists('logAction')) {
            logAction($userId, 'UPDATE_STATUS', 'report', $reportId, $conn);
        }

        echo json_encode(['success' => true, 'message' => 'Report status updated to ' . ucfirst($status)]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
}
?>
