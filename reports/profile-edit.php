<?php
session_start();
require_once '../backend/config.php';
require_once '../backend/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId, $conn);

$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $data = array(
            'employee_id' => $_POST['employee_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'department' => $_POST['department'],
            'position' => $_POST['position']
        );

        $result = updateUserProfile($userId, $data, $conn);
        if ($result['success']) {
            $successMessage = $result['message'];
            $user = getUserById($userId, $conn);
        }
        else {
            $errorMessage = $result['message'];
        }
    }

    if (isset($_POST['update_password'])) {
        $oldPassword = $_POST['old_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errorMessage = 'All password fields are required';
        }
        elseif ($newPassword !== $confirmPassword) {
            $errorMessage = 'New passwords do not match';
        }
        elseif (strlen($newPassword) < 6) {
            $errorMessage = 'New password must be at least 6 characters';
        }
        else {
            $result = updateUserPassword($userId, $oldPassword, $newPassword, $conn);
            if ($result['success']) {
                $successMessage = $result['message'];
            }
            else {
                $errorMessage = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - NEA Abolran</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        :root {
            --color-primary: #3c50e0;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background-color: #f7f8fc;
        }
        .card {
            background: white;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #2d3ba6;
            box-shadow: 0 4px 12px rgba(60, 80, 224, 0.3);
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php $activePage = 'profile';
$userInfo = $user; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Edit Profile';
$pageIcon = 'fas fa-user-edit';
$headerRight = '<a href="profile.php" class="btn-secondary"><i class="fas fa-arrow-left mr-2"></i>Back</a>';
include 'includes/header.php';
?>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <!-- Success Message -->
                <?php if (!empty($successMessage)): ?>
                    <div style="background-color: #d1fae5; border-left: 4px solid #10b981; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                        <p class="text-green-800">
                            <i class="fas fa-check-circle mr-2"></i>
                            <?php echo htmlspecialchars($successMessage); ?>
                        </p>
                    </div>
                <?php
endif; ?>

                <!-- Error Message -->
                <?php if (!empty($errorMessage)): ?>
                    <div style="background-color: #fee2e2; border-left: 4px solid #ef4444; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1.5rem;">
                        <p class="text-red-800">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </p>
                    </div>
                <?php
endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Sidebar - User Info Summary -->
                    <div class="lg:col-span-1">
                        <div class="card text-center">
                            <div class="w-24 h-24 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full mx-auto flex items-center justify-center mb-4">
                                <i class="fas fa-user text-white text-3xl"></i>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($user['position']); ?></p>
                            <p class="text-gray-500 text-xs mt-2">Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
                            <div class="border-t pt-4 mt-4">
                                <div class="text-sm">
                                    <p class="text-gray-600">Role: <span class="font-semibold text-gray-800 capitalize"><?php echo $user['role']; ?></span></p>
                                    <p class="text-gray-600 mt-2">Department: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['department']); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Edit Forms -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Profile Information Form -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-user-edit" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Personal Information
                            </h3>
                            
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Employee ID</label>
                                    <input type="text" name="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">First Name</label>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Last Name</label>
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Department</label>
                                        <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Position</label>
                                        <input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                    </div>
                                </div>

                                <div class="pt-4">
                                    <button type="submit" name="update_profile" class="btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save Profile Changes
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Change Password Form -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-lock" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Change Password
                            </h3>
                            
                            <form method="POST" class="space-y-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Current Password</label>
                                    <input type="password" name="old_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">New Password</label>
                                    <input type="password" name="new_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                    <p class="text-xs text-gray-600 mt-1">Minimum 6 characters</p>
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Confirm New Password</label>
                                    <input type="password" name="confirm_password" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2" style="focus:ring-color: var(--color-primary);">
                                </div>

                                <div class="pt-4">
                                    <button type="submit" name="update_password" class="btn-primary">
                                        <i class="fas fa-key mr-2"></i>Update Password
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>