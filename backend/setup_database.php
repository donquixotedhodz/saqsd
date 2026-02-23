<?php
include 'config.php';

$sql = file_get_contents('database.sql');

// Split the SQL file into individual statements
$statements = array_filter(array_map('trim', explode(';', $sql)));

foreach ($statements as $statement) {
    if (!empty($statement)) {
        if ($conn->query($statement) === TRUE) {
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        } else {
            echo "Error executing: " . $conn->error . "\n";
        }
    }
}

echo "Database setup completed\n";
?>