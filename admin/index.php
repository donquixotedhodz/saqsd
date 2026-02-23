<?php
session_start();

// Check if logged in
$isLoggedIn = isset($_SESSION['user_id']);

// Redirect if already logged in
if ($isLoggedIn) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - NEA</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #3c50e0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, var(--color-primary) 0%, #5568d3 100%);
            min-height: 100vh;
        }
        .feature-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .feature-icon {
            font-size: 3rem;
            color: var(--color-primary);
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white bg-opacity-10 backdrop-blur-md py-6">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-white rounded-lg flex items-center justify-center">
                            <img src="images/nealogo.png" alt="NEA Logo" style="width: 100%; height: 100%; object-fit: contain;">
                        </div>
                        <div>
                            <h1 class="text-2xl font-bold text-white">SAQSD</h1>
                            <p class="text-white text-opacity-80 text-sm">Strategic Performance Management System</p>
                        </div>
                    </div>
                    <a href="../reports/login.php" class="bg-white text-blue-600 px-4 py-2 rounded-lg font-semibold hover:bg-opacity-90 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>User Login
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 max-w-7xl mx-auto w-full px-4 py-12">
            <!-- Welcome Section -->
            <div class="bg-white bg-opacity-10 backdrop-blur-md rounded-lg p-8 mb-12 text-white text-center">
                <h2 class="text-4xl font-bold mb-4">Welcome to Admin Portal</h2>
                <p class="text-xl text-opacity-90 mb-8">Comprehensive administration tools for the Accomplishment Report System</p>
                <a href="login.php" class="inline-block bg-white text-blue-600 px-8 py-3 rounded-lg font-bold text-lg hover:bg-opacity-90 transition">
                    <i class="fas fa-arrow-right mr-2"></i>Admin Login
                </a>
            </div>

            <!-- Features Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
                <!-- Dashboard -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Dashboard</h3>
                    <p class="text-gray-600">Overview system statistics, recent activities, and quick actions</p>
                </div>

                <!-- User Management -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">User Management</h3>
                    <p class="text-gray-600">Create, edit, and manage user accounts with role assignment</p>
                </div>

                <!-- Report Management -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Report Management</h3>
                    <p class="text-gray-600">Review, approve, and manage all accomplishment reports</p>
                </div>

                <!-- Audit Logs -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Audit Logs</h3>
                    <p class="text-gray-600">Track all system activities with detailed logging</p>
                </div>

                <!-- Departments -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-building"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Department Management</h3>
                    <p class="text-gray-600">Manage departments and view organizational structure</p>
                </div>

                <!-- Settings -->
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">System Settings</h3>
                    <p class="text-gray-600">Configure system preferences and maintenance tools</p>
                </div>
            </div>

            <!-- Quick Links Section -->
            <div class="bg-white bg-opacity-10 backdrop-blur-md rounded-lg p-8 text-white">
                <h3 class="text-2xl font-bold mb-6">Quick Access</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <a href="login.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 border border-white border-opacity-30 rounded-lg p-4 transition">
                        <i class="fas fa-sign-in-alt mr-3"></i>
                        <span class="font-semibold">Admin Login</span>
                    </a>
                    <a href="../reports/login.php" class="bg-white bg-opacity-20 hover:bg-opacity-30 border border-white border-opacity-30 rounded-lg p-4 transition">
                        <i class="fas fa-users mr-3"></i>
                        <span class="font-semibold">Employee/User Login</span>
                    </a>
                    <a href="README.md" class="bg-white bg-opacity-20 hover:bg-opacity-30 border border-white border-opacity-30 rounded-lg p-4 transition">
                        <i class="fas fa-book mr-3"></i>
                        <span class="font-semibold">Documentation</span>
                    </a>
                    <a href="../" class="bg-white bg-opacity-20 hover:bg-opacity-30 border border-white border-opacity-30 rounded-lg p-4 transition">
                        <i class="fas fa-home mr-3"></i>
                        <span class="font-semibold">Main Dashboard</span>
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-black bg-opacity-20 text-white text-center py-6 mt-12">
            <p>&copy; 2026 Accomplishment Report System. All rights reserved.</p>
            <p class="text-sm text-opacity-75 mt-2">Admin Panel v1.0</p>
        </footer>
    </div>
</body>
</html>
