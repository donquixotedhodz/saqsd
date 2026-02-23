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

// Get pagination and entries per page parameters
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
    $itemsPerPage = 10;
}

$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1)
    $currentPage = 1;

// Get filter parameters
$filterUser = isset($_GET['user']) ? $_GET['user'] : '';
$filterAction = isset($_GET['action']) ? $_GET['action'] : '';
$filterDate = isset($_GET['date']) ? $_GET['date'] : '';

// Build count query
$countQuery = "SELECT COUNT(*) as total FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";

if (!empty($filterUser)) {
    $filterUser = intval($filterUser);
    $countQuery .= " AND al.user_id = $filterUser";
}

if (!empty($filterAction)) {
    $filterAction = $conn->real_escape_string($filterAction);
    $countQuery .= " AND al.action LIKE '%$filterAction%'";
}

if (!empty($filterDate)) {
    $filterDate = $conn->real_escape_string($filterDate);
    $countQuery .= " AND DATE(al.created_at) = '$filterDate'";
}

$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalLogs = $countRow['total'];

// Calculate pagination
$totalPages = $itemsPerPage > 0 ? ceil($totalLogs / $itemsPerPage) : 1;
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

// Build data query with pagination
$offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
$limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";

$query = "SELECT al.*, u.first_name, u.last_name FROM audit_logs al 
          LEFT JOIN users u ON al.user_id = u.id 
          WHERE 1=1";

if (!empty($filterUser)) {
    $query .= " AND al.user_id = $filterUser";
}

if (!empty($filterAction)) {
    $query .= " AND al.action LIKE '%$filterAction%'";
}

if (!empty($filterDate)) {
    $query .= " AND DATE(al.created_at) = '$filterDate'";
}

$query .= " ORDER BY al.created_at DESC $limitClause";

$logsResult = $conn->query($query);
$logs = [];
while ($row = $logsResult->fetch_assoc()) {
    $logs[] = $row;
}

// Get all users for filter
$usersQuery = "SELECT id, first_name, last_name FROM users ORDER BY first_name ASC";
$usersResult = $conn->query($usersQuery);
$allUsers = [];
while ($row = $usersResult->fetch_assoc()) {
    $allUsers[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <style>
        .action-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .action-create { background-color: #ecfdf5; color: #065f46; }
        .action-update { background-color: #eff6ff; color: #1e40af; }
        .action-delete { background-color: #fef2f2; color: #dc2626; }
        .action-default { background-color: #f8fafc; color: #374151; }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'audit-logs'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Audit Logs';
$pageIcon = 'fas fa-history';
include 'includes/header.php';
?>
            
            <!-- Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <!-- Filters -->
                <div class="card mb-6">
                    <form method="GET" class="flex gap-4 flex-wrap">
                        <div class="flex-1 min-w-xs">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">User</label>
                            <select name="user" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2">
                                <option value="">All Users</option>
                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?php echo $u['id']; ?>" <?php echo $filterUser == $u['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <div class="flex-1 min-w-xs">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Action</label>
                            <input type="text" name="action" placeholder="e.g., CREATE, UPDATE" value="<?php echo htmlspecialchars($filterAction); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2">
                        </div>
                        <div class="flex-1 min-w-xs">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Date</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($filterDate); ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2">
                        </div>
                        <div class="flex items-end gap-2 px-1">
                            <button type="submit" class="btn-primary py-2.5">
                                <i class="fas fa-filter mr-2"></i>Filter
                            </button>
                            <a href="audit-logs.php" class="btn-secondary py-2.5">
                                <i class="fas fa-redo mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Logs Table -->
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr style="border-bottom: 2px solid #e5e7eb;">
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">User</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Action</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Entity</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">IP Address</th>
                                    <th class="text-left py-3 px-4 font-semibold text-gray-700">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr class="table-row">
                                            <td class="py-3 px-4">
                                                <div>
                                                    <?php if ($log['first_name']): ?>
                                                        <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name']); ?></p>
                                                    <?php
        else: ?>
                                                        <p class="font-semibold text-gray-800">System</p>
                                                    <?php
        endif; ?>
                                                </div>
                                            </td>
                                            <td class="py-3 px-4">
                                                <span class="action-badge <?php
        if (strpos($log['action'], 'CREATE') !== false)
            echo 'action-create';
        elseif (strpos($log['action'], 'UPDATE') !== false)
            echo 'action-update';
        elseif (strpos($log['action'], 'DELETE') !== false)
            echo 'action-delete';
        else
            echo 'action-default';
?>">
                                                    <?php echo htmlspecialchars($log['action']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600">
                                                <p class="capitalize"><?php echo htmlspecialchars($log['entity_type']); ?> (ID: <?php echo $log['entity_id']; ?>)</p>
                                            </td>
                                            <td class="py-3 px-4 text-gray-600 font-mono text-xs"><?php echo htmlspecialchars($log['ip_address']); ?></td>
                                            <td class="py-3 px-4 text-gray-600">
                                                <span title="<?php echo date('M d, Y H:i:s', strtotime($log['created_at'])); ?>">
                                                    <?php echo date('M d, Y H:i', strtotime($log['created_at'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php
    endforeach; ?>
                                <?php
else: ?>
                                    <tr>
                                        <td colspan="5" class="py-8 px-4 text-center text-gray-600">
                                            <i class="fas fa-inbox text-2xl mb-2"></i>
                                            <p>No audit logs found</p>
                                        </td>
                                    </tr>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination and Entries Control -->
                    <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-semibold"><?php echo($currentPage - 1) * ($itemsPerPage > 0 ? $itemsPerPage : $totalLogs) + 1; ?></span> 
                            to 
                            <span class="font-semibold"><?php
$end = $currentPage * ($itemsPerPage > 0 ? $itemsPerPage : $totalLogs);
echo min($end, $totalLogs);
?></span> 
                            of <span class="font-semibold"><?php echo $totalLogs; ?></span> logs
                        </div>
                        
                        <div class="flex gap-2 items-center">
                            <label class="text-sm text-gray-700 font-semibold">Show entries:</label>
                            <select onchange="var newPerPage = this.value; window.location.href='audit-logs.php?per_page=' + newPerPage + '&page=1<?php echo !empty($filterUser) ? '&user=' . intval($filterUser) : '';
echo !empty($filterAction) ? '&action=' . htmlspecialchars($filterAction) : '';
echo !empty($filterDate) ? '&date=' . htmlspecialchars($filterDate) : ''; ?>'" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
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
                            <?php
    $filterParams = '';
    if (!empty($filterUser))
        $filterParams .= '&user=' . intval($filterUser);
    if (!empty($filterAction))
        $filterParams .= '&action=' . htmlspecialchars($filterAction);
    if (!empty($filterDate))
        $filterParams .= '&date=' . htmlspecialchars($filterDate);
?>
                            <?php if ($currentPage > 1): ?>
                                <a href="audit-logs.php?page=1&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">First</a>
                                <a href="audit-logs.php?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Previous</a>
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
                                        <a href="audit-logs.php?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100"><?php echo $i; ?></a>
                                    <?php
        endif;
    endfor;

    if ($end < $totalPages): ?>
                                    <span class="px-3 py-2 text-gray-500">...</span>
                                <?php
    endif;

    if ($currentPage < $totalPages): ?>
                                    <a href="audit-logs.php?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Next</a>
                                    <a href="audit-logs.php?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Last</a>
                                <?php
    endif; ?>
                        </div>
                    <?php
endif; ?>
    </div>
</body>
</html>
