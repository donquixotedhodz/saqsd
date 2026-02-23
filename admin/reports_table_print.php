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

// Get all reports for printing (no pagination)
$query = "SELECT ar.*, u.first_name, u.last_name, u.department, u.position, u.employee_id as emp_id,
          pr.average_rating
          FROM accomplishment_reports ar
          LEFT JOIN users u ON ar.employee_id = u.id
          LEFT JOIN performance_ratings pr ON ar.id = pr.report_id
          $whereClause
          ORDER BY u.first_name, u.last_name, ar.created_at DESC";

$reports = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $reports[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports Overview - Print</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 0.5in;
        }

        body {
            font-family: 'Inter', sans-serif;
            font-size: 30px;
            line-height: 1.3;
            color: #000;
            background: white;
            margin: 0;
            padding: 0;
        }

        .print-header {
            text-align: center;
            margin-bottom: 15px;
           
            padding-bottom: 10px;
        }

        .print-header h1 {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }

        .print-header p {
            font-size: 10px;
            margin: 3px 0;
        }

        .print-header h2 {
            font-size: 12px;
            font-weight: bold;
            margin: 8px 0 0 0;
            text-transform: uppercase;
        }

        .print-info {
            margin-bottom: 15px;
            font-size: 9px;
            color: #000;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 8px;
        }

        .print-table th {
            border: 1px solid #000;
            padding: 6px 4px;
            font-weight: bold;
            background-color: #f5f5f5;
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
        }

        .print-table td {
            border: 1px solid #000;
            padding: 4px;
            vertical-align: top;
            font-size: 8px;
        }

        .employee-name {
            font-weight: bold;
            color: #000;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-draft { background-color: #f3f4f6; color: #374151; }
        .status-submitted { background-color: #dbeafe; color: #1e40af; }
        .status-reviewed { background-color: #fef3c7; color: #b45309; }
        .status-approved { background-color: #dcfce7; color: #166534; }

        .period-info {
            line-height: 1.2;
        }

        .no-reports {
            text-align: center;
            padding: 40px;
            font-size: 12px;
            color: #666;
        }

        .print-footer {
            margin-top: 20px;
            text-align: center;
            font-size: 8px;
            color: #000;
            padding-top: 10px;
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
    <!-- Header -->
    <div class="print-header">
        <div style="text-align: center; margin-bottom: 10px;">
            <img src="../images/nealogo.png" alt="NEA Logo" style="width: 60px; height: 60px; object-fit: contain;">
        </div>
        <h1>NATIONAL ELECTRICFICATION ADMINISTRATION</h1>
        <p>Strategic Performance Management System</p>
        <h2>REPORTS OVERVIEW</h2>
    </div>

    <!-- Print Info -->
    <div class="print-info">
        <strong>Generated on:</strong> <?php echo date('F d, Y \a\t H:i:s'); ?> |
        <strong>Total Reports:</strong> <?php echo count($reports); ?>
        <?php if (!empty($filterStatus)): ?>
            | <strong>Status Filter:</strong> <?php echo ucfirst($filterStatus); ?>
        <?php endif; ?>
        <?php if (!empty($searchName)): ?>
            | <strong>Search:</strong> "<?php echo htmlspecialchars($searchName); ?>"
        <?php endif; ?>
    </div>

    <?php if (count($reports) > 0): ?>
        <!-- Reports Table -->
        <table class="print-table">
            <thead>
                <tr>
                    <th style="width: 5%;">No.</th>
                    <th style="width: 25%;">Employee Name</th>
                    <th style="width: 15%;">Employee ID</th>
                    <th style="width: 20%;">Department</th>
                    <th style="width: 22%;">Reporting Period</th>
                    <th style="width: 10%;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php $counter = 1; ?>
                <?php foreach ($reports as $report): ?>
                    <tr>
                        <td style="text-align: center;"><?php echo $counter++; ?></td>
                        <td>
                            <div class="employee-name"><?php echo htmlspecialchars($report['first_name'] . ' ' . $report['last_name']); ?></div>
                        </td>
                        <td style="text-align: center;"><?php echo htmlspecialchars($report['emp_id']); ?></td>
                        <td><?php echo htmlspecialchars($report['department']); ?></td>
                        <td>
                            <div class="period-info">
                                <?php echo date('M d, Y', strtotime($report['reporting_period_start'])); ?> to <?php echo date('M d, Y', strtotime($report['reporting_period_end'])); ?>
                            </div>
                        </td>
                        <td style="text-align: center;">
                            <span class="status-badge status-<?php echo $report['status']; ?>">
                                <?php echo ucfirst($report['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="no-reports">
            <p>No reports found matching the current filters.</p>
        </div>
    <?php endif; ?>

    <!-- Footer -->
    <div class="print-footer">
        <p>This report was generated from the Strategic Performance Management System</p>
        <p>Printed on <?php echo date('F d, Y \a\t H:i:s'); ?></p>
    </div>

    <script>
        // Optional: Add functionality after printing if needed
        // window.onafterprint = function() {
        //     // Removed auto-close functionality
        //     // Users can manually close the tab when ready
        // };
    </script>
</body>
</html>