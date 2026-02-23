<?php
require_once '../backend/config.php';
require_once '../backend/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user_id'];
$userInfo = getUserById($userId, $conn);

// Check if editing existing report
$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$report = null;
$projects = [];

if ($reportId > 0) {
    $report = getReportById($reportId, $conn);

    // Check if report exists and user owns it
    if (!$report || ($report['employee_id'] != $userId && $userInfo['role'] !== 'admin')) {
        header("Location: reports.php");
        exit;
    }

    $projects = getProjectsByReportId($reportId, $conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $report ? 'Edit Report' : 'Create New Report'; ?></title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
        .btn-primary {
            background-color: var(--color-primary);
            color: white;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        .btn-primary:hover {
            background-color: #2d3ba6;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(60, 80, 224, 0.1);
        }
        .badge {
            display: inline-block;
            padding: 0.375rem 0.875rem;
            border-radius: 0.25rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .badge-draft { background-color: #f3f4f6; color: #374151; }
        .badge-submitted { background-color: #dbeafe; color: #1e40af; }
        .badge-reviewed { background-color: #fef3c7; color: #b45309; }
        .badge-approved { background-color: #dcfce7; color: #166534; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen">
        <?php $activePage = 'new-report'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = $report ? 'Edit Report' : 'Create New Report';
$pageIcon = $report ? 'fas fa-edit' : 'fas fa-plus-circle';
$headerRight = '<a href="reports.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors"><i class="fas fa-arrow-left mr-2"></i>Back to Reports</a>';
include 'includes/header.php';
?>
            
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <?php if ($report): ?>
                    <!-- Edit Mode -->
                    <div class="card mb-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-file-alt mr-2 text-blue-500"></i>Report Details
                            </h3>
                            <span class="badge badge-<?php echo htmlspecialchars($report['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($report['status'])); ?>
                            </span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Period:</span>
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($report['reporting_period_start'])) . ' - ' . date('M d, Y', strtotime($report['reporting_period_end']))); ?>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500">Employee:</span>
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?>
                                </p>
                            </div>
                            <div>
                                <span class="text-gray-500">Created:</span>
                                <p class="font-semibold text-gray-800">
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($report['created_at']))); ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Accomplishments Section -->
                    <div class="card">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-800">
                                <i class="fas fa-tasks mr-2 text-green-500"></i>Accomplishments
                            </h3>
                            <?php if ($report['status'] === 'draft'): ?>
                            <button onclick="showAddForm()" class="btn-primary">
                                <i class="fas fa-plus mr-2"></i>Add Accomplishment
                            </button>
                            <?php
    endif; ?>
                        </div>

                        <?php if (empty($projects)): ?>
                            <p class="text-gray-500 text-center py-8">No accomplishments added yet.</p>
                        <?php
    else: ?>
                            <div class="space-y-4" id="projectsList">
                                <?php foreach ($projects as $project): ?>
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4" data-project-id="<?php echo $project['id']; ?>">
                                    <div class="flex justify-between items-start mb-3">
                                        <h5 class="font-semibold text-gray-800">
                                            <i class="fas fa-project-diagram mr-2 text-blue-500"></i><?php echo htmlspecialchars($project['project_name']); ?>
                                        </h5>
                                        <?php if ($report['status'] === 'draft'): ?>
                                        <button onclick="deleteProject(<?php echo $project['id']; ?>, '<?php echo htmlspecialchars(addslashes($project['project_name'])); ?>')" class="text-red-500 hover:text-red-700">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php
            endif; ?>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 mb-1">
                                                <i class="fas fa-bullseye mr-1 text-orange-400"></i>Success Indicators
                                            </p>
                                            <div class="text-sm text-gray-700 whitespace-pre-line bg-white p-2 rounded border"><?php echo htmlspecialchars($project['success_indicators'] ?? 'N/A'); ?></div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-gray-500 mb-1">
                                                <i class="fas fa-clipboard-check mr-1 text-green-400"></i>Actual Accomplishments
                                            </p>
                                            <div class="text-sm text-gray-700 whitespace-pre-line bg-white p-2 rounded border"><?php echo htmlspecialchars($project['actual_accomplishment'] ?? 'N/A'); ?></div>
                                        </div>
                                    </div>
                                    <?php if (!empty($project['attachment'])): ?>
                                    <div class="mt-2">
                                        <a href="../<?php echo htmlspecialchars($project['attachment']); ?>" target="_blank" class="text-blue-500 hover:text-blue-700 text-sm">
                                            <i class="fas fa-paperclip mr-1"></i>View Attachment
                                        </a>
                                    </div>
                                    <?php
            endif; ?>
                                </div>
                                <?php
        endforeach; ?>
                            </div>
                        <?php
    endif; ?>

                        <!-- Add Form (hidden by default) -->
                        <?php if ($report['status'] === 'draft'): ?>
                        <div id="addForm" class="hidden border-t border-gray-200 pt-4 mt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">
                                <i class="fas fa-plus-circle mr-2 text-blue-500"></i>Add New Accomplishment
                            </h4>
                            <form id="addProjectForm" enctype="multipart/form-data">
                                <input type="hidden" name="report_id" value="<?php echo $reportId; ?>">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Project/Task Name *</label>
                                    <input type="text" name="project_name" class="form-control" placeholder="e.g., Monthly Financial Report" required>
                                </div>
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <label class="text-sm font-medium text-gray-700">Success Indicators & Accomplishments *</label>
                                        <button type="button" onclick="addRow()" class="text-sm bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                            <i class="fas fa-plus mr-1"></i>Add Row
                                        </button>
                                    </div>
                                    <div id="rowsContainer" class="space-y-3"></div>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachment (Optional)</label>
                                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" onclick="hideAddForm()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                                        <i class="fas fa-times mr-2"></i>Cancel
                                    </button>
                                    <button type="submit" class="btn-primary">
                                        <i class="fas fa-save mr-2"></i>Save
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Submit Button -->
                        <div class="border-t border-gray-200 pt-4 mt-4 flex justify-end">
                            <button onclick="submitReport()" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600">
                                <i class="fas fa-paper-plane mr-2"></i>Submit Report
                            </button>
                        </div>
                        <?php
    endif; ?>
                    </div>
                <?php
else: ?>
                    <!-- Create Mode -->
                    <div class="card max-w-2xl mx-auto">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">
                            <i class="fas fa-plus-circle mr-2 text-blue-500"></i>Create New Report
                        </h3>
                        <p class="text-sm text-gray-600 mb-6 bg-blue-50 p-3 rounded-lg">
                            <i class="fas fa-info-circle mr-2 text-blue-500"></i>
                            Create a new accomplishment report for a specific reporting period.
                        </p>
                        <form id="createReportForm">
                            <div class="mb-4">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-1 text-gray-400"></i>
                                    Reporting Period Start *
                                </label>
                                <input type="date" name="period_start" id="period_start" class="form-control" required>
                            </div>
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-check mr-1 text-gray-400"></i>
                                    Reporting Period End *
                                </label>
                                <input type="date" name="period_end" id="period_end" class="form-control" required>
                            </div>
                            <button type="submit" class="btn-primary w-full">
                                <i class="fas fa-arrow-right mr-2"></i>Create & Add Accomplishments
                            </button>
                        </form>
                    </div>
                <?php
endif; ?>
            </main>
        </div>
    </div>

    <script>
    const CONTROLLER_URL = 'controller/ReportsController.php';
    let rowCounter = 0;
    const reportId = <?php echo $reportId ?: 'null'; ?>;

    // Set default dates for create form
    <?php if (!$report): ?>
    const today = new Date();
    const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
    const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    document.getElementById('period_start').value = firstDay.toISOString().split('T')[0];
    document.getElementById('period_end').value = lastDay.toISOString().split('T')[0];
    <?php
endif; ?>

    function showAddForm() {
        document.getElementById('addForm').classList.remove('hidden');
        document.getElementById('rowsContainer').innerHTML = '';
        rowCounter = 0;
        addRow();
    }

    function hideAddForm() {
        document.getElementById('addForm').classList.add('hidden');
        document.getElementById('addProjectForm').reset();
    }

    function addRow() {
        rowCounter++;
        const container = document.getElementById('rowsContainer');
        const rowHtml = `
            <div class="bg-white border border-gray-200 rounded-lg p-3" data-row="${rowCounter}">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-semibold text-blue-600 bg-blue-100 px-2 py-1 rounded">Item ${rowCounter}</span>
                    ${rowCounter > 1 ? `<button type="button" onclick="this.closest('[data-row]').remove()" class="text-red-500 hover:text-red-700 text-sm"><i class="fas fa-trash"></i></button>` : ''}
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Success Indicator</label>
                        <textarea name="success_indicators[]" class="form-control text-sm" rows="2" placeholder="Target..." required></textarea>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 mb-1 block">Actual Accomplishment</label>
                        <textarea name="actual_accomplishments[]" class="form-control text-sm" rows="2" placeholder="Accomplished..." required></textarea>
                    </div>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', rowHtml);
    }

    // Create report form
    <?php if (!$report): ?>
    document.getElementById('createReportForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const periodStart = document.getElementById('period_start').value;
        const periodEnd = document.getElementById('period_end').value;
        
        if (new Date(periodStart) > new Date(periodEnd)) {
            Swal.fire({ title: 'Invalid Dates', text: 'Start date must be before end date', icon: 'error', confirmButtonColor: '#3c50e0' });
            return;
        }
        
        Swal.fire({ title: 'Creating Report...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        const formData = new FormData();
        formData.append('action', 'create_report');
        formData.append('period_start', periodStart);
        formData.append('period_end', periodEnd);
        
        fetch(CONTROLLER_URL, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Created!', text: 'Report created successfully', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => {
                    window.location.href = 'report-create.php?id=' + data.report_id;
                });
            } else {
                Swal.fire({ title: 'Error!', text: data.message, icon: 'error', confirmButtonColor: '#3c50e0' });
            }
        })
        .catch(error => {
            Swal.fire({ title: 'Error!', text: 'An error occurred. Please try again.', icon: 'error', confirmButtonColor: '#3c50e0' });
        });
    });
    <?php
endif; ?>

    // Add project form
    <?php if ($report && $report['status'] === 'draft'): ?>
    document.getElementById('addProjectForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const projectName = this.querySelector('[name="project_name"]').value.trim();
        const successIndicators = Array.from(this.querySelectorAll('[name="success_indicators[]"]')).map(t => t.value.trim());
        const actualAccomplishments = Array.from(this.querySelectorAll('[name="actual_accomplishments[]"]')).map(t => t.value.trim());
        
        if (!projectName || successIndicators.some(s => !s) || actualAccomplishments.some(a => !a)) {
            Swal.fire({ title: 'Missing Fields', text: 'Please fill in all required fields', icon: 'warning', confirmButtonColor: '#3c50e0' });
            return;
        }
        
        Swal.fire({ title: 'Saving...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        const formData = new FormData(this);
        formData.append('action', 'add_project');
        
        fetch(CONTROLLER_URL, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Added!', text: 'Accomplishment added successfully', icon: 'success', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
            } else {
                Swal.fire({ title: 'Error!', text: data.message, icon: 'error', confirmButtonColor: '#3c50e0' });
            }
        })
        .catch(error => {
            Swal.fire({ title: 'Error!', text: 'An error occurred. Please try again.', icon: 'error', confirmButtonColor: '#3c50e0' });
        });
    });

    function deleteProject(projectId, projectName) {
        Swal.fire({
            title: 'Delete?',
            html: `Delete <strong>"${projectName}"</strong>?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                const formData = new FormData();
                formData.append('action', 'delete_project');
                formData.append('project_id', projectId);
                
                fetch(CONTROLLER_URL, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: 'Deleted!', icon: 'success', timer: 1500, showConfirmButton: false });
                        document.querySelector(`[data-project-id="${projectId}"]`).remove();
                    } else {
                        Swal.fire({ title: 'Error!', text: data.message, icon: 'error', confirmButtonColor: '#3c50e0' });
                    }
                })
                .catch(error => {
                    Swal.fire({ title: 'Error!', text: 'An error occurred.', icon: 'error', confirmButtonColor: '#3c50e0' });
                });
            }
        });
    }

    function submitReport() {
        Swal.fire({
            title: 'Submit Report?',
            text: 'Once submitted, you cannot edit this report anymore.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3c50e0',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, submit!',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({ title: 'Submitting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
                
                const formData = new FormData();
                formData.append('action', 'update_status');
                formData.append('report_id', reportId);
                formData.append('status', 'submitted');
                
                fetch(CONTROLLER_URL, { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: 'Submitted!', text: 'Your report has been submitted.', icon: 'success', confirmButtonColor: '#3c50e0' })
                        .then(() => window.location.href = 'reports.php');
                    } else {
                        Swal.fire({ title: 'Error!', text: data.message, icon: 'error', confirmButtonColor: '#3c50e0' });
                    }
                })
                .catch(error => {
                    Swal.fire({ title: 'Error!', text: 'An error occurred.', icon: 'error', confirmButtonColor: '#3c50e0' });
                });
            }
        });
    }
    <?php
endif; ?>
    </script>
</body>
</html>
