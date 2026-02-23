<?php
/**
 * Users Controller
 * Handles all user-related actions (CRUD operations)
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
    case 'create':
        createUser($conn, $userId);
        break;
    case 'update':
        updateUser($conn, $userId);
        break;
    case 'delete':
        deleteUser($conn, $userId);
        break;
    case 'reset_password':
        resetPassword($conn, $userId);
        break;
    case 'get':
        getUser($conn);
        break;
    case 'list':
        listUsers($conn);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

/**
 * Create a new user
 */
function createUser($conn, $adminId)
{
    $employeeId = $conn->real_escape_string($_POST['employee_id']);
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string($_POST['position']);
    $role = $conn->real_escape_string($_POST['role']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if employee_id or email already exists
    $checkQuery = "SELECT id FROM users WHERE employee_id = '$employeeId' OR email = '$email'";
    $checkResult = $conn->query($checkQuery);
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Employee ID or Email already exists']);
        return;
    }

    $query = "INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password) 
              VALUES ('$employeeId', '$firstName', '$lastName', '$email', '$department', '$position', '$role', '$password')";

    if ($conn->query($query)) {
        $newUserId = $conn->insert_id;
        logAction($adminId, 'CREATE', 'user', $newUserId, $conn);
        echo json_encode(['success' => true, 'message' => 'User created successfully!', 'user_id' => $newUserId]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
}

/**
 * Update an existing user
 */
function updateUser($conn, $adminId)
{
    $editUserId = intval($_POST['user_id']);
    $employeeId = $conn->real_escape_string($_POST['employee_id']);
    $firstName = $conn->real_escape_string($_POST['first_name']);
    $lastName = $conn->real_escape_string($_POST['last_name']);
    $email = $conn->real_escape_string($_POST['email']);
    $department = $conn->real_escape_string($_POST['department']);
    $position = $conn->real_escape_string($_POST['position']);
    $role = $conn->real_escape_string($_POST['role']);

    // Check if employee_id or email already exists for another user
    $checkQuery = "SELECT id FROM users WHERE (employee_id = '$employeeId' OR email = '$email') AND id != $editUserId";
    $checkResult = $conn->query($checkQuery);
    if ($checkResult->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Employee ID or Email already exists for another user']);
        return;
    }

    $query = "UPDATE users SET 
              employee_id = '$employeeId',
              first_name = '$firstName',
              last_name = '$lastName',
              email = '$email',
              department = '$department',
              position = '$position',
              role = '$role'
              WHERE id = $editUserId";

    if ($conn->query($query)) {
        logAction($adminId, 'UPDATE', 'user', $editUserId, $conn);
        echo json_encode(['success' => true, 'message' => 'User updated successfully!']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
}

/**
 * Delete a user
 */
function deleteUser($conn, $adminId)
{
    $deleteUserId = intval($_POST['user_id']);

    // Don't allow deleting yourself
    if ($deleteUserId == $adminId) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account!']);
        return;
    }

    // Start transaction to ensure data integrity
    $conn->begin_transaction();

    try {
        // 1. Clear supervisor reference for other users
        $conn->query("UPDATE users SET supervisor_id = NULL WHERE supervisor_id = $deleteUserId");

        // 2. Detach from audit logs (set to NULL to keep logs but anonymous)
        $conn->query("UPDATE audit_logs SET user_id = NULL WHERE user_id = $deleteUserId");

        // 3. Handle reports reviewed by this user
        $conn->query("UPDATE accomplishment_reports SET reviewed_by = NULL WHERE reviewed_by = $deleteUserId");

        // 4. Delete user's own reports (this will cascade to projects_activities and performance_ratings)
        $conn->query("DELETE FROM accomplishment_reports WHERE employee_id = $deleteUserId");

        // 5. Delete files uploaded by this user in database_storage
        $conn->query("DELETE FROM database_storage WHERE uploaded_by = $deleteUserId");

        // 6. Finally delete the user
        $query = "DELETE FROM users WHERE id = $deleteUserId";
        if ($conn->query($query)) {
            logAction($adminId, 'DELETE', 'user', $deleteUserId, $conn);
            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'User and all related data deleted successfully!']);
        }
        else {
            throw new Exception($conn->error);
        }
    }
    catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}

/**
 * Reset user password
 */
function resetPassword($conn, $adminId)
{
    $resetUserId = intval($_POST['user_id']);
    $newPassword = password_hash('Password123!', PASSWORD_DEFAULT);

    $query = "UPDATE users SET password = '$newPassword' WHERE id = $resetUserId";
    if ($conn->query($query)) {
        logAction($adminId, 'RESET_PASSWORD', 'user', $resetUserId, $conn);
        echo json_encode(['success' => true, 'message' => 'Password reset to: Password123!']);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
    }
}

/**
 * Get a single user by ID
 */
function getUser($conn)
{
    $getUserId = intval($_GET['user_id']);

    $query = "SELECT id, employee_id, first_name, last_name, email, department, position, role, created_at FROM users WHERE id = $getUserId";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $userData = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $userData]);
    }
    else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

/**
 * List all users with pagination
 */
function listUsers($conn)
{
    $itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
        $itemsPerPage = 10;
    }

    $currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
    if ($currentPage < 1)
        $currentPage = 1;

    // Get total count of users
    $countQuery = "SELECT COUNT(*) as total FROM users";
    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $totalUsers = $countRow['total'];

    // Calculate pagination
    $totalPages = $itemsPerPage > 0 ? ceil($totalUsers / $itemsPerPage) : 1;
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }

    // Get list of users with pagination
    $offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
    $limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";

    $usersQuery = "SELECT id, employee_id, first_name, last_name, email, department, position, role, created_at FROM users ORDER BY created_at DESC $limitClause";
    $usersResult = $conn->query($usersQuery);
    $usersList = [];
    while ($row = $usersResult->fetch_assoc()) {
        $usersList[] = $row;
    }

    echo json_encode([
        'success' => true,
        'users' => $usersList,
        'pagination' => [
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'itemsPerPage' => $itemsPerPage
        ]
    ]);
}
