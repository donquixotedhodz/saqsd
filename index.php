<?php
// Redirect to login or dashboard
require_once 'backend/config.php';

if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userQuery = "SELECT role FROM users WHERE id = $userId";
    $userResult = $conn->query($userQuery);
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: reports/dashboard.php');
        }
    } else {
        header('Location: reports/login.php');
    }
} else {
    header('Location: reports/login.php');
}
exit;
