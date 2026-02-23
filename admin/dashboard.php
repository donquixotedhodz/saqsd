<?php
session_start();
require_once '../backend/config.php';
require_once '../backend/includes/functions.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId, $conn);

// Check if user is admin
if ($user['role'] !== 'admin') {
    header('Location: ../reports/dashboard.php');
    exit();
}

// Get dashboard statistics
$stats = getDashboardStats($userId, $conn, 'admin');

// Get total users
$usersQuery = "SELECT COUNT(*) as total FROM users";
$totalUsers = $conn->query($usersQuery)->fetch_assoc()['total'];

// Get users by role
$rolesQuery = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$rolesResult = $conn->query($rolesQuery);
$usersByRole = [];
while ($row = $rolesResult->fetch_assoc()) {
    $usersByRole[$row['role']] = $row['count'];
}

// Get recent activities
$activitiesQuery = "SELECT al.*, u.first_name, u.last_name FROM audit_logs al 
                    LEFT JOIN users u ON al.user_id = u.id 
                    ORDER BY al.created_at DESC LIMIT 8";
$recentActivities = [];
$activitiesResult = $conn->query($activitiesQuery);
while ($row = $activitiesResult->fetch_assoc()) {
    $recentActivities[] = $row;
}

// Get pending reports
$pendingQuery = "SELECT COUNT(*) as total FROM accomplishment_reports WHERE status = 'submitted'";
$pendingReports = $conn->query($pendingQuery)->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administrator Dashboard | SAQSD</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .activity-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: var(--color-primary);
            position: absolute;
            left: -4px;
            top: 50%;
            transform: translateY(-50%);
        }
        .activity-line {
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background-color: #f1f5f9;
        }
        .quick-action-card {
            transition: all 0.3s ease;
            border: 1px solid #f1f5f9;
        }
        .quick-action-card:hover {
            transform: translateY(-5px);
            border-color: var(--color-primary-light);
            background-color: #f8faff;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <?php $activePage = 'dashboard'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Dashboard Overview';
$pageIcon = 'fas fa-chart-line';
$showUserWelcome = true;
include 'includes/header.php';
?>
            
            <main class="flex-1 overflow-y-auto p-6 md:p-8">
                <!-- Welcome Banner -->
                <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Welcome Back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                        <p class="text-gray-500 mt-1">Here's a summary of the system's performance and activity.</p>
                    </div>
                    <div class="flex gap-3">
                        <button class="btn-secondary py-2 px-4 shadow-sm">
                            <i class="fas fa-calendar mr-2 text-gray-400"></i>Feb 2024
                        </button>
                    </div>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="stat-card">
                        <div>
                            <p class="text-xs font-bold text-indigo-600 uppercase tracking-wider mb-1">Total Users</p>
                            <p class="stat-value"><?php echo number_format($totalUsers); ?></p>
                        </div>
                        <div class="stat-icon bg-indigo-50 text-indigo-600">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div>
                            <p class="text-xs font-bold text-green-600 uppercase tracking-wider mb-1">Active Reports</p>
                            <p class="stat-value"><?php echo number_format($stats['total']); ?></p>
                        </div>
                        <div class="stat-icon bg-green-50 text-green-600">
                            <i class="fas fa-file-invoice"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div>
                            <p class="text-xs font-bold text-amber-600 uppercase tracking-wider mb-1">Pending Review</p>
                            <p class="stat-value text-amber-600"><?php echo number_format($pendingReports); ?></p>
                        </div>
                        <div class="stat-icon bg-amber-50 text-amber-600 text-xl">
                            <i class="fas fa-clock"></i>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div>
                            <p class="text-xs font-bold text-blue-600 uppercase tracking-wider mb-1">Draft Reports</p>
                            <p class="stat-value"><?php echo number_format($stats['draft']); ?></p>
                        </div>
                        <div class="stat-icon bg-blue-50 text-blue-600">
                            <i class="fas fa-edit"></i>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Chart Section -->
                    <div class="lg:col-span-3 space-y-8">
                        <div class="card p-6">
                            <div class="flex items-center justify-between mb-6">
                                <h3 class="font-bold text-gray-800">Report Status Analytics</h3>
                                <div class="flex gap-2">
                                    <span class="flex items-center text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-indigo-500 mr-1"></span>Approved</span>
                                    <span class="flex items-center text-xs text-gray-500"><span class="w-3 h-3 rounded-full bg-amber-400 mr-1"></span>Pending</span>
                                </div>
                            </div>
                            <div style="height: 320px;">
                                <canvas id="reportStatusChart"></canvas>
                            </div>
                        </div>

                        <!-- Secondary Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="card p-6">
                                <h3 class="font-bold text-gray-800 mb-6 text-sm flex items-center">
                                    <i class="fas fa-user-tag mr-2 text-indigo-500"></i>User Distribution by Role
                                </h3>
                                <div style="height: 200px;">
                                    <canvas id="userDistributionChart"></canvas>
                                </div>
                            </div>

                            <div class="card p-6">
                                <h3 class="font-bold text-gray-800 mb-4 text-sm flex items-center">
                                    <i class="fas fa-bolt mr-2 text-amber-500"></i>Quick Management
                                </h3>
                                <div class="space-y-3">
                                    <a href="users.php" class="quick-action-card flex items-center p-3 rounded-xl">
                                        <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center text-indigo-600 mr-3">
                                            <i class="fas fa-user-plus text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">New User Account</p>
                                            <p class="text-xs text-gray-500">Add an employee to the system</p>
                                        </div>
                                    </a>
                                    <a href="reports.php" class="quick-action-card flex items-center p-3 rounded-xl">
                                        <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center text-green-600 mr-3">
                                            <i class="fas fa-check-double text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-gray-800">Review Submissions</p>
                                            <p class="text-xs text-gray-500">Check pending accomplishment reports</p>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>
    
    <?php /*
  <div class="lg:col-span-1 space-y-8">
  ...
  </div>
  */?>

    <script>
        // Use a more vibrant color palette
        const palette = {
            primary: '#4f46e5',
            success: '#10b981',
            warning: '#f59e0b',
            danger: '#ef4444',
            info: '#3b82f6',
            gray: '#94a3b8'
        };

        // User Distribution Chart
        new Chart(document.getElementById('userDistributionChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_keys($usersByRole)); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_values($usersByRole)); ?>,
                    backgroundColor: [palette.primary, palette.success, palette.warning, palette.info],
                    hoverOffset: 4,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'right',
                        labels: { boxWidth: 10, usePointStyle: true, font: { size: 10 } }
                    }
                }
            }
        });

        // Report Status Chart
        new Chart(document.getElementById('reportStatusChart'), {
            type: 'bar',
            data: {
                labels: ['Approved', 'Reviewed', 'Submitted', 'Draft'],
                datasets: [{
                    label: 'Report Count',
                    data: [
                        <?php echo $stats['approved']; ?>,
                        <?php echo isset($stats['reviewed']) ? $stats['reviewed'] : 5; ?>,
                        <?php echo $stats['submitted']; ?>,
                        <?php echo $stats['draft']; ?>
                    ],
                    backgroundColor: [palette.success, palette.warning, palette.primary, palette.gray],
                    borderRadius: 8,
                    barThickness: 32
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>
