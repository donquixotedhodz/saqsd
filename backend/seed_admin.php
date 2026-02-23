<?php
// Admin User Seeding Script
// Run this script to create an admin user with known credentials

require_once 'config.php';

echo "Seeding Admin User...\n";

// Check if admin user already exists
$checkQuery = "SELECT id FROM users WHERE employee_id = 'admin' OR email = 'admin@saqsd.com'";
$result = $conn->query($checkQuery);

if ($result && $result->num_rows > 0) {
    echo "Admin user already exists. Updating password...\n";

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
        echo "Admin user updated successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
    } else {
        echo "Error updating admin user: " . $conn->error . "\n";
    }
} else {
    echo "Creating new admin user...\n";

    // Create new admin user
    $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
    $insertQuery = "INSERT INTO users (employee_id, first_name, last_name, email, department, position, role, password) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssssss",
        $employeeId = 'admin',
        $firstName = 'System',
        $lastName = 'Administrator',
        $email = 'admin@saqsd.com',
        $department = 'Administration',
        $position = 'System Administrator',
        $role = 'admin',
        $hashedPassword
    );

    if ($stmt->execute()) {
        echo "Admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Email: admin@saqsd.com\n";
    } else {
        echo "Error creating admin user: " . $conn->error . "\n";
    }
}

echo "\nSeeding completed.\n";
?>