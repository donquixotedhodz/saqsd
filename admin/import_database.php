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

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_sql'])) {
        // Handle direct SQL import
        $sql = trim($_POST['sql_content']);

        if (empty($sql)) {
            $message = 'Please enter SQL content to import.';
            $messageType = 'error';
        }
        else {
            // Execute the SQL
            $deleteDatabase = isset($_POST['delete_database_text']) && $_POST['delete_database_text'] == '1';
            $dropTables = isset($_POST['drop_tables_text']) && $_POST['drop_tables_text'] == '1';
            $truncateTables = isset($_POST['truncate_tables_text']) && $_POST['truncate_tables_text'] == '1';
            try {
                if (executeSqlImport($conn, $sql, $dropTables, $truncateTables, $deleteDatabase)) {
                    $message = 'Database imported successfully!';
                    $messageType = 'success';
                }
            }
            catch (Exception $e) {
                $message = 'Error importing database: ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
    elseif (isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
        // Handle file upload
        $fileTmpPath = $_FILES['sql_file']['tmp_name'];
        $fileName = $_FILES['sql_file']['name'];
        $fileSize = $_FILES['sql_file']['size'];

        // Check file extension
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExtension !== 'sql') {
            $message = 'Please upload a valid SQL file (.sql extension only).';
            $messageType = 'error';
        }
        elseif ($fileSize > 10 * 1024 * 1024) { // 10MB limit
            $message = 'File size too large. Maximum allowed size is 10MB.';
            $messageType = 'error';
        }
        else {
            // Read file content
            $sql = file_get_contents($fileTmpPath);

            if ($sql === false) {
                $message = 'Error reading the uploaded file.';
                $messageType = 'error';
            }
            else {
                // Execute the SQL
                $deleteDatabase = isset($_POST['delete_database']) && $_POST['delete_database'] == '1';
                $dropTables = isset($_POST['drop_tables']) && $_POST['drop_tables'] == '1';
                $truncateTables = isset($_POST['truncate_tables']) && $_POST['truncate_tables'] == '1';
                try {
                    if (executeSqlImport($conn, $sql, $dropTables, $truncateTables, $deleteDatabase)) {
                        $message = 'Database imported successfully from file: ' . htmlspecialchars($fileName);
                        $messageType = 'success';
                    }
                }
                catch (Exception $e) {
                    $message = 'Error importing database from file: ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }
}

function executeSqlImport($conn, $sql, $dropTables = false, $truncateTables = false, $deleteDatabase = false)
{
    // Split SQL into statements and clean them
    $statements = explode(';', $sql);

    // Clean and categorize statements
    $createDbStatements = [];
    $createTableStatements = [];
    $otherStatements = [];

    foreach ($statements as $statement) {
        $statement = trim($statement);
        // Remove comments
        $statement = preg_replace('/--.*$/m', '', $statement);
        $statement = preg_replace('/\/\*.*?\*\//s', '', $statement);
        $statement = trim($statement);

        // Skip empty statements
        if (empty($statement))
            continue;

        // Categorize statements
        if (preg_match('/^CREATE DATABASE/i', $statement)) {
            $createDbStatements[] = $statement;
        }
        elseif (preg_match('/^CREATE TABLE/i', $statement)) {
            $createTableStatements[] = $statement;
        }
        else {
            $otherStatements[] = $statement;
        }
    }

    $conn->autocommit(false);
    $executedCount = 0;

    try {
        // If delete database is requested, drop the entire database first
        if ($deleteDatabase) {
            try {
                $conn->query("DROP DATABASE IF EXISTS accomplishment_report_system");
                $conn->query("CREATE DATABASE accomplishment_report_system");
                $conn->query("USE accomplishment_report_system");
                $executedCount += 3; // Count these operations
            }
            catch (Exception $e) {
                error_log("Error deleting/recreating database: " . $e->getMessage());
                throw new Exception("Failed to delete and recreate database: " . $e->getMessage());
            }
        }

        // Execute CREATE DATABASE statements (skip if database exists)
        foreach ($createDbStatements as $index => $statement) {
            try {
                if (!$conn->query($statement)) {
                    // If database creation fails (probably already exists), continue
                    error_log("Database creation failed (might already exist): " . $conn->error);
                }
                else {
                    $executedCount++;
                }
            }
            catch (Exception $e) {
                error_log("Database creation error (continuing): " . $e->getMessage());
            }
        }

        // If drop tables is requested, drop existing tables first
        if ($dropTables) {
            // Get all table names from CREATE TABLE statements
            $tablesToDrop = [];
            foreach ($createTableStatements as $statement) {
                if (preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
                    $tablesToDrop[] = $matches[1];
                }
            }

            // Drop tables in reverse order to handle foreign keys
            $tablesToDrop = array_reverse($tablesToDrop);
            foreach ($tablesToDrop as $tableName) {
                try {
                    $conn->query("DROP TABLE IF EXISTS `$tableName`");
                    $executedCount++;
                }
                catch (Exception $e) {
                    error_log("Error dropping table $tableName: " . $e->getMessage());
                }
            }
        }

        // If truncate tables is requested, clear existing data
        if ($truncateTables && !$dropTables) {
            // Get all table names from CREATE TABLE statements
            $tablesToTruncate = [];
            foreach ($createTableStatements as $statement) {
                if (preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
                    $tablesToTruncate[] = $matches[1];
                }
            }

            // Truncate tables (clear data but keep structure)
            foreach ($tablesToTruncate as $tableName) {
                try {
                    $conn->query("TRUNCATE TABLE `$tableName`");
                    $executedCount++;
                }
                catch (Exception $e) {
                    error_log("Error truncating table $tableName: " . $e->getMessage());
                }
            }
        }

        // Execute CREATE TABLE statements
        foreach ($createTableStatements as $index => $statement) {
            // Extract table name
            if (preg_match('/CREATE TABLE `?(\w+)`?/i', $statement, $matches)) {
                $tableName = $matches[1];

                // Check if table exists
                $checkResult = $conn->query("SHOW TABLES LIKE '$tableName'");
                if ($checkResult && $checkResult->num_rows > 0) {
                    // Table exists, skip creation
                    continue;
                }
            }

            if (!$conn->query($statement)) {
                throw new Exception("Error creating table in statement #" . ($index + 1) . ": " . $conn->error . "\nStatement: " . substr($statement, 0, 150) . "...");
            }
            $executedCount++;
        }

        // Execute other statements (INSERT, ALTER, etc.)
        foreach ($otherStatements as $index => $statement) {
            if (!$conn->query($statement)) {
                throw new Exception("Error executing statement #" . ($index + 1) . ": " . $conn->error . "\nStatement: " . substr($statement, 0, 150) . "...");
            }
            $executedCount++;
        }

        $conn->commit();
        return true;
    }
    catch (Exception $e) {
        $conn->rollback();
        error_log("Database import error: " . $e->getMessage());
        // Re-throw with more context
        throw new Exception("Import failed after executing $executedCount statements. " . $e->getMessage());
    }
    finally {
        $conn->autocommit(true);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Import Database</title>
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
        .sidebar-active {
            border-left-color: var(--color-primary);
            background-color: #f3f4f6;
            color: var(--color-primary);
        }
        .success-message {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        .error-message {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'settings'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Import Database';
$pageIcon = 'fas fa-upload';
include 'includes/header.php';
?>

            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <?php if (!empty($message)): ?>
                    <div class="message <?php echo $messageType === 'success' ? 'success-message' : 'error-message'; ?>">
                        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                        <?php echo $message; ?>
                    </div>
                <?php
endif; ?>

                <!-- Import from File -->
                <div class="card max-w-4xl mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-file-upload mr-2" style="color: var(--color-primary);"></i>Import Database from File
                    </h3>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="sql_file" class="block text-sm font-medium text-gray-700 mb-2">
                                Select SQL File
                            </label>
                            <input type="file" id="sql_file" name="sql_file" accept=".sql"
                                   class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Upload a .sql file (max 10MB)</p>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="delete_database" name="delete_database" value="1"
                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <label for="delete_database" class="ml-2 block text-sm text-red-700 font-semibold">
                                ⚠️ Delete entire database before import (nuclear option)
                            </label>
                        </div>
                        <div id="delete_warning" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                            <strong>⚠️ WARNING:</strong> This will completely delete the entire database and all its data. This action cannot be undone. Make sure you have a backup of any important data before proceeding.
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="drop_tables" name="drop_tables" value="1"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="drop_tables" class="ml-2 block text-sm text-gray-700">
                                Drop existing tables before import (complete restore)
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="truncate_tables" name="truncate_tables" value="1"
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="truncate_tables" class="ml-2 block text-sm text-gray-700">
                                Clear existing data before import (keep table structure)
                            </label>
                        </div>

                        <script>
                            // Make checkboxes mutually exclusive
                            function setupExclusiveCheckboxes() {
                                const deleteDb = document.getElementById('delete_database');
                                const dropTables = document.getElementById('drop_tables');
                                const truncateTables = document.getElementById('truncate_tables');

                                deleteDb.addEventListener('change', function() {
                                    if (this.checked) {
                                        dropTables.checked = false;
                                        truncateTables.checked = false;
                                        document.getElementById('delete_warning').classList.remove('hidden');
                                    } else {
                                        document.getElementById('delete_warning').classList.add('hidden');
                                    }
                                });

                                dropTables.addEventListener('change', function() {
                                    if (this.checked) {
                                        deleteDb.checked = false;
                                        truncateTables.checked = false;
                                    }
                                });

                                truncateTables.addEventListener('change', function() {
                                    if (this.checked) {
                                        deleteDb.checked = false;
                                        dropTables.checked = false;
                                    }
                                });
                            }
                            setupExclusiveCheckboxes();
                        </script>

                        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded transition">
                            <i class="fas fa-upload mr-2"></i>Import from File
                        </button>
                    </form>
                </div>

                <!-- Import from Text -->
                <div class="card max-w-4xl mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-code mr-2" style="color: var(--color-primary);"></i>Import Database from SQL Text
                    </h3>

                    <form method="POST" class="space-y-4">
                        <div>
                            <label for="sql_content" class="block text-sm font-medium text-gray-700 mb-2">
                                SQL Content
                            </label>
                            <textarea id="sql_content" name="sql_content" rows="15"
                                      class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                                      placeholder="Paste your SQL content here..."></textarea>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="delete_database_text" name="delete_database_text" value="1"
                                   class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded">
                            <label for="delete_database_text" class="ml-2 block text-sm text-red-700 font-semibold">
                                ⚠️ Delete entire database before import (nuclear option)
                            </label>
                        </div>
                        <div id="delete_warning_text" class="hidden mt-2 p-3 bg-red-50 border border-red-200 rounded text-sm text-red-800">
                            <strong>⚠️ WARNING:</strong> This will completely delete the entire database and all its data. This action cannot be undone. Make sure you have a backup of any important data before proceeding.
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="drop_tables_text" name="drop_tables_text" value="1"
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="drop_tables_text" class="ml-2 block text-sm text-gray-700">
                                Drop existing tables before import (complete restore)
                            </label>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" id="truncate_tables_text" name="truncate_tables_text" value="1"
                                   class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded">
                            <label for="truncate_tables_text" class="ml-2 block text-sm text-gray-700">
                                Clear existing data before import (keep table structure)
                            </label>
                        </div>

                        <script>
                            // Make checkboxes mutually exclusive for text import
                            function setupExclusiveCheckboxesText() {
                                const deleteDb = document.getElementById('delete_database_text');
                                const dropTables = document.getElementById('drop_tables_text');
                                const truncateTables = document.getElementById('truncate_tables_text');

                                deleteDb.addEventListener('change', function() {
                                    if (this.checked) {
                                        dropTables.checked = false;
                                        truncateTables.checked = false;
                                        document.getElementById('delete_warning_text').classList.remove('hidden');
                                    } else {
                                        document.getElementById('delete_warning_text').classList.add('hidden');
                                    }
                                });

                                dropTables.addEventListener('change', function() {
                                    if (this.checked) {
                                        deleteDb.checked = false;
                                        truncateTables.checked = false;
                                    }
                                });

                                truncateTables.addEventListener('change', function() {
                                    if (this.checked) {
                                        deleteDb.checked = false;
                                        dropTables.checked = false;
                                    }
                                });
                            }
                            setupExclusiveCheckboxesText();
                        </script>

                        <button type="submit" name="import_sql" class="bg-green-500 hover:bg-green-600 text-white px-6 py-2 rounded transition">
                            <i class="fas fa-play mr-2"></i>Execute SQL
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="card max-w-4xl">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-info-circle mr-2" style="color: var(--color-primary);"></i>Instructions
                    </h3>

                    <div class="space-y-3 text-sm text-gray-600">
                        <p><strong>Important:</strong> Database import will execute SQL statements that may modify your existing data. Make sure you have a backup before proceeding.</p>

                        <div class="bg-yellow-50 border border-yellow-200 rounded p-3">
                            <p class="font-semibold text-yellow-800">⚠️ Warning:</p>
                            <ul class="list-disc list-inside mt-1 text-yellow-700">
                                <li>This will execute all SQL statements in the file</li>
                                <li>Existing tables will be dropped and recreated if included in the SQL</li>
                                <li>Make sure the SQL file is from a trusted source</li>
                                <li>Consider testing on a development environment first</li>
                            </ul>
                        </div>

                        <p><strong>Supported operations:</strong></p>
                        <ul class="list-disc list-inside ml-4">
                            <li>CREATE DATABASE / TABLE statements</li>
                            <li>INSERT statements</li>
                            <li>ALTER TABLE statements</li>
                            <li>DROP statements</li>
                            <li>Other DDL and DML operations</li>
                        </ul>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>