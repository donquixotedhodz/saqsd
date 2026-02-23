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

$successMessage = '';
$errorMessage = '';

// Get pagination and entries per page parameters
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
    $itemsPerPage = 10;
}

$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1)
    $currentPage = 1;

// Get all distinct departments
$deptQuery = "SELECT DISTINCT department FROM users ORDER BY department ASC";
$deptResult = $conn->query($deptQuery);
$allDepartments = [];
while ($row = $deptResult->fetch_assoc()) {
    $allDepartments[] = $row['department'];
}

// Calculate pagination
$totalDepts = count($allDepartments);
$totalPages = $itemsPerPage > 0 ? ceil($totalDepts / $itemsPerPage) : 1;
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

// Get paginated departments
$offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
$limit = $itemsPerPage > 0 ? $itemsPerPage : $totalDepts;
$departments = array_slice($allDepartments, $offset, $limit);

// Count users per department
$deptCountQuery = "SELECT department, COUNT(*) as count FROM users GROUP BY department ORDER BY department ASC";
$deptCountResult = $conn->query($deptCountQuery);
$deptCount = [];
while ($row = $deptCountResult->fetch_assoc()) {
    $deptCount[$row['department']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Departments - NEA Abolran</title>
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
        .dept-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            border-left: 4px solid var(--color-primary);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'departments'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Departments';
$pageIcon = 'fas fa-building';
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

                <!-- Departments Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($departments as $dept): ?>
                        <div class="dept-card">
                            <div class="flex justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($dept); ?></h3>
                                </div>
                                <i class="fas fa-building text-2xl text-gray-300"></i>
                            </div>
                            <div class="border-t pt-4">
                                <p class="text-gray-600 text-sm">
                                    <i class="fas fa-users mr-2" style="color: var(--color-primary);"></i>
                                    <span class="font-semibold"><?php echo $deptCount[$dept]; ?></span> Employees
                                </p>
                            </div>
                            <div class="mt-4">
                                <a href="users.php?department=<?php echo urlencode($dept); ?>" class="text-blue-600 hover:text-blue-800 text-sm">
                                    <i class="fas fa-arrow-right mr-2"></i>View Members
                                </a>
                            </div>
                        </div>
                    <?php
endforeach; ?>
                </div>

                <!-- Pagination and Entries Control -->
                <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-semibold"><?php echo($currentPage - 1) * ($itemsPerPage > 0 ? $itemsPerPage : $totalDepts) + 1; ?></span> 
                        to 
                        <span class="font-semibold"><?php
$end = $currentPage * ($itemsPerPage > 0 ? $itemsPerPage : $totalDepts);
echo min($end, $totalDepts);
?></span> 
                        of <span class="font-semibold"><?php echo $totalDepts; ?></span> departments
                    </div>
                    
                    <div class="flex gap-2 items-center">
                        <label class="text-sm text-gray-700 font-semibold">Show entries:</label>
                        <select onchange="window.location.href='departments.php?per_page=' + this.value + '&page=1'" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                            <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $itemsPerPage == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="0" <?php echo $itemsPerPage == 0 ? 'selected' : ''; ?>>All</option>
                        </select>
                    </div>
                </div>
                
                <!-- Pagination Links -->
                <?php if ($totalPages > 1): ?>
                    <div class="flex justify-center gap-1 mt-6">
                        <?php if ($currentPage > 1): ?>
                            <a href="departments.php?page=1&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">First</a>
                            <a href="departments.php?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Previous</a>
                        <?php
    endif; ?>
                        
                        <?php
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1): ?>
                                <span class="px-3 py-2 text-gray-500">...</span>
                            <?php
    endif;

    for ($i = $start; $i <= $end; $i++):
        if ($i == $currentPage): ?>
                                    <span class="px-3 py-2 bg-blue-500 text-white rounded-lg text-sm font-semibold"><?php echo $i; ?></span>
                                <?php
        else: ?>
                                    <a href="departments.php?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100"><?php echo $i; ?></a>
                                <?php
        endif;
    endfor;

    if ($end < $totalPages): ?>
                                <span class="px-3 py-2 text-gray-500">...</span>
                            <?php
    endif;

    if ($currentPage < $totalPages): ?>
                                <a href="departments.php?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Next</a>
                                <a href="departments.php?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Last</a>
                            <?php
    endif; ?>
                    </div>
                <?php
endif; ?>

                <!-- Department Statistics -->
                <div class="card mt-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Department Summary</h3>
                    <div class="space-y-2">
                        <?php foreach ($allDepartments as $dept): ?>
                                <div class="w-64 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo($deptCount[$dept] / array_sum($deptCount)) * 100; ?>%"></div>
                                </div>
                                <span class="text-gray-800 font-semibold w-12 text-right"><?php echo $deptCount[$dept]; ?></span>
                            </div>
                        <?php
endforeach; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
