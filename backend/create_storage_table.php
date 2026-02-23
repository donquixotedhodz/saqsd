<?php
include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS database_storage (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size INT NOT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id),
    INDEX idx_category (category),
    INDEX idx_created_at (created_at)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table 'database_storage' created successfully!";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
