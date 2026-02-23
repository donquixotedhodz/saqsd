<?php
session_start();
require_once '../backend/config.php';
require_once '../backend/includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$user = getUserById($userId, $conn);

$successMessage = '';
$errorMessage = '';

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_file'])) {
    $title = $conn->real_escape_string($_POST['title']);
    $category = $conn->real_escape_string($_POST['category']);

    // If category is "Other", use the custom category
    if ($category === 'Other') {
        $customCategory = $conn->real_escape_string($_POST['custom_category']);
        if (empty($customCategory)) {
            $errorMessage = 'Custom category is required when "Other" is selected!';
        }
        else {
            $category = $customCategory;
            // Add the custom category to the categories table if it doesn't exist
            $checkQuery = "SELECT id FROM categories WHERE name = '$category'";
            $checkResult = $conn->query($checkQuery);
            if ($checkResult && $checkResult->num_rows == 0) {
                $insertQuery = "INSERT INTO categories (name) VALUES ('$category')";
                $conn->query($insertQuery);
            }
        }
    }

    if (empty($title)) {
        $errorMessage = 'Title is required!';
    }
    elseif (empty($category)) {
        $errorMessage = 'Category is required!';
    }
    elseif (!isset($_FILES['storage_file']) || $_FILES['storage_file']['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Please select a file to upload!';
    }
    else {
        $uploadDir = '../uploads/storage/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileInfo = pathinfo($_FILES['storage_file']['name']);
        $fileName = time() . '_' . basename($_FILES['storage_file']['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['storage_file']['tmp_name'], $filePath)) {
            $fileSize = filesize($filePath);
            $uploadedBy = $userId;

            $query = "INSERT INTO database_storage (title, category, file_name, file_size, uploaded_by)
                      VALUES ('$title', '$category', '$fileName', $fileSize, $uploadedBy)";

            if ($conn->query($query)) {
                $successMessage = 'File uploaded successfully!';
                logAction($userId, 'UPLOAD', 'storage', $conn->insert_id, $conn);
            }
            else {
                $errorMessage = 'Error saving file information: ' . $conn->error;
                unlink($filePath);
            }
        }
        else {
            $errorMessage = 'Error moving uploaded file!';
        }
    }
}

// Handle file delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_file'])) {
    $fileId = intval($_POST['file_id']);

    $query = "SELECT file_name FROM database_storage WHERE id = $fileId";
    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $filePath = '../uploads/storage/' . $row['file_name'];

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $deleteQuery = "DELETE FROM database_storage WHERE id = $fileId";
        if ($conn->query($deleteQuery)) {
            $successMessage = 'File deleted successfully!';
            logAction($userId, 'DELETE', 'storage', $fileId, $conn);
        }
        else {
            $errorMessage = 'Error deleting file!';
        }
    }
}

// Get search parameters
$searchCategory = isset($_GET['search_category']) ? $conn->real_escape_string($_GET['search_category']) : '';
$searchDate = isset($_GET['search_date']) ? $conn->real_escape_string($_GET['search_date']) : '';
$searchText = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';

// Check if any filter is active
$hasActiveFilter = !empty($searchText) || !empty($searchCategory) || !empty($searchDate);

// Get pagination parameters (only used when no filter is active)
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
    $itemsPerPage = 10;
}
$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1)
    $currentPage = 1;

// Build WHERE clause for search
$whereClause = "WHERE 1=1";
if (!empty($searchText)) {
    $whereClause .= " AND ds.title LIKE '%$searchText%'";
}
if (!empty($searchCategory)) {
    $whereClause .= " AND ds.category = '$searchCategory'";
}
if (!empty($searchDate)) {
    $whereClause .= " AND DATE(ds.created_at) = '$searchDate'";
}

// Get total count
$countQuery = "SELECT COUNT(*) as total FROM database_storage ds
               LEFT JOIN users u ON ds.uploaded_by = u.id
               $whereClause";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalItems = $countRow['total'];

// Calculate pagination (disabled when filter is active - show all results)
if ($hasActiveFilter) {
    $totalPages = 1;
    $limitClause = "";
}
else {
    $totalPages = $itemsPerPage > 0 ? ceil($totalItems / $itemsPerPage) : 1;
    if ($currentPage > $totalPages && $totalPages > 0) {
        $currentPage = $totalPages;
    }
    $offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
    $limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";
}

$query = "SELECT ds.*, u.first_name, u.last_name FROM database_storage ds
          LEFT JOIN users u ON ds.uploaded_by = u.id
          $whereClause
          ORDER BY ds.created_at DESC
          $limitClause";
$result = $conn->query($query);
$storageFiles = [];
while ($row = $result->fetch_assoc()) {
    $storageFiles[] = $row;
}

// Get categories from categories table for auto-suggestions
$categories = [];
$categoriesQuery = "SELECT name FROM categories ORDER BY name";
$categoriesResult = $conn->query($categoriesQuery);
if ($categoriesResult) {
    while ($row = $categoriesResult->fetch_assoc()) {
        $categories[] = $row['name'];
    }
}

$allCategories = $categories;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Storage</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- SweetAlert2 -->
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
        .btn-danger {
            background-color: #ef4444;
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
        }
        .btn-danger:hover {
            background-color: #dc2626;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
        }
        .form-control {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            font-size: 0.875rem;
        }
        .form-control:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(60, 80, 224, 0.1);
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background-color: #dcfce7;
            color: #166534;
            border: 1px solid #86efac;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        .sidebar-active {
            border-left-color: var(--color-primary);
            background-color: #f3f4f6;
            color: var(--color-primary);
        }
        .table-row:hover {
            background-color: #f9fafb;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 50;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        .modal-content {
            background: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: all 0.3s ease;
        }
        .modal-overlay.active .modal-content {
            transform: scale(1);
        }
        .modal-content.modal-xlarge {
            max-width: 1200px;
            width: 95%;
        }
        .modal-header {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-body {
            padding: 1.5rem;
        }
        .modal-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }

        /* Document viewer */
        .document-viewer {
            width: 100%;
            height: 500px;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            background-color: #f9fafb;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php $activePage = 'storage'; ?>
    <div class="flex h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'Database Storage';
$pageIcon = 'fas fa-database';
$headerRight = '<button onclick="openUploadModal()" class="btn-primary"><i class="fas fa-upload mr-2"></i>Upload File</button>';
include 'includes/header.php';
?>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($pageTitle ?? 'Page'); ?></h1>
                    <p class="text-gray-600 mt-1">Overview and management for <?php echo strtolower($pageTitle ?? 'this section'); ?>.</p>
                </div>
                <?php if ($successMessage): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php echo htmlspecialchars($successMessage); ?></span>
                    </div>
                <?php
endif; ?>

                <?php if ($errorMessage): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span><?php echo htmlspecialchars($errorMessage); ?></span>
                    </div>
                <?php
endif; ?>

                <!-- Files Table -->
                <div class="card">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Uploaded Files</h3>

                    <!-- Search and Filter Section -->
                    <form method="GET" id="filterForm" class="mb-6 p-4 bg-gray-50 rounded">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                            <div class="form-group mb-0">
                                <label for="search">Search</label>
                                <input type="text" id="search" name="search" class="form-control" placeholder="Search by title..." value="<?php echo htmlspecialchars($searchText); ?>" onkeyup="debounceSearch()">
                            </div>

                            <div class="form-group mb-0">
                                <label for="search_category">Filter by Category</label>
                                <select id="search_category" name="search_category" class="form-control" onchange="document.getElementById('filterForm').submit()">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $searchCategory === $cat ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat); ?>
                                        </option>
                                    <?php
endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group mb-0">
                                <label for="search_date">Filter by Date</label>
                                <input type="date" id="search_date" name="search_date" class="form-control" value="<?php echo htmlspecialchars($searchDate); ?>" onchange="document.getElementById('filterForm').submit()">
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <a href="storage.php" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                                <i class="fas fa-redo mr-2"></i>Reset
                            </a>
                        </div>
                    </form>

                    <!-- Results Count and Pagination Info -->
                    <div class="mb-4 flex items-center justify-between">
                        <div class="text-sm text-gray-600">
                            <p>
                                <strong>Total Files:</strong> <?php echo $totalItems; ?>
                                <?php if ($itemsPerPage > 0): ?>
                                    | <strong>Showing:</strong> <?php echo(($currentPage - 1) * $itemsPerPage) + 1; ?> - <?php echo min($currentPage * $itemsPerPage, $totalItems); ?>
                                <?php
endif; ?>
                            </p>
                        </div>

                        <div style="max-width: 200px;">
                            <form method="GET" style="display: flex; gap: 0.5rem; align-items: flex-end;">
                                <input type="hidden" name="search" value="<?php echo htmlspecialchars($searchText); ?>">
                                <input type="hidden" name="search_category" value="<?php echo htmlspecialchars($searchCategory); ?>">
                                <input type="hidden" name="search_date" value="<?php echo htmlspecialchars($searchDate); ?>">
                                <input type="hidden" name="page" value="1">
                                <div style="flex: 1;">
                                    <label for="per_page" style="font-size: 0.875rem; font-weight: 500; color: #374151; display: block; margin-bottom: 0.25rem;">Items Per Page</label>
                                    <select id="per_page" name="per_page" class="form-control" onchange="this.form.submit()" style="font-size: 0.875rem;">
                                        <option value="10" <?php echo $itemsPerPage === 10 ? 'selected' : ''; ?>>10 per page</option>
                                        <option value="25" <?php echo $itemsPerPage === 25 ? 'selected' : ''; ?>>25 per page</option>
                                        <option value="50" <?php echo $itemsPerPage === 50 ? 'selected' : ''; ?>>50 per page</option>
                                        <option value="0" <?php echo $itemsPerPage === 0 ? 'selected' : ''; ?>>Show All</option>
                                    </select>
                                </div>
                            </form>
                        </div>
                    </div>

                    <?php if (empty($storageFiles)): ?>
                        <p class="text-gray-600 text-center py-8">No files found.</p>
                    <?php
else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr style="border-bottom: 2px solid #e5e7eb;">
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">Title</th>
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">Category</th>
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">File Name</th>
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">Size</th>
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">Uploaded By</th>
                                        <th class="text-left py-3 px-3 font-semibold text-gray-700">Uploaded Date</th>
                                        <th class="text-center py-3 px-3 font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($storageFiles as $file): ?>
                                        <tr class="table-row">
                                            <td class="py-3 px-3"><?php echo htmlspecialchars($file['title']); ?></td>
                                            <td class="py-3 px-3">
                                                <span class="px-2 py-1 rounded-full text-xs font-semibold" style="background-color: #e0e7ff; color: #3730a3;">
                                                    <?php echo htmlspecialchars($file['category']); ?>
                                                </span>
                                            </td>
                                            <td class="py-3 px-3 text-gray-600 truncate max-w-xs"><?php echo htmlspecialchars($file['file_name']); ?></td>
                                            <td class="py-3 px-3 text-gray-600"><?php echo round($file['file_size'] / 1024, 2) . ' KB'; ?></td>
                                            <td class="py-3 px-3 text-gray-600"><?php echo htmlspecialchars($file['first_name'] . ' ' . $file['last_name']); ?></td>
                                            <td class="py-3 px-3 text-gray-600 whitespace-nowrap"><?php echo date('M d, Y H:i', strtotime($file['created_at'])); ?></td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex gap-2 justify-center">
                                                    <button type="button" onclick="viewFile('<?php echo htmlspecialchars($file['file_name']); ?>', '<?php echo htmlspecialchars(addslashes($file['title']), ENT_QUOTES); ?>')" class="inline-flex items-center justify-center w-10 h-10 bg-green-500 text-white hover:bg-green-600 rounded-lg text-base transition-colors shadow-sm" title="Preview">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="../uploads/storage/<?php echo htmlspecialchars($file['file_name']); ?>" download class="inline-flex items-center justify-center w-10 h-10 bg-blue-500 text-white hover:bg-blue-600 rounded-lg text-base transition-colors shadow-sm" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    <button type="button" onclick="deleteFile(<?php echo $file['id']; ?>, '<?php echo htmlspecialchars(addslashes($file['title']), ENT_QUOTES); ?>')" class="inline-flex items-center justify-center w-10 h-10 bg-red-500 text-white hover:bg-red-600 rounded-lg text-base transition-colors shadow-sm" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                    <form id="delete-form-<?php echo $file['id']; ?>" method="POST" style="display: none;">
                                                        <input type="hidden" name="file_id" value="<?php echo $file['id']; ?>">
                                                        <input type="hidden" name="delete_file" value="1">
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php
    endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination Controls -->
                        <?php if ($itemsPerPage > 0 && $totalPages > 1): ?>
                            <div class="mt-6 flex items-center justify-between">
                                <div class="text-sm text-gray-600">
                                    Page <strong><?php echo $currentPage; ?></strong> of <strong><?php echo $totalPages; ?></strong>
                                </div>

                                <div class="flex gap-2">
                                    <?php if ($currentPage > 1): ?>
                                        <a href="?page=1&per_page=<?php echo $itemsPerPage; ?>&search=<?php echo urlencode($searchText); ?>&search_category=<?php echo urlencode($searchCategory); ?>&search_date=<?php echo urlencode($searchDate); ?>" class="btn-secondary px-3 py-2">
                                            <i class="fas fa-step-backward mr-1"></i>First
                                        </a>
                                        <a href="?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?>&search=<?php echo urlencode($searchText); ?>&search_category=<?php echo urlencode($searchCategory); ?>&search_date=<?php echo urlencode($searchDate); ?>" class="btn-secondary px-3 py-2">
                                            <i class="fas fa-chevron-left mr-1"></i>Previous
                                        </a>
                                    <?php
        endif; ?>

                                    <?php
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);

        if ($startPage > 1): ?>
                                        <span class="px-3 py-2">...</span>
                                    <?php
        endif;

        for ($i = $startPage; $i <= $endPage; $i++):
            $isActive = $i === $currentPage;
?>
                                        <a href="?page=<?php echo $i; ?>&per_page=<?php echo $itemsPerPage; ?>&search=<?php echo urlencode($searchText); ?>&search_category=<?php echo urlencode($searchCategory); ?>&search_date=<?php echo urlencode($searchDate); ?>"
                                           class="px-3 py-2 rounded <?php echo $isActive ? 'bg-blue-500 text-white font-bold' : 'btn-secondary'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php
        endfor;

        if ($endPage < $totalPages): ?>
                                        <span class="px-3 py-2">...</span>
                                    <?php
        endif; ?>

                                    <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?>&search=<?php echo urlencode($searchText); ?>&search_category=<?php echo urlencode($searchCategory); ?>&search_date=<?php echo urlencode($searchDate); ?>" class="btn-secondary px-3 py-2">
                                            Next<i class="fas fa-chevron-right ml-1"></i>
                                        </a>
                                        <a href="?page=<?php echo $totalPages; ?>&per_page=<?php echo $itemsPerPage; ?>&search=<?php echo urlencode($searchText); ?>&search_category=<?php echo urlencode($searchCategory); ?>&search_date=<?php echo urlencode($searchDate); ?>" class="btn-secondary px-3 py-2">
                                            Last<i class="fas fa-step-forward ml-1"></i>
                                        </a>
                                    <?php
        endif; ?>
                                </div>
                            </div>
                        <?php
    endif; ?>
                    <?php
endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Upload File Modal -->
    <div id="uploadModal" class="modal-overlay" onclick="closeUploadModal(event)">
        <div class="modal-content" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-upload mr-2 text-blue-500"></i>Upload File to Storage
                </h3>
                <button onclick="closeUploadModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" id="uploadForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Title *</label>
                        <input type="text" id="title" name="title" class="form-control" placeholder="Enter file title" required>
                    </div>

                    <div class="form-group">
                        <label for="category">Category *</label>
                        <select id="category" name="category" class="form-control" required onchange="toggleCustomCategory()">
                            <option value="">Select Category</option>
                            <?php foreach ($allCategories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat); ?>">
                                    <?php echo htmlspecialchars($cat); ?>
                                </option>
                            <?php
endforeach; ?>
                        </select>
                    </div>

                    <div id="customCategoryDiv" style="display: none;">
                        <label for="custom_category">Custom Category *</label>
                        <input type="text" id="custom_category" name="custom_category" class="form-control" placeholder="Enter custom category">
                    </div>

                    <div class="form-group">
                        <label for="storage_file">Upload File *</label>
                        <input type="file" id="storage_file" name="storage_file" class="form-control" required>
                        <p class="text-xs text-gray-500 mt-1">Supported formats: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, JPG, PNG, GIF</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeUploadModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                        <i class="fas fa-times mr-2"></i>Cancel
                    </button>
                    <button type="submit" name="upload_file" class="btn-primary">
                        <i class="fas fa-upload mr-2"></i>Upload File
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- File Viewer Modal -->
    <div id="fileModal" class="modal-overlay" onclick="closeFileModal(event)">
        <div class="modal-content modal-xlarge" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-lg font-bold text-gray-800">
                    <i class="fas fa-file mr-2 text-green-500"></i><span id="fileModalTitle">File Viewer</span>
                </h3>
                <button onclick="closeFileModal()" class="text-gray-400 hover:text-gray-600 text-xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <div id="fileViewerContainer">
                    <iframe id="fileViewer" class="document-viewer" src="" frameborder="0"></iframe>
                </div>
                <div id="fileNotFound" style="display: none;" class="text-center py-12">
                    <i class="fas fa-file-excel text-6xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg">File not found or unavailable</p>
                    <p class="text-gray-400 text-sm mt-2">The file may have been moved or deleted.</p>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="downloadFileLink" download class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                    <i class="fas fa-download mr-2"></i>Download
                </a>
                <button type="button" onclick="closeFileModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors">
                    <i class="fas fa-times mr-2"></i>Close
                </button>
            </div>
        </div>
    </div>

    <script>
    // =====================
    // Upload Modal Functions
    // =====================
    function openUploadModal() {
        document.getElementById('uploadModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        // Reset form
        document.getElementById('uploadForm').reset();
    // Hide custom category initially
    document.getElementById('customCategoryDiv').style.display = 'none';
}

function closeUploadModal(event) {
    if (event && event.target !== event.currentTarget) return;
    document.getElementById('uploadModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Toggle custom category input
function toggleCustomCategory() {
    const categorySelect = document.getElementById('category');
    const customCategoryDiv = document.getElementById('customCategoryDiv');
    const customCategoryInput = document.getElementById('custom_category');

    if (categorySelect.value === 'Other') {
        customCategoryDiv.style.display = 'block';
        customCategoryInput.required = true;
    } else {
        customCategoryDiv.style.display = 'none';
        customCategoryInput.required = false;
        customCategoryInput.value = '';
    }
}

    // =====================
    // File Viewer Modal
    // =====================
    function viewFile(fileName, title) {
        document.getElementById('fileModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        document.getElementById('fileModalTitle').textContent = title;

        // Construct the file URL
        const fileUrl = '../uploads/storage/' + fileName;

        // Check file type
        const extension = fileName.split('.').pop().toLowerCase();
        const viewer = document.getElementById('fileViewer');
        const container = document.getElementById('fileViewerContainer');
        const notFound = document.getElementById('fileNotFound');
        const downloadLink = document.getElementById('downloadFileLink');

        downloadLink.href = fileUrl;

        if (['pdf'].includes(extension)) {
            container.style.display = 'block';
            notFound.style.display = 'none';
            container.innerHTML = `<iframe id="fileViewer" class="document-viewer" src="${fileUrl}" frameborder="0"></iframe>`;
        } else if (['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'].includes(extension)) {
            container.innerHTML = `<img src="${fileUrl}" alt="${title}" class="max-w-full h-auto rounded-lg shadow-lg mx-auto" style="max-height: 500px;" onerror="showFileNotFound()">`;
            container.style.display = 'block';
            notFound.style.display = 'none';
        } else if (['txt', 'csv', 'html', 'htm', 'xml', 'json'].includes(extension)) {
            container.style.display = 'block';
            notFound.style.display = 'none';
            container.innerHTML = `<iframe id="fileViewer" class="document-viewer" src="${fileUrl}" frameborder="0"></iframe>`;
        } else if (['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'].includes(extension)) {
            // For Office documents, show a message with download option
            container.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-file-word text-6xl text-blue-400 mb-4"></i>
                    <p class="text-gray-600 text-lg">This document type cannot be previewed directly.</p>
                    <p class="text-gray-500 text-sm mt-2">Please download the file to view it.</p>
                </div>
            `;
            container.style.display = 'block';
            notFound.style.display = 'none';
        } else {
            container.style.display = 'none';
            notFound.style.display = 'block';
        }
    }

    function showFileNotFound() {
        document.getElementById('fileViewerContainer').style.display = 'none';
        document.getElementById('fileNotFound').style.display = 'block';
    }

    function closeFileModal(event) {
        if (event && event.target !== event.currentTarget) return;
        document.getElementById('fileModal').classList.remove('active');
        document.body.style.overflow = '';
        document.getElementById('fileViewerContainer').innerHTML = '<iframe id="fileViewer" class="document-viewer" src="" frameborder="0"></iframe>';
    }

    // Close modal with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeUploadModal();
            closeFileModal();
        }
    });

    // =====================
    // Delete File Function
    // =====================
    function deleteFile(fileId, fileTitle) {
        Swal.fire({
            title: 'Delete File?',
            html: `Are you sure you want to delete <strong>${fileTitle}</strong>?<br><br><span class="text-red-600 text-sm">This action cannot be undone!</span>`,
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
                document.getElementById('delete-form-' + fileId).submit();
            }
        });
    }

    // =====================
    // Debounce Search Function
    // =====================
    let searchTimeout;
    function debounceSearch() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            document.getElementById('filterForm').submit();
        }, 500); // Wait 500ms after user stops typing
    }

    // Show success/error alerts if messages exist
    <?php if ($successMessage): ?>
    Swal.fire({
        title: 'Success!',
        text: '<?php echo addslashes($successMessage); ?>',
        icon: 'success',
        showConfirmButton: true,
        confirmButtonText: 'OK',
        confirmButtonColor: '#3c50e0'
    });
    <?php
endif; ?>

    <?php if ($errorMessage): ?>
    Swal.fire({
        title: 'Error!',
        text: '<?php echo addslashes($errorMessage); ?>',
        icon: 'error',
        confirmButtonColor: '#3c50e0'
    });
    <?php
endif; ?>
    </script>
</body>
</html>