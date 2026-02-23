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

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterEmployee = isset($_GET['employee']) ? $_GET['employee'] : '';
$searchName = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Check if any filter is active
$hasActiveFilter = !empty($filterStatus) || !empty($filterEmployee) || !empty($searchName);

// Get pagination and entries per page parameters (only used when no filter is active)
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
    $itemsPerPage = 10;
}

$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1)
    $currentPage = 1;

// Build WHERE clause
$whereClause = "WHERE 1=1";

if (!empty($searchName)) {
    $whereClause .= " AND (u.first_name LIKE '%$searchName%' OR u.last_name LIKE '%$searchName%' OR CONCAT(u.first_name, ' ', u.last_name) LIKE '%$searchName%')";
}

if (!empty($filterStatus)) {
    $filterStatusEsc = $conn->real_escape_string($filterStatus);
    $whereClause .= " AND ar.status = '$filterStatusEsc'";
}

if (!empty($filterEmployee)) {
    $filterEmployeeEsc = $conn->real_escape_string($filterEmployee);
    $whereClause .= " AND ar.employee_id = '$filterEmployeeEsc'";
}

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM accomplishment_reports ar
               LEFT JOIN users u ON ar.employee_id = u.id
               $whereClause";
$totalReports = $conn->query($countQuery)->fetch_assoc()['total'];

// Calculate pagination
$totalPages = $itemsPerPage > 0 ? ceil($totalReports / $itemsPerPage) : 1;
if ($currentPage > $totalPages && $totalPages > 0)
    $currentPage = $totalPages;

$offset = ($currentPage - 1) * ($itemsPerPage > 0 ? $itemsPerPage : 0);

// Get reports with pagination
$limitClause = $itemsPerPage > 0 ? "LIMIT $offset, $itemsPerPage" : "";

$query = "SELECT ar.*, u.first_name, u.last_name, u.department, u.position, u.employee_id as emp_id,
          pr.average_rating
          FROM accomplishment_reports ar
          LEFT JOIN users u ON ar.employee_id = u.id
          LEFT JOIN performance_ratings pr ON ar.id = pr.report_id
          $whereClause
          GROUP BY ar.id
          ORDER BY ar.created_at DESC
          $limitClause";

$reports = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}

// Get employees for filter dropdown
$employeesQuery = "SELECT id, first_name, last_name FROM users WHERE role = 'employee' ORDER BY first_name, last_name";
$employees = [];
$empResult = $conn->query($employeesQuery);
while ($row = $empResult->fetch_assoc()) {
    $employees[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Overview</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
</head>
<body class="bg-gray-100">
    <?php $activePage = 'reports_table'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Reports Overview';
$pageIcon = 'fas fa-table';
$totalCount = $totalReports;
$countLabel = 'reports';
$countIcon = 'fa-file-alt';

// Build print URL with current filters
$printUrl = 'reports_table_print.php';
$printParams = [];
if (!empty($filterStatus))
    $printParams[] = 'status=' . urlencode($filterStatus);
if (!empty($filterEmployee))
    $printParams[] = 'employee=' . urlencode($filterEmployee);
if (!empty($searchName))
    $printParams[] = 'search=' . urlencode($searchName);
if (!empty($printParams))
    $printUrl .= '?' . implode('&', $printParams);

$headerRight = '<a href="' . $printUrl . '" target="_blank" class="btn-primary">
                <i class="fas fa-print mr-2"></i>Print Reports
            </a>';

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
                    <form method="GET" id="filterForm" class="flex gap-4 flex-wrap items-end">
                        <div class="flex-1 min-w-xs">
                            <label class="form-label">Search Name</label>
                            <input type="text" name="search" class="form-input" placeholder="Search by name..." value="<?php echo htmlspecialchars($searchName); ?>" onkeyup="debounceSearch()">
                        </div>
                        <div class="flex-1 min-w-xs">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input" onchange="document.getElementById('filterForm').submit()">
                                <option value="">All Statuses</option>
                                <option value="draft" <?php echo $filterStatus == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                <option value="submitted" <?php echo $filterStatus == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="reviewed" <?php echo $filterStatus == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                <option value="approved" <?php echo $filterStatus == 'approved' ? 'selected' : ''; ?>>Approved</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-xs">
                            <label class="form-label">Employee</label>
                            <select name="employee" class="form-input" onchange="document.getElementById('filterForm').submit()">
                                <option value="">All Employees</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?php echo $emp['id']; ?>" <?php echo $filterEmployee == $emp['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($emp['first_name'] . ' ' . $emp['last_name']); ?>
                                    </option>
                                <?php
endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <a href="<?php echo $printUrl; ?>" target="_blank" class="btn-primary" style="background: var(--color-success) !important;">
                                <i class="fas fa-print mr-2"></i>Print
                            </a>
                            <a href="reports_table.php" class="btn-secondary">
                                <i class="fas fa-redo mr-2"></i>Clear
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Reports Table -->
                <div class="card">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr style="border-bottom: 2px solid #e5e7eb;">
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Employee Name</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Employee ID</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Department</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Reporting Period</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Created Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="table-row">
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-blue-400 to-blue-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                                        <?php echo substr($report['first_name'], 0, 1) . substr($report['last_name'], 0, 1); ?>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-gray-600 text-xs"><?php echo htmlspecialchars($report['emp_id']); ?></td>
                                            <td class="py-3 px-3 text-gray-600 text-xs"><?php echo htmlspecialchars($report['department']); ?></td>
                                            <td class="py-3 px-3 text-gray-600 text-xs whitespace-nowrap">
                                                <div class="text-xs">
                                                    <div><?php echo date('M d, Y', strtotime($report['reporting_period_start'])); ?></div>
                                                    <div class="text-gray-400">to</div>
                                                    <div><?php echo date('M d, Y', strtotime($report['reporting_period_end'])); ?></div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <span class="status-badge status-<?php echo $report['status']; ?>">
                                                    <?php echo $report['status']; ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap text-xs">
                                                <?php echo date('M d, Y', strtotime($report['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php
    endforeach; ?>
                                <?php
else: ?>
                                    <tr>
                                        <td colspan="6" class="py-12 px-4 text-center text-gray-500">
                                            <i class="fas fa-inbox text-4xl mb-3 block"></i>
                                            <p class="text-lg font-medium">No reports found</p>
                                            <p class="text-sm">Try adjusting your filters</p>
                                        </td>
                                    </tr>
                                <?php
endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination and Entries Control -->
                    <?php if ($totalReports > 0): ?>
                    <div class="flex flex-col md:flex-row justify-between items-center mt-6 gap-4">
                        <div class="text-sm text-gray-600">
                            Showing <span class="font-semibold"><?php echo min(($currentPage - 1) * ($itemsPerPage > 0 ? $itemsPerPage : $totalReports) + 1, $totalReports); ?></span>
                            to
                            <span class="font-semibold"><?php
    $end = $currentPage * ($itemsPerPage > 0 ? $itemsPerPage : $totalReports);
    echo min($end, $totalReports);
?></span>
                            of <span class="font-semibold"><?php echo $totalReports; ?></span> reports
                        </div>

                        <div class="flex gap-2 items-center">
                            <label class="text-sm text-gray-700 font-semibold">Show entries:</label>
                            <select onchange="updatePerPage(this.value)" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
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
        if (!empty($filterStatus))
            $filterParams .= '&status=' . htmlspecialchars($filterStatus);
        if (!empty($filterEmployee))
            $filterParams .= '&employee=' . intval($filterEmployee);
?>
                            <?php if ($currentPage > 1): ?>
                                <a href="reports_table.php?page=1&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">First</a>
                                <a href="reports_table.php?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Previous</a>
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
                                        <a href="reports_table.php?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100"><?php echo $i; ?></a>
                                    <?php
            endif;
        endfor;

        if ($end < $totalPages): ?>
                                    <span class="px-3 py-2 text-gray-500">...</span>
                                <?php
        endif;

        if ($currentPage < $totalPages): ?>
                                    <a href="reports_table.php?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Next</a>
                                    <a href="reports_table.php?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Last</a>
                                <?php
        endif; ?>
                        </div>
                    <?php
    endif; ?>
                    <?php
endif; ?>
                </div>
            </main>
        </div>
    </div>

    <script>
    // Helper function to preserve filters in URL
    function getFilterParams() {
        const urlParams = new URLSearchParams(window.location.search);
        let params = '';
        if (urlParams.get('status')) params += '&status=' + urlParams.get('status');
        if (urlParams.get('employee')) params += '&employee=' + urlParams.get('employee');
        return params;
    }

    function updatePerPage(value) {
        window.location.href = 'reports_table.php?per_page=' + value + '&page=1' + getFilterParams();
    }

    // Debounce search function
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            document.getElementById('filterForm').submit();
        }, 500);
    }
    </script>
</body>
</html>