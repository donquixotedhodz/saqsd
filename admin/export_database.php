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

// Function to get CREATE TABLE statements
function getCreateTableStatements($conn) {
    $sql = "SHOW TABLES";
    $result = $conn->query($sql);
    $tables = [];

    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $createStatements = [];

    foreach ($tables as $table) {
        $sql = "SHOW CREATE TABLE `$table`";
        $result = $conn->query($sql);
        $row = $result->fetch_array();
        $createStatements[] = $row[1] . ";\n\n";
    }

    return $createStatements;
}

// Function to get INSERT statements for all tables
function getInsertStatements($conn) {
    $sql = "SHOW TABLES";
    $result = $conn->query($sql);
    $tables = [];

    while ($row = $result->fetch_array()) {
        $tables[] = $row[0];
    }

    $insertStatements = [];

    foreach ($tables as $table) {
        // Get column names
        $columnsSql = "DESCRIBE `$table`";
        $columnsResult = $conn->query($columnsSql);
        $columns = [];

        while ($colRow = $columnsResult->fetch_assoc()) {
            $columns[] = $colRow['Field'];
        }

        // Get data
        $dataSql = "SELECT * FROM `$table`";
        $dataResult = $conn->query($dataSql);

        if ($dataResult->num_rows > 0) {
            $insertStatements[] = "-- Data for table `$table`\n";

            while ($row = $dataResult->fetch_assoc()) {
                $values = [];
                foreach ($columns as $column) {
                    $value = $row[$column];
                    if ($value === null) {
                        $values[] = 'NULL';
                    } else {
                        // Escape special characters and wrap in quotes
                        $values[] = "'" . $conn->real_escape_string($value) . "'";
                    }
                }
                $insertStatements[] = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
            }
            $insertStatements[] = "\n";
        }
    }

    return $insertStatements;
}

// Generate the export
$exportContent = "-- Accomplishment Report System Database Export\n";
$exportContent .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
$exportContent .= "-- Database: accomplishment_report_system\n\n";

$exportContent .= "-- Create Database\n";
$exportContent .= "CREATE DATABASE IF NOT EXISTS accomplishment_report_system;\n";
$exportContent .= "USE accomplishment_report_system;\n\n";

// Add CREATE TABLE statements
$createStatements = getCreateTableStatements($conn);
foreach ($createStatements as $statement) {
    $exportContent .= $statement;
}

// Add INSERT statements
$insertStatements = getInsertStatements($conn);
foreach ($insertStatements as $statement) {
    $exportContent .= $statement;
}

// Set headers for download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="accomplishment_report_system_backup_' . date('Y-m-d_H-i-s') . '.sql"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $exportContent;
exit();
?>