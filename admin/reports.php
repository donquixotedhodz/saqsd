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
    $filterEmployeeInt = intval($filterEmployee);
    $whereClause .= " AND ar.employee_id = $filterEmployeeInt";
}

// Build count query
$countQuery = "SELECT COUNT(*) as total FROM accomplishment_reports ar 
          JOIN users u ON ar.employee_id = u.id 
          $whereClause";

$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalReports = $countRow['total'];

// Calculate pagination (disabled when filter is active - show all results)
if ($hasActiveFilter) {
    $totalPages = 1;
    $limitClause = "";
}
else {
    $totalPages = $itemsPerPage > 0 ? ceil($totalReports / $itemsPerPage) : 1;
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    $offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
    $limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";
}

$query = "SELECT ar.*, u.first_name, u.last_name, u.employee_id as emp_id, u.department, pr.average_rating
          FROM accomplishment_reports ar 
          JOIN users u ON ar.employee_id = u.id 
          LEFT JOIN performance_ratings pr ON ar.id = pr.report_id
          $whereClause
          ORDER BY ar.created_at DESC $limitClause";

$reportsResult = $conn->query($query);
$reports = [];
while ($row = $reportsResult->fetch_assoc()) {
    $reports[] = $row;
}

// Get all employees for filter
$employeesQuery = "SELECT id, first_name, last_name, employee_id FROM users WHERE role IN ('employee', 'supervisor') ORDER BY first_name ASC";
$employeesResult = $conn->query($employeesQuery);
$employees = [];
while ($row = $employeesResult->fetch_assoc()) {
    $employees[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Specific view components */
        .modal-content.modal-large { max-width: 900px !important; }
        .modal-content.modal-xlarge { max-width: 1200px !important; width: 95% !important; }
        .document-viewer {
            width: 100%;
            height: 600px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius-lg);
            background-color: var(--color-background);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }
        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-submitted { background-color: #dbeafe; color: #1e40af; }
        .status-reviewed { background-color: #fef3c7; color: #b45309; }
        .status-approved { background-color: #dcfce7; color: #166534; }
        .info-item {
            padding: 0.75rem;
            background-color: #f9fafb;
            border-radius: 0.5rem;
        }
        .info-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .info-value {
            font-size: 0.95rem;
            color: #111827;
            font-weight: 500;
            margin-top: 0.25rem;
        }
        
        /* Projects table */
        .projects-table {
            width: 100%;
            font-size: 0.875rem;
        }
        .projects-table th {
            background-color: #f3f4f6;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
        }
        .projects-table td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'reports'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'All Reports';
$pageIcon = 'fas fa-file-alt';
$totalCount = $totalReports;
$countLabel = 'reports';
$countIcon = 'fa-file-alt';
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
                            <a href="reports.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
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
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Employee</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Department</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Period</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Status</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Rating</th>
                                    <th class="text-left py-3 px-3 font-semibold text-gray-700">Created</th>
                                    <th class="text-center py-3 px-3 font-semibold text-gray-700">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($reports) > 0): ?>
                                    <?php foreach ($reports as $report): ?>
                                        <tr class="table-row">
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 bg-gradient-to-br from-green-400 to-green-600 rounded-full flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                                        <?php echo substr($report['first_name'], 0, 1) . substr($report['last_name'], 0, 1); ?>
                                                    </div>
                                                    <div>
                                                        <span class="font-medium"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></span>
                                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($report['emp_id']); ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-gray-600 text-xs"><?php echo htmlspecialchars($report['department']); ?></td>
                                            <td class="py-3 px-3 text-gray-600 text-xs whitespace-nowrap">
                                                <?php echo date('M d', strtotime($report['reporting_period_start'])); ?> - <?php echo date('M d, Y', strtotime($report['reporting_period_end'])); ?>
                                            </td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold capitalize status-<?php echo $report['status']; ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3">
                                                <?php if ($report['average_rating']): ?>
                                                    <span class="text-gray-800 font-semibold"><?php echo number_format($report['average_rating'], 2); ?></span>
                                                    <span class="text-gray-500 text-xs">/5</span>
                                                <?php
        else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php
        endif; ?>
                                            </td>
                                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap text-xs"><?php echo date('M d, Y', strtotime($report['created_at'])); ?></td>
                                            <td class="py-3 px-3 whitespace-nowrap">
                                                <div class="flex gap-2 justify-center">
                                                    <button type="button" onclick="viewReport(<?php echo $report['id']; ?>)" class="btn-icon btn-icon-blue" title="View Report">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="report_print.php?id=<?php echo $report['id']; ?>" target="_blank" class="btn-icon btn-icon-indigo" title="Print Report">
                                                        <i class="fas fa-print"></i>
                                                    </a>
                                                    <button type="button" onclick="openStatusModal(<?php echo $report['id']; ?>, '<?php echo $report['status']; ?>', '<?php echo htmlspecialchars(addslashes($report['review_comments'] ?? ''), ENT_QUOTES); ?>')" class="btn-icon btn-icon-green" title="Update Status">
                                                        <i class="fas fa-check-circle"></i>
                                                    </button>
                                                    <?php if (!empty($report['scanned_file'])): ?>
                                                        <button type="button" onclick="viewDocument(<?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['scanned_file']); ?>')" class="btn-icon btn-icon-purple" title="View Document">
                                                            <i class="fas fa-file-pdf"></i>
                                                        </button>
                                                    <?php
        endif; ?>
                                                    <button type="button" onclick="deleteReport(<?php echo $report['id']; ?>, '<?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name'], ENT_QUOTES); ?>')" class="btn-icon btn-icon-red" title="Delete Report">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
    endforeach; ?>
                                <?php
else: ?>
                                    <tr>
                                        <td colspan="7" class="py-12 px-4 text-center text-gray-500">
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
                                <a href="reports.php?page=1&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">First</a>
                                <a href="reports.php?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Previous</a>
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
                                        <a href="reports.php?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100"><?php echo $i; ?></a>
                                    <?php
            endif;
        endfor;

        if ($end < $totalPages): ?>
                                    <span class="px-3 py-2 text-gray-500">...</span>
                                <?php
        endif;

        if ($currentPage < $totalPages): ?>
                                    <a href="reports.php?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Next</a>
                                    <a href="reports.php?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?><?php echo $filterParams; ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-100">Last</a>
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

    <!-- View Report Modal -->
    <div id="viewModal" class="modal-overlay" onclick="closeViewModal(event)">
        <div class="modal-content modal-xl" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-file-alt text-blue-500"></i>
                    Accomplishment Report Details
                </h3>
                <button onclick="closeViewModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body" id="viewModalContent">
                <!-- Content loaded via JavaScript -->
                <div class="text-center py-20">
                    <i class="fas fa-circle-notch fa-spin text-4xl text-blue-500 mb-4"></i>
                    <p class="text-gray-500 font-medium">Fetching report data...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" onclick="closeViewModal()" class="btn-secondary">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div id="statusModal" class="modal-overlay" onclick="closeStatusModal(event)">
        <div class="modal-content modal-md" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-edit text-amber-500"></i>
                    Update Submission Status
                </h3>
                <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="statusForm" onsubmit="submitStatusForm(event)">
                <input type="hidden" name="report_id" id="status_report_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Review Status</label>
                        <select name="status" id="status_value" required class="form-control">
                            <option value="draft">Draft (Return to Employee)</option>
                            <option value="submitted">Submitted (Under Review)</option>
                            <option value="reviewed">Reviewed (Pending Approval)</option>
                            <option value="approved">Approved (Finalized)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Feedback / Comments</label>
                        <textarea name="review_comments" id="status_comments" rows="4" class="form-control" placeholder="Provide feedback or reasons for the status change..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeStatusModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-save mr-2"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Document Viewer Modal -->
    <div id="documentModal" class="modal-overlay" onclick="closeDocumentModal(event)">
        <div class="modal-content modal-xlarge" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-file-pdf mr-2 text-purple-500"></i>Document Viewer
                </h3>
                <button onclick="closeDocumentModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="documentViewerContainer">
                    <iframe id="documentViewer" class="document-viewer" src="" frameborder="0"></iframe>
                </div>
                <div id="documentNotFound" style="display: none;" class="text-center py-12">
                    <i class="fas fa-file-excel text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">Document not found or unavailable</p>
                    <p class="text-gray-400 text-sm mt-2">The scanned document may not have been uploaded yet.</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="downloadDocLink" target="_blank" class="px-4 py-2 bg-purple-500 text-white rounded-lg hover:bg-purple-600 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
                <button type="button" onclick="closeDocumentModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>

    <script>
    const CONTROLLER_URL = 'controller/ReportsController.php';

    // Helper function to preserve filters in URL
    function getFilterParams() {
        const urlParams = new URLSearchParams(window.location.search);
        let params = '';
        if (urlParams.get('status')) params += '&status=' + urlParams.get('status');
        if (urlParams.get('employee')) params += '&employee=' + urlParams.get('employee');
        return params;
    }

    function updatePerPage(value) {
        window.location.href = 'reports.php?per_page=' + value + '&page=1' + getFilterParams();
    }

    // =====================
    // View Report Modal
    // =====================
    function viewReport(reportId) {
        document.getElementById('viewModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        document.getElementById('viewModalContent').innerHTML = `
            <div class="text-center py-8">
                <i class="fas fa-spinner fa-spin text-3xl text-gray-400"></i>
                <p class="text-gray-500 mt-2">Loading report...</p>
            </div>
        `;

        fetch(`${CONTROLLER_URL}?action=get&report_id=${reportId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const report = data.report;
                const projects = report.projects || [];
                
                // Format dates
                const startDate = new Date(report.reporting_period_start);
                const endDate = new Date(report.reporting_period_end);
                const formatDate = (date) => date.toLocaleDateString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
                const year = endDate.getFullYear();
                
                // Build projects table HTML
                let projectsHtml = '';
                if (projects.length > 0) {
                    let tableHtml = `
                        <table style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #000; padding: 12px; font-weight: 600; color: #2d3ba6; font-size: 13px; text-transform: uppercase; background-color: #f9fafb; text-align: center; width: 20%;">PROJECT</th>
                                    <th style="border: 1px solid #000; padding: 12px; font-weight: 600; color: #2d3ba6; font-size: 13px; text-transform: uppercase; background-color: #f9fafb; text-align: center; width: 32%;">SUCCESS INDICATORS</th>
                                    <th style="border: 1px solid #000; padding: 12px; font-weight: 600; color: #2d3ba6; font-size: 13px; text-transform: uppercase; background-color: #f9fafb; text-align: center; width: 32%;">ACCOMPLISHMENTS</th>
                                    <th style="border: 1px solid #000; padding: 12px; font-weight: 600; color: #2d3ba6; font-size: 13px; text-transform: uppercase; background-color: #f9fafb; text-align: center; width: 16%;">ATTACHMENT</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    
                    projects.forEach((p, index) => {
                        // Split items by the delimiter (do not filter out empty items to maintain layout positions)
                        const indicators = (p.success_indicators || '').split('\n---\n');
                        const accomplishments = (p.actual_accomplishment || '').split('\n---\n');
                        const maxCount = Math.max(indicators.length, accomplishments.length, 1);
                        
                        // Build rows - one row per item, with PROJECT and ATTACHMENT spanning all rows
                        for (let i = 0; i < maxCount; i++) {
                            const indicatorText = indicators[i] ? escapeHtml(indicators[i].trim()) : '';
                            const accomplishmentText = accomplishments[i] ? escapeHtml(accomplishments[i].trim()) : '';
                            
                            tableHtml += '<tr>';
                            
                            // PROJECT column - only on first row with rowspan
                            if (i === 0) {
                                tableHtml += '<td style="border: 1px solid #000; padding: 12px; vertical-align: middle; text-align: center; font-size: 13px; color: #374151; font-weight: 500;" rowspan="' + maxCount + '">' + escapeHtml(p.project_name) + '</td>';
                            }
                            
                            // SUCCESS INDICATOR column
                            tableHtml += '<td style="border: 1px solid #000; padding: 10px 12px; vertical-align: top; font-size: 13px; color: #374151;">' + (i + 1) + '. ' + indicatorText + '</td>';
                            
                            // ACCOMPLISHMENT column
                            tableHtml += '<td style="border: 1px solid #000; padding: 10px 12px; vertical-align: top; font-size: 13px; color: #374151;">' + (i + 1) + '. ' + accomplishmentText + '</td>';
                            
                            // ATTACHMENT column - only on first row with rowspan
                            if (i === 0) {
                                const attachmentHtml = p.attachment 
                                    ? '<a href="../uploads/reports/' + escapeHtml(p.attachment) + '" target="_blank" style="color: #2d3ba6; text-decoration: none; display: inline-flex; flex-direction: column; align-items: center; gap: 4px; font-weight: 600; font-size: 13px;"><i class="fas fa-file-pdf" style="font-size: 24px;"></i>preview</a>'
                                    : '<span style="color: #999; font-style: italic; font-size: 13px;">No file</span>';
                                tableHtml += '<td style="border: 1px solid #000; padding: 12px; text-align: center; vertical-align: middle;" rowspan="' + maxCount + '">' + attachmentHtml + '</td>';
                            }
                            
                            tableHtml += '</tr>';
                        }
                    });
                    
                    tableHtml += '</tbody></table>';
                    
                    projectsHtml = `
                        <div style="margin-top: 2rem;">
                            <h3 style="font-size: 15px; font-weight: bold; color: #1f2937; margin-bottom: 1rem;">KEY RESULT AREAS, OBJECTIVES, TARGETS AND MEASURES</h3>
                            ${tableHtml}
                        </div>
                    `;
                } else {
                    projectsHtml = `
                        <div style="margin-top: 2rem;">
                            <h3 style="font-size: 0.95rem; font-weight: bold; color: #1f2937; margin-bottom: 1rem;">KEY RESULT AREAS, OBJECTIVES, TARGETS AND MEASURES</h3>
                            <div style="background-color: #fef3c7; border-left: 4px solid #fbbf24; padding: 1rem; border-radius: 0.375rem;">
                                <p style="color: #374151;"><i class="fas fa-info-circle" style="color: #d97706; margin-right: 0.5rem;"></i><strong>No projects have been added to this report yet.</strong></p>
                            </div>
                        </div>
                    `;
                }

                document.getElementById('viewModalContent').innerHTML = `
                    <!-- IPCR Header -->
                    <div style="text-align: center; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb;">
                        <h1 style="font-size: 1.1rem; font-weight: bold; color: #1f2937;">NATIONAL ELECTRICFICATION ADMINISTRATION</h1>
                        <p style="font-size: 0.875rem; color: #4b5563; margin-top: 0.25rem;">Strategic Performance Management System</p>
                        <h2 style="font-size: 1rem; font-weight: bold; color: #1f2937; margin-top: 1rem;">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
                    </div>
                    
                    <!-- Commitment Statement -->
                    <div style="text-align: justify; line-height: 1.8; color: #1f2937; margin-bottom: 2rem;">
                        <p>
                            <strong>I, <u>${escapeHtml(report.first_name + ' ' + report.last_name)}</u>, 
                            <u>${escapeHtml(report.position || 'Employee')}</u>, of the 
                            <u>${escapeHtml(report.department)}</u>,</strong> 
                            commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period 
                            <u>${formatDate(startDate)}</u> 
                            and <u>${formatDate(endDate)}</u>, 
                            <u>${year}</u>.
                        </p>
                    </div>
                    
                    <!-- Employee Signature Section -->
                    <div style="margin-left: auto; width: 45%; text-align: center; margin-bottom: 2rem;">
                        <p style="font-size: 0.95rem; font-weight: 600; color: #1f2937; margin-bottom: 0.5rem;">${escapeHtml(report.first_name + ' ' + report.last_name)}</p>
                        <div style="border-bottom: 1px solid #374151; margin-bottom: 0.5rem;"></div>
                        <p style="font-size: 0.75rem; color: #6b7280;">Employee</p>
                        <p style="font-size: 0.75rem; color: #6b7280; margin-top: 1.5rem;">Date: <span style="border-bottom: 1px solid #374151; display: inline-block; width: 60%; margin-left: 0.5rem;"></span></p>
                    </div>
                    
                    <!-- Approved By Section -->
                    <div style="margin-bottom: 2rem;">
                        <table style="width: 100%; border: 1px solid #000; border-collapse: collapse;">
                            <tr style="height: 100px;">
                                <td style="border: 1px solid #000; padding: 1.5rem; width: 75%; vertical-align: top; position: relative;">
                                    <p style="font-size: 0.75rem; font-weight: 600; color: #4b5563; margin-bottom: 3rem;">Approved by:</p>
                                    <div style="position: absolute; bottom: 1rem; left: 50%; transform: translateX(-50%); width: 60%; text-align: center;">
                                        <div style="border-bottom: 1px solid #374151; margin-bottom: 0.25rem;"></div>
                                        <p style="font-size: 0.75rem; color: #6b7280;">Department Manager</p>
                                    </div>
                                </td>
                                <td style="border: 1px solid #000; padding: 1.5rem; width: 25%; vertical-align: top;">
                                    <p style="font-size: 0.75rem; color: #6b7280;">Date:</p>
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    ${projectsHtml}
                    
                    <div style="margin-top: 1.5rem; display: flex; gap: 0.5rem; justify-content: flex-end;">
                        <button onclick="closeViewModal(); openStatusModal(${report.id}, '${report.status}', '${escapeHtml(report.review_comments || '')}')" class="btn-primary" style="background-color: #10b981;">
                            <i class="fas fa-check-circle mr-2"></i>Update Status
                        </button>
                    </div>
                `;
            } else {
                document.getElementById('viewModalContent').innerHTML = `
                    <div class="text-center py-8">
                        <i class="fas fa-exclamation-circle text-4xl text-red-400"></i>
                        <p class="text-gray-600 mt-2">${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            document.getElementById('viewModalContent').innerHTML = `
                <div class="text-center py-8">
                    <i class="fas fa-exclamation-circle text-4xl text-red-400"></i>
                    <p class="text-gray-600 mt-2">Failed to load report data.</p>
                </div>
            `;
        });
    }

    function closeViewModal(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('viewModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    // =====================
    // Status Modal Functions
    // =====================
    function openStatusModal(reportId, currentStatus, comments) {
        document.getElementById('status_report_id').value = reportId;
        document.getElementById('status_value').value = currentStatus;
        document.getElementById('status_comments').value = comments || '';
        document.getElementById('statusModal').classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeStatusModal(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('statusModal').classList.remove('active');
        document.body.style.overflow = '';
    }

    function submitStatusForm(event) {
        event.preventDefault();
        const form = document.getElementById('statusForm');
        const formData = new FormData(form);
        formData.append('action', 'update_status');

        Swal.fire({
            title: 'Updating Status...',
            allowOutsideClick: false,
            didOpen: () => Swal.showLoading()
        });

        fetch(CONTROLLER_URL, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3c50e0'
                }).then(() => {
                    window.location.reload();
                });
            } else {
                Swal.fire({
                    title: 'Error!',
                    text: data.message,
                    icon: 'error',
                    confirmButtonColor: '#3c50e0'
                });
            }
        })
        .catch(error => {
            Swal.fire({
                title: 'Error!',
                text: 'An error occurred. Please try again.',
                icon: 'error',
                confirmButtonColor: '#3c50e0'
            });
        });
    }

    // =====================
    // Document Viewer Modal
    // =====================
    function viewDocument(reportId, filePath) {
        document.getElementById('documentModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Construct the document URL
        const docUrl = '../uploads/' + filePath;
        
        // Check if it's a PDF or image
        const extension = filePath.split('.').pop().toLowerCase();
        const viewer = document.getElementById('documentViewer');
        const container = document.getElementById('documentViewerContainer');
        const notFound = document.getElementById('documentNotFound');
        const downloadLink = document.getElementById('downloadDocLink');
        
        downloadLink.href = docUrl;
        
        if (['pdf'].includes(extension)) {
            container.style.display = 'block';
            notFound.style.display = 'none';
            viewer.src = docUrl;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(extension)) {
            container.innerHTML = `<img src="${docUrl}" alt="Document" class="max-w-full h-auto rounded-lg shadow-lg" onerror="showDocNotFound()">`;
            container.style.display = 'block';
            notFound.style.display = 'none';
        } else {
            container.style.display = 'none';
            notFound.style.display = 'block';
        }
    }

    function showDocNotFound() {
        document.getElementById('documentViewerContainer').style.display = 'none';
        document.getElementById('documentNotFound').style.display = 'block';
    }

    function closeDocumentModal(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('documentModal').classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('documentViewer').src = '';
    }

    // =====================
    // Delete Report Function
    // =====================
    function deleteReport(reportId, employeeName) {
        Swal.fire({
            title: 'Delete Report?',
            html: `Are you sure you want to delete the report for <strong>${employeeName}</strong>?<br><br><span class="text-red-600 text-sm">This action cannot be undone! All projects and ratings will also be deleted.</span>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: '<i class="fas fa-trash mr-2"></i>Yes, delete!',
            cancelButtonText: '<i class="fas fa-times mr-2"></i>Cancel',
            reverseButtons: true,
            focusCancel: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: 'Deleting...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('report_id', reportId);

                fetch(CONTROLLER_URL, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: data.message,
                            icon: 'success',
                            showConfirmButton: true,
                            confirmButtonText: 'OK',
                            confirmButtonColor: '#3c50e0'
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: data.message,
                            icon: 'error',
                            confirmButtonColor: '#3c50e0'
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        title: 'Error!',
                        text: 'An error occurred. Please try again.',
                        icon: 'error',
                        confirmButtonColor: '#3c50e0'
                    });
                });
            }
        });
    }

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Debounce Search Function
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 500); // Wait 500ms after user stops typing
    }

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeViewModal();
            closeStatusModal();
            closeDocumentModal();
        }
    });
    </script>
</body>
</html>
