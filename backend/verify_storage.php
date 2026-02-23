<?php
include 'config.php';

// Verify the table exists and show its structure
$result = $conn->query("DESCRIBE database_storage");

if ($result) {
    echo "<h2>database_storage Table Structure</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green; font-weight: bold;'>✓ Table exists and is properly configured!</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Error: " . $conn->error . "</p>";
}

// Check if uploads/storage directory exists
$storage_dir = '../uploads/storage';
if (is_dir($storage_dir)) {
    echo "<p style='color: green; font-weight: bold;'>✓ Storage directory exists: " . realpath($storage_dir) . "</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>✗ Storage directory missing</p>";
}

$conn->close();
?>
