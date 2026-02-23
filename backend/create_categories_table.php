<?php
include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'categories' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

// Insert default categories
$insertSql = "INSERT IGNORE INTO categories (name) VALUES
('Document'),
('Report'),
('Template'),
('Policy'),
('Training'),
('Other')";

if ($conn->query($insertSql) === TRUE) {
    echo "Default categories inserted successfully!";
} else {
    echo "Error inserting categories: " . $conn->error;
}

$conn->close();
?>