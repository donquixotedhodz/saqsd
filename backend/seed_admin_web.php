<?php
session_start();

// Simple admin seeding script - accessible via web browser
// Access this at: http://localhost/SAQSD/backend/seed_admin_web.php

require_once 'config.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['seed_admin'])) {
    // Check if admin user already exists
    $checkQuery = "SELECT id FROM users WHERE employee_id = 'admin' OR email = 'admin@saqsd.com'";
    $result = $conn->query($checkQuery);

    if ($result && $result->num_rows > 0) {
        $message = "Admin user already exists. Updating password...";

        // Update existing admin user with new password
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $updateQuery = "UPDATE users SET
            password = ?,
            first_name = 'System',
            last_name = 'Administrator',
            email = 'admin@saqsd.com',
            department = 'Administration',
            position = 'System Administrator',
            role = 'admin',
            updated_at = NOW()
            WHERE employee_id = 'admin' OR email = 'admin@saqsd.com'";

        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $hashedPassword);

        if ($stmt->execute()) {
            $message = "✅ Admin user updated successfully!<br>Username: <strong>admin</strong><br>Password: <strong>admin123</strong>";
            $messageType = 'success';
        } else {
            $message = "❌ Error updating admin user: " . $conn->error;
            $messageType = 'error';
        }
    } else {
        $message = "Creating new admin user...";

        // Create new admin user
        $employeeId = 'admin';
        $firstName = 'System';
        $lastName = 'Administrator';
        $email = 'admin@saqsd.com';
        $department = 'Administration';
        $position = 'System Administrator';
        $role = 'admin';
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);

        $insertQuery = "INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("ssssssss", $employeeId, $firstName, $lastName, $email, $department, $position, $role, $hashedPassword);

        if ($stmt->execute()) {
            $message = "✅ Admin user created successfully!<br>Username: <strong>admin</strong><br>Password: <strong>admin123</strong><br>Email: <strong>admin@saqsd.com</strong>";
            $messageType = 'success';
        } else {
            $message = "❌ Error creating admin user: " . $conn->error;
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Admin User</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            padding: 2rem;
            max-width: 500px;
            width: 100%;
        }
        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="text-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Seed Admin User</h1>
            <p class="text-gray-600">Create or update the admin user with known credentials</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="message <?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-2">Admin Credentials</h3>
            <div class="space-y-1 text-sm text-blue-700">
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
                <p><strong>Email:</strong> admin@saqsd.com</p>
                <p><strong>Role:</strong> Administrator</p>
            </div>
        </div>

        <form method="POST" class="space-y-4">
            <button type="submit" name="seed_admin"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 transform hover:scale-105">
                🚀 Seed Admin User
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="../admin/login.php" class="text-blue-600 hover:text-blue-800 text-sm">
                ← Back to Admin Login
            </a>
        </div>

        <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
            <strong>⚠️ Warning:</strong> This will create or update an admin user. Make sure to change the password after first login for security.
        </div>
    </div>
</body>
</html>