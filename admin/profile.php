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

$successMessage = '';
$errorMessage = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        // Handle profile photo upload if a file was selected
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['profile_photo']['type'], $allowedTypes)) {
                $errorMessage = 'Only JPG, PNG, and GIF files are allowed';
            }
            elseif ($_FILES['profile_photo']['size'] > $maxSize) {
                $errorMessage = 'File size must be less than 5MB';
            }
            else {
                // Create uploads directory if it doesn't exist
                $uploadDir = '../uploads/profiles/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate unique filename
                $fileExtension = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $filePath)) {
                    // Delete old profile photo if exists
                    if (!empty($user['profile_photo']) && file_exists($uploadDir . $user['profile_photo'])) {
                        unlink($uploadDir . $user['profile_photo']);
                    }

                    // Update database
                    $updateSql = "UPDATE users SET profile_photo = ? WHERE id = ?";
                    $stmt = $conn->prepare($updateSql);
                    $stmt->bind_param("si", $fileName, $userId);

                    if ($stmt->execute()) {
                        $photoUpdated = true;
                    }
                    else {
                        $errorMessage = 'Failed to update profile photo in database';
                        // Delete uploaded file if database update failed
                        if (file_exists($filePath)) {
                            unlink($filePath);
                        }
                    }
                    $stmt->close();
                }
                else {
                    $errorMessage = 'Failed to upload profile photo';
                }
            }
        }

        // Handle profile information update
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
            if (isset($photoUpdated) && $photoUpdated) {
                $successMessage .= ' Profile photo updated successfully.';
            }
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
        }
    }

}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - NEA Abolran</title>
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
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #2d3ba6;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'profile'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Admin Profile';
$pageIcon = 'fas fa-user-circle';
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
                            <div class="w-24 h-24 mx-auto mb-4">
                                <?php if (!empty($user['profile_photo']) && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>?t=<?php echo time(); ?>" alt="Profile Photo" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                                <?php
else: ?>
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user-shield text-white text-3xl"></i>
                                    </div>
                                <?php
endif; ?>
                            </div>
                            <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p class="text-gray-600 text-sm"><?php echo htmlspecialchars($user['position']); ?></p>
                            <p class="text-gray-500 text-xs mt-2">Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
                            <div class="border-t pt-4 mt-4">
                                <div class="text-sm">
                                    <p class="text-gray-600">Role: <span class="font-semibold text-gray-800 capitalize">Administrator</span></p>
                                    <p class="text-gray-600 mt-2">Department: <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['department']); ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Content - Action Buttons -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Quick Actions -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-cogs" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Account Management
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <button onclick="openProfileModal()" class="flex items-center justify-center p-6 bg-blue-50 hover:bg-blue-100 rounded-lg border-2 border-blue-200 hover:border-blue-300 transition-colors">
                                    <div class="text-center">
                                        <i class="fas fa-user-edit text-2xl text-blue-600 mb-2"></i>
                                        <p class="font-semibold text-gray-800">Edit Profile</p>
                                        <p class="text-sm text-gray-600">Update personal information</p>
                                    </div>
                                </button>

                                <button onclick="openPasswordModal()" class="flex items-center justify-center p-6 bg-green-50 hover:bg-green-100 rounded-lg border-2 border-green-200 hover:border-green-300 transition-colors">
                                    <div class="text-center">
                                        <i class="fas fa-lock text-2xl text-green-600 mb-2"></i>
                                        <p class="font-semibold text-gray-800">Change Password</p>
                                        <p class="text-sm text-gray-600">Update account security</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modals -->
                <!-- Profile Edit Modal -->
                <div id="profileModal" class="modal-overlay" onclick="closeProfileModal(event)">
                    <div class="modal-content modal-lg" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-user-edit text-indigo-500"></i>
                                Update Profile Information
                            </h3>
                            <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="POST" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">Employee ID</label>
                                        <input type="text" name="employee_id" value="<?php echo htmlspecialchars($user['employee_id']); ?>" required class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required class="form-control">
                                    </div>
                                    <div class="form-group md:col-span-2">
                                        <label class="form-label">Email Address</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Department</label>
                                        <input type="text" name="department" value="<?php echo htmlspecialchars($user['department']); ?>" required class="form-control">
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">Position</label>
                                        <input type="text" name="position" value="<?php echo htmlspecialchars($user['position']); ?>" required class="form-control">
                                    </div>
                                </div>

                                <!-- Profile Photo Section -->
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <label class="form-label">Update Profile Photo</label>
                                    <div class="flex items-center gap-4 mt-2 p-4 bg-gray-50 rounded-xl border border-gray-200 border-dashed">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($user['profile_photo']) && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                                                <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>" class="w-12 h-12 rounded-full object-cover">
                                            <?php
else: ?>
                                                <div class="w-12 h-12 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center">
                                                    <i class="fas fa-camera"></i>
                                                </div>
                                            <?php
endif; ?>
                                        </div>
                                        <div class="flex-grow">
                                            <input type="file" id="modal_profile_photo" name="profile_photo" accept="image/*" class="hidden">
                                            <button type="button" onclick="document.getElementById('modal_profile_photo').click()" class="btn-secondary py-1 text-sm bg-white border">
                                                <i class="fas fa-upload mr-2"></i>Select Image
                                            </button>
                                            <span id="modal-file-name" class="ml-2 text-xs text-gray-500">No file chosen</span>
                                            <p class="text-[10px] text-gray-400 mt-1">Maximum size 5MB. JPG, PNG or GIF.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="closeProfileModal()" class="btn-secondary">Cancel</button>
                                <button type="submit" name="update_profile" class="btn-primary">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Modal -->
                <div id="passwordModal" class="modal-overlay" onclick="closePasswordModal(event)">
                    <div class="modal-content modal-md" onclick="event.stopPropagation()">
                        <div class="modal-header">
                            <h3 class="text-xl font-bold text-gray-800">
                                <i class="fas fa-shield-alt text-green-500"></i>
                                Update Password
                            </h3>
                            <button onclick="closePasswordModal()" class="text-gray-400 hover:text-gray-600 transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <div class="form-group">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="old_password" required class="form-control" placeholder="••••••••">
                                </div>

                                <div class="form-group">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" required class="form-control" placeholder="••••••••">
                                    <p class="text-xs text-gray-400 mt-1">Must be at least 6 characters.</p>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" required class="form-control" placeholder="••••••••">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" onclick="closePasswordModal()" class="btn-secondary">Cancel</button>
                                <button type="submit" name="update_password" class="btn-primary px-6">
                                    <i class="fas fa-key mr-2"></i>Update Password
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Handle file selection display
        document.getElementById('modal_profile_photo').addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'No file selected';
            document.getElementById('modal-file-name').textContent = fileName;
        });

        // Modal functions
        function openProfileModal() {
            document.getElementById('profileModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
            document.body.style.overflow = 'auto';
        }

        // Close modals when clicking outside
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeProfileModal();
            }
        });

        document.getElementById('passwordModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePasswordModal();
            }
        });

        // Close modals with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
                closePasswordModal();
            }
        });
    </script>
</body>
</html>
