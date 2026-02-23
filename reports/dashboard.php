<?php
require_once '../backend/config.php';
require_once '../backend/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId, $conn);

// Check if user info was retrieved successfully
if (!$userInfo) {
    header("Location: login.php");
    exit;
}

$userRole = $userInfo['role'] ?? 'employee';

// Get dashboard statistics
$stats = getDashboardStats($userId, $conn, $userRole);
$recentReports = getRecentReports($conn, 5, $userId, $userRole);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        /* Modernized via src/css/style.css */
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php $activePage = 'dashboard'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Dashboard';
$pageIcon = 'fas fa-tachometer-alt';
$showNewReport = true;
include 'includes/header.php';
?>
            
            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Reports -->
                    <div class="stat-card">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">Total Reports</p>
                            <p class="stat-value"><?php echo $stats['total']; ?></p>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                    
                    <!-- Draft Reports -->
                    <div class="stat-card">
                        <div>
                            <p class="text-gray-500 text-sm font-medium mb-1">Draft</p>
                            <p class="stat-value"><?php echo $stats['draft']; ?></p>
                        </div>
                        <div class="stat-icon" style="background-color: #f1f5f9; color: #475569;">
                            <i class="fas fa-file-signature"></i>
                        </div>
                    </div>
                    
                    <!-- Submitted Reports -->
                    <div class="stat-card">
                        <div>
                            <p class="text-gray-600 text-sm mb-2">Submitted</p>
                            <p class="stat-value"><?php echo $stats['submitted']; ?></p>
                        </div>
                        <div class="stat-icon" style="color: #bfdbfe;">
                            <i class="fas fa-paper-plane"></i>
                        </div>
                    </div>
                    
                    <!-- Approved Reports -->
                    <div class="stat-card">
                        <div>
                            <p class="text-gray-600 text-sm mb-2">Approved</p>
                            <p class="stat-value"><?php echo $stats['approved']; ?></p>
                        </div>
                        <div class="stat-icon" style="color: #86efac;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Reports -->
                <!-- <div class="card">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-bold text-gray-800">Recent Reports</h3>
                        <a href="reports.php" class="text-sm text-blue-600 hover:text-blue-800">View All</a>
                    </div>
                    
                    <?php if (empty($recentReports)): ?>
                        <p class="text-gray-600 text-center py-8">No reports found. <a href="report-create.php" class="text-blue-600 hover:text-blue-800">Create one now</a></p>
                    <?php
else: ?>
                        <div class="overflow-x-auto">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentReports as $report): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars(date('M d', strtotime($report['reporting_period_start'])) . ' - ' . date('M d, Y', strtotime($report['reporting_period_end']))); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($report['department']); ?></td>
                                        <td>
                                            <span class="badge badge-<?php echo htmlspecialchars($report['status']); ?>">
                                                <?php echo ucfirst(htmlspecialchars($report['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($report['created_at']))); ?></td>
                                        <td>
                                            <a href="report-view.php?id=<?php echo $report['id']; ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                                <i class="fas fa-eye mr-1"></i>View
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
    endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php
endif; ?>
                </div> -->
            </main>
        </div>
    </div>
</body>
</html>
