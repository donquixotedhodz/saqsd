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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        .setting-section {
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .setting-section:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'settings'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Settings';
$pageIcon = 'fas fa-cog';
include 'includes/header.php';
?>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <!-- System Information -->
                <div class="card max-w-2xl mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2" style="color: var(--color-primary);"></i>System Information
                    </h3>
                    
                    <div class="setting-section">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600 text-sm">System Name</p>
                                <p class="text-lg font-semibold text-gray-800">Accomplishment Report System</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Version</p>
                                <p class="text-lg font-semibold text-gray-800">1.0.0</p>
                            </div>
                        </div>
                    </div>

                    <div class="setting-section">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-gray-600 text-sm">Timezone</p>
                                <p class="text-lg font-semibold text-gray-800">Asia/Manila</p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm">Database</p>
                                <p class="text-lg font-semibold text-gray-800">MySQL</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- System Status -->
                <div class="card max-w-2xl mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-heartbeat mr-2" style="color: var(--color-primary);"></i>System Status
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">Database Connection</span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">Web Server</span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Running
                            </span>
                        </div>
                        <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                            <span class="text-gray-700">File Uploads</span>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-green-200 text-green-800">
                                <i class="fas fa-check-circle mr-1"></i>Enabled
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Maintenance -->
                <div class="card max-w-2xl mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-tools mr-2" style="color: var(--color-primary);"></i>Maintenance
                    </h3>
                    
                    <div class="space-y-3">
                        <p class="text-gray-600 text-sm">Perform system maintenance tasks</p>
                        <button class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded transition">
                            <i class="fas fa-refresh mr-2"></i>Clear Cache
                        </button>
                        <a href="export_database.php" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded ml-2 transition inline-block">
                            <i class="fas fa-download mr-2"></i>Export Database
                        </a>
                        <a href="import_database.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded ml-2 transition inline-block">
                            <i class="fas fa-upload mr-2"></i>Import Database
                        </a>
                        <a href="../backend/seed_admin_web.php" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded ml-2 transition inline-block" target="_blank">
                            <i class="fas fa-user-shield mr-2"></i>Seed Admin
                        </a>
                    </div>
                </div>

                <!-- Security -->
                <div class="card max-w-2xl">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-lock mr-2" style="color: var(--color-primary);"></i>Security
                    </h3>
                    
                    <div class="space-y-3">
                        <div class="p-3 bg-gray-50 rounded">
                            <p class="text-gray-700 font-semibold">Recommended Security Practices</p>
                            <ul class="text-sm text-gray-600 mt-2 space-y-1 ml-4">
                                <li>✓ Regularly backup your database</li>
                                <li>✓ Monitor audit logs regularly</li>
                                <li>✓ Keep user passwords strong</li>
                                <li>✓ Review access permissions regularly</li>
                                <li>✓ Update system files as needed</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
