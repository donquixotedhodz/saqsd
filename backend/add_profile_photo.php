<?php
require_once '../backend/config.php';

$sql = 'ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL';

if ($conn->query($sql) === TRUE) {
    echo 'Profile photo column added successfully';
} else {
    echo 'Error: ' . $conn->error;
}

$conn->close();
?>