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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Report - NEA Aboilan</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <style>
        @page {
            size: A4;
            margin: 0.5in;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }

        .print-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }

        .print-header h1 {
            font-size: 16px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .print-header p {
            font-size: 12px;
            margin: 5px 0;
        }

        .print-header h2 {
            font-size: 14px;
            font-weight: bold;
            margin: 10px 0 0 0;
            text-transform: uppercase;
        }

        .commitment-text {
            text-align: justify;
            margin: 20px 0;
            line-height: 1.6;
        }

        .commitment-text strong {
            font-weight: bold;
        }

        .commitment-text u {
            text-decoration: underline;
        }

        .signature-section {
            margin-left: auto;
            width: 40%;
            text-align: center;
            margin-top: 30px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            margin-bottom: 5px;
            height: 20px;
        }

        .signature-label {
            font-size: 10px;
            color: #666;
        }

        .approval-table {
            width: 100%;
            border: 1px solid #000;
            border-collapse: collapse;
            margin: 20px 0;
        }

        .approval-table td {
            border: 1px solid #000;
            padding: 15px;
            vertical-align: top;
        }

        .approval-table .approval-cell {
            width: 75%;
            position: relative;
        }

        .approval-signature {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            width: 60%;
            text-align: center;
        }

        .projects-section {
            margin-top: 30px;
        }

        .projects-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
            text-transform: uppercase;
            color: #2d3ba6;
        }

        .projects-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .projects-table th {
            border: 1px solid #000;
            padding: 8px;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
            background-color: #f5f5f5;
            text-align: center;
            color: #2d3ba6;
        }

        .projects-table td {
            border: 1px solid #000;
            padding: 8px;
            font-size: 11px;
            vertical-align: top;
        }

        .project-name {
            font-weight: bold;
            color: #2d3ba6;
        }

        .no-projects {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
            font-style: italic;
            color: #856404;
        }

        .page-break {
            page-break-before: always;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body onload="window.print()">
    <!-- IPCR Header -->
    <div class="print-header">
        <h1>NATIONAL ELECTRICFICATION ADMINISTRATION</h1>
        <p>Strategic Performance Management System</p>
        <h2>INDIVIDUAL PERFORMANCE COMMITMENT AND REVIEW (IPCR)</h2>
    </div>

    <!-- Commitment Statement -->
    <div class="commitment-text">
        <p>
            <strong>I, <u><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></u>,
            <u><?php echo htmlspecialchars($report['position']); ?></u>, of the
            <u><?php echo htmlspecialchars($report['department']); ?></u>,</strong>
            commit to deliver and agree to be rated on the attainment of the following targets in accordance with the indicated measures for the period
            <u><?php echo htmlspecialchars(date('F d, Y', strtotime($report['reporting_period_start']))); ?></u>
            and <u><?php echo htmlspecialchars(date('F d, Y', strtotime($report['reporting_period_end']))); ?></u>,
            <u><?php echo htmlspecialchars(date('Y', strtotime($report['reporting_period_end']))); ?></u>.
        </p>
    </div>

    <!-- Employee Signature Section -->
    <div class="signature-section">
        <p style="font-weight: bold; margin-bottom: 10px;"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></p>
        <div class="signature-line"></div>
        <p class="signature-label">Employee</p>

        <p class="signature-label" style="margin-top: 30px;">
            Date: <span style="border-bottom: 1px solid #000; display: inline-block; width: 60%; margin-left: 10px;"></span>
        </p>
    </div>

    <!-- Approved By Section -->
    <table class="approval-table">
        <tr style="height: 100px;">
            <td class="approval-cell">
                <p style="font-size: 11px; font-weight: bold; margin-bottom: 40px;">Approved by:</p>
                <div class="approval-signature">
                    <div class="signature-line"></div>
                    <p class="signature-label">Department Manager</p>
                </div>
            </td>
            <td style="width: 25%;">
                <p class="signature-label">Date:</p>
            </td>
        </tr>
    </table>

    <!-- Projects and Activities Table -->
    <div class="projects-section">
        <h3 class="projects-title">KEY RESULT AREAS, OBJECTIVES, TARGETS AND MEASURES</h3>

        <?php if (empty($projects)): ?>
            <div class="no-projects">
                <p><strong>No projects have been added to this report yet.</strong></p>
            </div>
        <?php else: ?>
            <table class="projects-table">
                <thead>
                    <tr>
                        <th style="width: 20%;">PROJECT/ACTIVITY</th>
                        <th style="width: 32%;">SUCCESS INDICATORS</th>
                        <th style="width: 32%;">ACCOMPLISHMENTS</th>
                        <th style="width: 16%;">ATTACHMENT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $projectIndex = 1; ?>
                    <?php foreach ($projects as $project): ?>
                        <?php
                            $indicators = array_filter(explode("\n---\n", $project['success_indicators']));
                            $accomplishments = array_filter(explode("\n---\n", $project['actual_accomplishment']));
                            $maxCount = max(count($indicators), count($accomplishments), 1);
                        ?>

                        <?php for ($i = 0; $i < $maxCount; $i++): ?>
                        <tr>
                            <!-- Project Name Column (only first row with rowspan) -->
                            <?php if ($i === 0): ?>
                            <td rowspan="<?php echo $maxCount; ?>" style="text-align: center; font-weight: bold;">
                                <?php echo $projectIndex . '. ' . htmlspecialchars($project['project_name']); ?>
                            </td>
                            <?php endif; ?>

                            <!-- Success Indicators Column -->
                            <td>
                                <?php if (isset($indicators[$i])): ?>
                                    <?php echo ($i + 1) . '. ' . htmlspecialchars(trim($indicators[$i])); ?>
                                <?php endif; ?>
                            </td>

                            <!-- Accomplishments Column -->
                            <td>
                                <?php if (isset($accomplishments[$i])): ?>
                                    <?php echo ($i + 1) . '. ' . htmlspecialchars(trim($accomplishments[$i])); ?>
                                <?php endif; ?>
                            </td>

                            <!-- Attachment Column (only first row with rowspan) -->
                            <?php if ($i === 0): ?>
                            <td rowspan="<?php echo $maxCount; ?>" style="text-align: center;">
                                <?php if (!empty($project['attachment'])): ?>
                                    <span style="color: #2d3ba6; font-size: 10px;">✓ File Attached</span>
                                <?php else: ?>
                                    <span style="color: #999; font-size: 10px;">No file</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endfor; ?>

                        <?php $projectIndex++; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Performance Rating Section (if available) -->
    <?php if ($performance && $performance['average_rating'] > 0): ?>
    <div class="projects-section">
        <h3 class="projects-title">PERFORMANCE RATING</h3>
        <table class="projects-table">
            <tr>
                <td style="width: 70%; font-weight: bold;">Overall Performance Rating</td>
                <td style="text-align: center; font-size: 14px; font-weight: bold; color: #2d3ba6;">
                    <?php echo number_format($performance['average_rating'], 2); ?>/5.00
                </td>
            </tr>
        </table>
    </div>
    <?php endif; ?>

    <script>
        // Auto-close window after printing (optional)
        window.onafterprint = function() {
            setTimeout(function() {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>