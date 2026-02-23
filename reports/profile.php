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
    <title>Profile</title>
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
$pageTitle = 'My Profile';
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

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Profile Card -->
                    <div class="md:col-span-1">
                        <div class="card text-center">
                            <div class="w-24 h-24 mx-auto mb-4">
                                <?php if (!empty($user['profile_photo']) && file_exists('../uploads/profiles/' . $user['profile_photo'])): ?>
                                    <img src="../uploads/profiles/<?php echo htmlspecialchars($user['profile_photo']); ?>?t=<?php echo time(); ?>" alt="Profile Photo" class="w-24 h-24 rounded-full object-cover border-4 border-gray-200">
                                <?php
else: ?>
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center">
                                        <i class="fas fa-user text-white text-3xl"></i>
                                    </div>
                                <?php
endif; ?>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($user['position']); ?></p>
                            <p class="text-gray-500 text-sm mt-4">Employee ID: <?php echo htmlspecialchars($user['employee_id']); ?></p>
                            
                            <div class="mt-6 pt-6 border-t space-y-3">
                                <button onclick="openProfileModal()" class="w-full btn-primary">
                                    <i class="fas fa-edit mr-2"></i>Edit Profile
                                </button>
                                <button onclick="openPasswordModal()" class="w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded-lg transition-colors">
                                    <i class="fas fa-lock mr-2"></i>Change Password
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Profile Details -->
                    <div class="md:col-span-2 space-y-4">
                        <!-- Personal Information -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-user" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Personal Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">First Name:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['first_name']); ?></span>
                                </div>
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">Last Name:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['last_name']); ?></span>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span class="text-gray-600">Email:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['email']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Employment Information -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-briefcase" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Employment Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">Employee ID:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['employee_id']); ?></span>
                                </div>
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">Department:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['department']); ?></span>
                                </div>
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">Position:</span>
                                    <span class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['position']); ?></span>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span class="text-gray-600">Role:</span>
                                    <span class="font-semibold text-gray-800 capitalize"><?php echo $user['role']; ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information -->
                        <div class="card">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">
                                <i class="fas fa-lock" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Account Information
                            </h3>
                            <div class="space-y-3">
                                <div class="flex justify-between items-start border-b pb-2">
                                    <span class="text-gray-600">Member Since:</span>
                                    <span class="font-semibold text-gray-800"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                                </div>
                                <div class="flex justify-between items-start">
                                    <span class="text-gray-600">Last Updated:</span>
                                    <span class="font-semibold text-gray-800"><?php echo date('F j, Y \a\t g:i A', strtotime($user['updated_at'])); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modals -->
                <!-- Profile Edit Modal -->
                <div id="profileModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-user-edit" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Edit Personal Information
                            </h3>
                            <button onclick="closeProfileModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

                        <form method="POST" enctype="multipart/form-data" class="space-y-4">
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

                            <!-- Profile Photo Section -->
                            <div class="border-t pt-4 mt-4">
                                <h4 class="text-md font-semibold text-gray-800 mb-3">
                                    <i class="fas fa-camera" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Profile Photo
                                </h4>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Upload New Photo</label>
                                    <input type="file" id="modal_profile_photo" name="profile_photo" accept="image/*" class="hidden">
                                    <div class="flex items-center gap-4">
                                        <button type="button" onclick="document.getElementById('modal_profile_photo').click()" class="btn-primary">
                                            <i class="fas fa-upload mr-2"></i>Choose Photo
                                        </button>
                                        <span id="modal-file-name" class="text-sm text-gray-600">No file selected</span>
                                    </div>
                                    <p class="text-xs text-gray-600 mt-2">Supported formats: JPG, PNG, GIF. Maximum size: 5MB</p>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" name="update_profile" class="btn-primary">
                                    <i class="fas fa-save mr-2"></i>Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Password Change Modal -->
                <div id="passwordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
                    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-lock" style="color: var(--color-primary); margin-right: 0.5rem;"></i>Change Password
                            </h3>
                            <button onclick="closePasswordModal()" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xl"></i>
                            </button>
                        </div>

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

                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" onclick="closePasswordModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                    Cancel
                                </button>
                                <button type="submit" name="update_password" class="btn-primary">
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
            document.getElementById('profileModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function openPasswordModal() {
            document.getElementById('passwordModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.add('hidden');
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