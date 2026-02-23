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
$userInfo = getUserById($userId, $conn);
$userRole = $userInfo['role'] ?? 'employee';

try {
    switch ($action) {
        // Get all reports with filters
        case 'list':
            $status = isset($input['status']) ? sanitize($input['status']) : '';
            $employeeId = isset($input['employee_id']) ? intval($input['employee_id']) : 0;
            $periodStart = isset($input['period_start']) ? $input['period_start'] : '';
            $periodEnd = isset($input['period_end']) ? $input['period_end'] : '';
            $page = isset($input['page']) ? intval($input['page']) : 1;
            $limit = isset($input['limit']) ? intval($input['limit']) : 10;
            $offset = ($page - 1) * $limit;
            
            $where = [];
            
            // Role-based filtering
            if ($userRole === 'employee') {
                $where[] = "ar.employee_id = $userId";
            } elseif ($userRole === 'supervisor') {
                // Supervisors see reports of their subordinates
                $where[] = "(ar.employee_id = $userId OR u.supervisor_id = $userId)";
            }
            // Admin sees all
            
            if ($status) {
                $status = escape($status, $conn);
                $where[] = "ar.status = '$status'";
            }
            
            if ($employeeId > 0) {
                $where[] = "ar.employee_id = $employeeId";
            }
            
            if ($periodStart) {
                $periodStart = escape($periodStart, $conn);
                $where[] = "ar.reporting_period_start >= '$periodStart'";
            }
            
            if ($periodEnd) {
                $periodEnd = escape($periodEnd, $conn);
                $where[] = "ar.reporting_period_end <= '$periodEnd'";
            }
            
            $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
            
            // Get total count
            $countQuery = "SELECT COUNT(*) as total FROM accomplishment_reports ar 
                          JOIN users u ON ar.employee_id = u.id 
                          $whereClause";
            $countResult = $conn->query($countQuery);
            $total = $countResult->fetch_assoc()['total'];
            
            // Get reports
            $query = "SELECT ar.*, u.first_name, u.last_name, u.employee_id, u.department, u.position 
                     FROM accomplishment_reports ar 
                     JOIN users u ON ar.employee_id = u.id 
                     $whereClause
                     ORDER BY ar.created_at DESC 
                     LIMIT $limit OFFSET $offset";
            
            $result = $conn->query($query);
            $reports = [];
            while ($row = $result->fetch_assoc()) {
                $reports[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $reports,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
        
        // Get dashboard statistics
        case 'dashboard_stats':
            $stats = getDashboardStats($userId, $conn, $userRole);
            $recentReports = getRecentReports($conn, 5, $userId, $userRole);
            
            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'recent_reports' => $recentReports
            ]);
            break;
        
        // Get employees list (for admin/supervisor)
        case 'employees':
            if ($userRole === 'employee') {
                throw new Exception('Unauthorized');
            }
            
            $query = "SELECT id, employee_id, first_name, last_name, department, position FROM users WHERE role = 'employee' ORDER BY last_name, first_name";
            $result = $conn->query($query);
            $employees = [];
            while ($row = $result->fetch_assoc()) {
                $employees[] = $row;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $employees
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
