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

$reportId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$reportId) {
    header('Location: reports.php');
    exit();
}

$report = getReportById($reportId, $conn);
if (!$report) {
    header('Location: reports.php');
    exit();
}

// Fetch projects for this report
$projects = getProjectsByReportId($reportId, $conn);
$performance = getPerformanceRating($reportId, $conn);

// Handle status update from admin view
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $newStatus = $conn->real_escape_string($_POST['status']);
    $reviewComments = $conn->real_escape_string($_POST['review_comments']);

    $query = "UPDATE accomplishment_reports SET 
              status = '$newStatus', 
              reviewed_by = $userId,
              review_date = NOW(),
              review_comments = '$reviewComments'
              WHERE id = $reportId";

    if ($conn->query($query)) {
        $successMessage = 'Report status updated successfully!';
        // Refresh report data
        $report = getReportById($reportId, $conn);
    }
    else {
        $errorMessage = 'Error updating report: ' . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="pragma" content="no-cache">
    <meta http-equiv="expires" content="0">
    <title>View Report - NEA Abolran</title>
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
            margin-bottom: 1.5rem;
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
            font-weight: 500;
        }
        .btn-primary:hover {
            background-color: #2d3ba6;
        }
        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
            padding: 0.625rem 1.25rem;
            border-radius: 0.375rem;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-weight: 500;
        }
        .btn-secondary:hover {
            background-color: #d1d5db;
        }
        .sidebar-active {
            border-left-color: var(--color-primary);
            background-color: #f3f4f6;
            color: var(--color-primary);
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        @media print {
            body {
                background-color: white;
            }
            .no-print {
                display: none !important;
            }
            .card {
                page-break-inside: avoid;
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
            .project-section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'reports'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <?php
$pageTitle = 'Report Details';
$headerRight = '
                <div class="flex gap-2">
                    <a href="report_print.php?id=' . $reportId . '" target="_blank" class="btn-secondary">
                        <i class="fas fa-print mr-2"></i>Print Page
                    </a>
                    <button onclick="window.print()" class="btn-secondary">
                        <i class="fas fa-print mr-2"></i>Print
                    </button>
                    <a href="reports.php" class="btn-secondary">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Reports
                    </a>
                </div>
            ';
include 'includes/header.php';
?>

            <!-- Main Content Area -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <!-- Messages -->
                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php
endif; ?>
                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php
endif; ?>

                <!-- IPCR Header Information -->
                <div class="card">
                    <!-- IPCR Title -->
                    <div class="text-center mb-8 pb-6">
                        <h1 class="text-lg font-bold text-gray-800">NATIONAL ELECTRICFICATION ADMINISTRATION</h1>
                        <p class="text-sm text-gray-700 mt-1">Strategic Performance Management System</p>
                        <h2 class="text-base font-bold text-gray-800 mt-4">INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
                    </div>
                    
                    <!-- Commitment Statement -->
                    <div class="text-justify leading-relaxed text-gray-800 mb-0 pb-6">
                        <p class="mb-8">
                            <strong>I, <u><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></u>, 
                            <u><?php echo htmlspecialchars($report['position']); ?></u>, of the 
                            <u><?php echo htmlspecialchars($report['department']); ?></u>,</strong> 
                            commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period 
                            <u><?php echo htmlspecialchars(date('F d, Y', strtotime($report['reporting_period_start']))); ?></u> 
                            and <u><?php echo htmlspecialchars(date('F d, Y', strtotime($report['reporting_period_end']))); ?></u>, 
                            <u><?php echo htmlspecialchars(date('Y', strtotime($report['reporting_period_end']))); ?></u>.
                        </p>
                        
                        <!-- Signature Section -->
                        <div class="mt-8" style="margin-left: auto; width: 40%; text-align: center;">
                            <p class="text-sm font-semibold text-gray-800 mb-4"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></p>
                            <div style="border-bottom: 1px solid #374151; height: 1px; margin-bottom: 0.5rem;"></div>
                            <p class="text-xs text-gray-600 mt-2">Employee</p>
                            
                            <p class="text-xs text-gray-600 mt-8">Date: <span style="border-bottom: 1px solid #374151; display: inline-block; width: 60%; margin-left: 0.5rem;"></span></p>
                        </div>
                    </div>
                
                    <!-- Approved By Section -->
                    <div style="padding: 0;">
                        <table class="w-full" style="border: 1px solid #000; border-collapse: collapse;">
                            <tr style="height: 120px;">
                                <td style="border: 1px solid #000; padding: 1.5rem; width: 75%; vertical-align: top; position: relative;">
                                    <p class="text-xs font-semibold text-gray-700 mb-12">Approved by:</p>
                                    <div style="position: absolute; bottom: 1.5rem; left: 50%; transform: translateX(-50%); width: 60%; text-align: center;">
                                        <div style="border-bottom: 1px solid #374151; height: 1px; margin-bottom: 0.5rem;"></div>
                                        <p class="text-xs text-gray-600">Department Manager</p>
                                    </div>
                                </td>
                                <td style="border: 1px solid #000; padding: 1.5rem; width: 25%; vertical-align: top;">
                                    <p class="text-xs text-gray-600">Date: </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Projects and Activities Table - IPCR Format -->
                <div class="card">
                    <h3 class="text-base font-bold text-gray-800 mb-4">KEY RESULT AREAS, OBJECTIVES, TARGETS AND MEASURES</h3>
                    
                    <?php if (empty($projects)): ?>
                        <div style="background-color: #fef3c7; border-left: 4px solid #fbbf24; padding: 1rem; border-radius: 0.375rem; margin-bottom: 1rem;">
                            <p class="text-gray-700">
                                <i class="fas fa-info-circle text-amber-600 mr-2"></i>
                                <strong>No projects have been added to this report yet.</strong>
                            </p>
                        </div>
                    <?php
else: ?>
                        <table class="w-full" style="border-collapse: collapse;">
                            <?php $projectIndex = 1;
    foreach ($projects as $project): ?>
                                <?php
        $indicators = explode("\n---\n", $project['success_indicators']);
        $accomplishments = explode("\n---\n", $project['actual_accomplishment']);
        $maxCount = max(count($indicators), count($accomplishments));
?>
                                <?php for ($i = 0; $i < $maxCount; $i++): ?>
                                <tr>
                                    <!-- Project Name Column (only first row) -->
                                    <?php if ($i === 0): ?>
                                    <td style="border: 1px solid #000; padding: 0; width: 15%; vertical-align: top; <?php echo($maxCount > 1) ? 'border-bottom: none;' : ''; ?>">
                                        <div style="font-weight: 600; color: #2d3ba6; font-size: 0.875rem; text-transform: uppercase; padding: 1rem 1rem 0.75rem 1rem; border-bottom: 1px solid #d1d5db;">PROJECT/ACTIVITY</div>
                                        <div style="font-size: 0.9rem; color: #374151; padding: 1rem;">
                                            <?php echo $projectIndex . '. ' . htmlspecialchars($project['project_name']); ?>
                                        </div>
                                    </td>
                                    <?php
            else: ?>
                                    <td style="border: 1px solid #000; border-top: none; padding: 0; width: 15%; vertical-align: top; <?php echo($i < $maxCount - 1) ? 'border-bottom: none;' : ''; ?>" rowspan="1"></td>
                                    <?php
            endif; ?>
                                    
                                    <!-- Success Indicators Column -->
                                    <td style="border: 1px solid #000; padding: 0; width: 28%; vertical-align: top; border-top: <?php echo($i > 0) ? 'none' : ''; ?>; <?php echo($i < $maxCount - 1) ? 'border-bottom: none;' : ''; ?>">
                                        <?php if ($i === 0): ?>
                                        <div style="font-weight: 600; color: #2d3ba6; font-size: 0.875rem; text-transform: uppercase; padding: 1rem 1rem 0.75rem 1rem; border-bottom: 1px solid #d1d5db;">SUCCESS INDICATORS</div>
                                        <?php
            endif; ?>
                                        <div style="font-size: 0.9rem; color: #374151; padding: 1rem;">
                                            <?php
            if (isset($indicators[$i])) {
                echo htmlspecialchars(trim($indicators[$i]));
            }
?>
                                        </div>
                                    </td>
                                    
                                    <!-- Accomplishment Column -->
                                    <td style="border: 1px solid #000; padding: 0; width: 28%; vertical-align: top; border-top: <?php echo($i > 0) ? 'none' : ''; ?>; <?php echo($i < $maxCount - 1) ? 'border-bottom: none;' : ''; ?>">
                                        <?php if ($i === 0): ?>
                                        <div style="font-weight: 600; color: #2d3ba6; font-size: 0.875rem; text-transform: uppercase; padding: 1rem 1rem 0.75rem 1rem; border-bottom: 1px solid #d1d5db;">ACCOMPLISHMENT</div>
                                        <?php
            endif; ?>
                                        <div style="font-size: 0.9rem; color: #374151; padding: 1rem;">
                                            <?php
            if (isset($accomplishments[$i])) {
                echo htmlspecialchars(trim($accomplishments[$i]));
            }
?>
                                        </div>
                                    </td>
                                    
                                    <!-- Scanned File Column (only first row) -->
                                    <?php if ($i === 0): ?>
                                    <td style="border: 1px solid #000; padding: 0; width: 20%; text-align: center; vertical-align: top; <?php echo($maxCount > 1) ? 'border-bottom: none;' : ''; ?>">
                                        <div style="font-weight: 600; color: #2d3ba6; font-size: 0.875rem; text-transform: uppercase; padding: 1rem 1rem 0.75rem 1rem; border-bottom: 1px solid #d1d5db;">SCANNED FILE</div>
                                        <div style="padding: 1rem;">
                                            <?php if ($report['scanned_file']): ?>
                                                <a href="../uploads/<?php echo htmlspecialchars($report['scanned_file']); ?>" target="_blank" style="color: #2d3ba6; text-decoration: none; display: inline-flex; flex-direction: column; align-items: center; gap: 0.5rem; font-weight: 600;">
                                                    <i class="fas fa-file-pdf" style="font-size: 1.5rem;"></i>
                                                    Preview
                                                </a>
                                            <?php
                else: ?>
                                                <span style="color: #999; font-style: italic; font-size: 0.875rem;">No file</span>
                                            <?php
                endif; ?>
                                        </div>
                                    </td>
                                    <?php
            else: ?>
                                    <td style="border: 1px solid #000; border-top: none; padding: 0; width: 20%; vertical-align: top; <?php echo($i < $maxCount - 1) ? 'border-bottom: none;' : ''; ?>" rowspan="1"></td>
                                    <?php
            endif; ?>
                                </tr>
                                <?php
        endfor; ?>
                            <?php $projectIndex++;
    endforeach; ?>
                        </table>
                    <?php
endif; ?>
                </div>

                <!-- Performance Summary -->
                <?php if ($performance): ?>
                    <div class="card">
                        <h3 class="text-base font-bold text-gray-800 mb-4">Performance Summary</h3>
                        
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-blue-50 p-4 rounded border-l-4 border-blue-500">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Overall Quality</p>
                                <p class="text-2xl font-bold text-blue-600"><?php echo htmlspecialchars($performance['overall_quality']); ?></p>
                            </div>
                            <div class="bg-green-50 p-4 rounded border-l-4 border-green-500">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Overall Efficiency</p>
                                <p class="text-2xl font-bold text-green-600"><?php echo htmlspecialchars($performance['overall_efficiency']); ?></p>
                            </div>
                            <div class="bg-purple-50 p-4 rounded border-l-4 border-purple-500">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Overall Timeliness</p>
                                <p class="text-2xl font-bold text-purple-600"><?php echo htmlspecialchars($performance['overall_timeliness']); ?></p>
                            </div>
                            <div class="bg-orange-50 p-4 rounded border-l-4 border-orange-500">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Average Rating</p>
                                <p class="text-2xl font-bold text-orange-600"><?php echo number_format($performance['average_rating'], 2); ?></p>
                            </div>
                        </div>
                    </div>
                <?php
endif; ?>

                <!-- Admin Review Section -->
                <div class="card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">
                        <i class="fas fa-clipboard-check mr-2" style="color: var(--color-primary);"></i>Admin Review & Status
                    </h3>
                    <form method="POST" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Update Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    <option value="draft" <?php echo $report['status'] == 'draft' ? 'selected' : ''; ?>>Draft</option>
                                    <option value="submitted" <?php echo $report['status'] == 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                    <option value="reviewed" <?php echo $report['status'] == 'reviewed' ? 'selected' : ''; ?>>Reviewed</option>
                                    <option value="approved" <?php echo $report['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Current Status</label>
                                <div class="pt-2">
                                    <span class="badge badge-<?php echo htmlspecialchars($report['status']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($report['status'])); ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Review Comments</label>
                            <textarea name="review_comments" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" placeholder="Add review comments, feedback, or recommendations..."><?php echo htmlspecialchars($report['review_comments'] ?? ''); ?></textarea>
                        </div>

                        <?php if ($report['review_date']): ?>
                            <div class="bg-blue-50 p-4 rounded border-l-4 border-blue-500">
                                <p class="text-xs font-semibold text-gray-600 uppercase mb-1">Last Reviewed</p>
                                <p class="text-sm text-gray-800"><?php echo htmlspecialchars(date('M d, Y H:i', strtotime($report['review_date']))); ?></p>
                            </div>
                        <?php
endif; ?>

                        <div class="flex gap-2 pt-4 border-t border-gray-200">
                            <button type="submit" name="update_status" value="1" class="btn-primary">
                                <i class="fas fa-save mr-2"></i>Save Changes
                            </button>
                            <a href="reports.php" class="btn-secondary">
                                <i class="fas fa-times mr-2"></i>Cancel
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Review Comments (if reviewed) -->
                <?php if ($report['status'] !== 'draft' && $report['review_comments']): ?>
                    <div class="card">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Previous Review Comments</h3>
                        <div class="bg-yellow-50 p-4 rounded border-l-4 border-yellow-400">
                            <p class="text-gray-700"><?php echo htmlspecialchars($report['review_comments']); ?></p>
                            <?php if ($report['review_date']): ?>
                                <p class="text-sm text-gray-600 mt-2">Reviewed on: <?php echo htmlspecialchars(date('M d, Y H:i', strtotime($report['review_date']))); ?></p>
                            <?php
    endif; ?>
                        </div>
                    </div>
                <?php
endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
