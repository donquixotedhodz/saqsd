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

$action = isset($_GET['action']) ? $_GET['action'] : 'list';

// Get pagination parameters
$itemsPerPage = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
if (!in_array($itemsPerPage, [10, 25, 50, 0])) {
    $itemsPerPage = 10;
}

$currentPage = isset($_GET['page']) ? intval($_GET['page']) : 1;
if ($currentPage < 1)
    $currentPage = 1;

// Get total count of users
$countQuery = "SELECT COUNT(*) as total FROM users";
$countResult = $conn->query($countQuery);
$countRow = $countResult->fetch_assoc();
$totalUsers = $countRow['total'];

// Calculate pagination
$totalPages = $itemsPerPage > 0 ? ceil($totalUsers / $itemsPerPage) : 1;
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

// Get users
$offset = $itemsPerPage > 0 ? ($currentPage - 1) * $itemsPerPage : 0;
$limitClause = $itemsPerPage > 0 ? "LIMIT $itemsPerPage OFFSET $offset" : "";
$usersQuery = "SELECT * FROM users ORDER BY created_at DESC $limitClause";
$usersResult = $conn->query($usersQuery);
$usersList = [];
while ($row = $usersResult->fetch_assoc()) {
    $usersList[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users | SAQSD</title>
    <link rel="icon" type="image/png" href="../images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../src/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Status Badges */
        .badge-admin { background-color: #fee2e2; color: #dc2626; }
        .badge-supervisor { background-color: #fef3c7; color: #b45309; }
        .badge-employee { background-color: #e0e7ff; color: #3730a3; }
        
        .table-row:hover { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen">
        <?php $activePage = 'users'; ?>
        <?php include 'includes/sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col overflow-hidden">
            <?php
$pageTitle = 'User Management';
$pageIcon = 'fas fa-users';
$headerRight = '<button onclick="openCreateModal()" class="btn-primary"><i class="fas fa-plus mr-2"></i>Create User</button>';
include 'includes/header.php';
?>
            
            <main class="flex-1 overflow-y-auto p-6">
                <!-- Header Section -->
                <div class="mb-8">
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo $pageTitle; ?></h1>
                    <p class="text-gray-500 mt-1">Manage system access and user profiles.</p>
                </div>

                <div class="card p-0 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr>
                                    <th class="px-6 py-4">User</th>
                                    <th class="px-6 py-4">Employee ID</th>
                                    <th class="px-6 py-4">Department</th>
                                    <th class="px-6 py-4 text-center">Role</th>
                                    <th class="px-6 py-4 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersList as $u): ?>
                                <tr class="table-row">
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold">
                                                <?php echo substr($u['first_name'], 0, 1) . substr($u['last_name'], 0, 1); ?>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></p>
                                                <p class="text-xs text-gray-500"><?php echo htmlspecialchars($u['email']); ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 font-medium"><?php echo htmlspecialchars($u['employee_id']); ?></td>
                                    <td class="px-6 py-4">
                                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($u['department']); ?></span>
                                        <p class="text-xs text-gray-400"><?php echo htmlspecialchars($u['position']); ?></p>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider badge-<?php echo strtolower($u['role']); ?>">
                                            <?php echo htmlspecialchars($u['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <div class="flex gap-2 justify-center">
                                            <button onclick="openEditModal(<?php echo $u['id']; ?>)" class="btn-icon btn-icon-indigo" title="Edit Profile">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="resetPassword(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name']); ?>')" class="btn-icon btn-icon-amber" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($u['id'] != $userId): ?>
                                            <button onclick="deleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name']); ?>')" class="btn-icon btn-icon-red" title="Delete User">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php
    endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php
endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination Controls -->
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-100 flex flex-col md:flex-row justify-between items-center gap-4">
                        <div class="text-sm text-gray-500">
                            Showing <span class="font-bold text-gray-700"><?php echo count($usersList); ?></span> of <span class="font-bold text-gray-700"><?php echo $totalUsers; ?></span> total users
                        </div>
                        
                        <div class="flex gap-2">
                            <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="btn-secondary py-1 px-3 text-sm">Previous</a>
                            <?php
endif; ?>
                            
                            <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?>&per_page=<?php echo $itemsPerPage; ?>" class="btn-secondary py-1 px-3 text-sm">Next</a>
                            <?php
endif; ?>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createModal" class="modal-overlay" onclick="closeCreateModal(event)">
        <div class="modal-content modal-lg" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-user-plus text-indigo-500"></i>
                    Create New User
                </h3>
                <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="createForm" onsubmit="submitCreateForm(event)">
                <div class="modal-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="employee_id" required class="form-control" placeholder="ABC-1234">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" required class="form-control" placeholder="user@saqsd.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" required class="form-control" placeholder="John">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" required class="form-control" placeholder="Doe">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <select name="department" required class="form-control">
                                <option value="" disabled selected>Select Department</option>
                                <option value="IT Services">IT Services</option>
                                <option value="Human Resources">Human Resources</option>
                                <option value="Operations">Operations</option>
                                <option value="Finance">Finance</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" required class="form-control">
                                <option value="employee">Employee</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="md:col-span-2 form-group">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" required class="form-control" minlength="8" placeholder="••••••••">
                            <p class="text-xs text-gray-400 mt-2 italic">Minimum 8 characters. Default suggested: SAQSD_2024!</p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeCreateModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary px-8">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="modal-overlay" onclick="closeEditModal(event)">
        <div class="modal-content modal-lg" onclick="event.stopPropagation()">
            <div class="modal-header">
                <h3 class="text-xl font-bold text-gray-800">
                    <i class="fas fa-user-edit text-indigo-500"></i>
                    Edit User Details
                </h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="editForm" onsubmit="submitEditForm(event)">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-body">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div class="form-group">
                            <label class="form-label">Employee ID</label>
                            <input type="text" name="employee_id" id="edit_employee_id" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" id="edit_email" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" id="edit_first_name" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" id="edit_last_name" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Department</label>
                            <input type="text" name="department" id="edit_department" required class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Role</label>
                            <select name="role" id="edit_role" required class="form-control">
                                <option value="employee">Employee</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" onclick="closeEditModal()" class="btn-secondary">Cancel</button>
                    <button type="submit" class="btn-primary px-8">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const CONTROLLER = 'controller/UsersController.php';

    function openCreateModal() {
        document.getElementById('createForm').reset();
        document.getElementById('createModal').classList.add('active');
    }

    function closeCreateModal() { document.getElementById('createModal').classList.remove('active'); }
    function closeEditModal() { document.getElementById('editModal').classList.remove('active'); }

    async function openEditModal(id) {
        Swal.fire({ title: 'Fetching data...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        const res = await fetch(`${CONTROLLER}?action=get&user_id=${id}`);
        const data = await res.json();
        Swal.close();
        
        if (data.success) {
            const u = data.user;
            document.getElementById('edit_user_id').value = u.id;
            document.getElementById('edit_employee_id').value = u.employee_id;
            document.getElementById('edit_email').value = u.email;
            document.getElementById('edit_first_name').value = u.first_name;
            document.getElementById('edit_last_name').value = u.last_name;
            document.getElementById('edit_department').value = u.department;
            document.getElementById('edit_role').value = u.role;
            document.getElementById('editModal').classList.add('active');
        }
    }

    async function submitCreateForm(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'create');
        
        const res = await fetch(CONTROLLER, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire('Created!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    async function submitEditForm(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        fd.append('action', 'update');
        
        const res = await fetch(CONTROLLER, { method: 'POST', body: fd });
        const data = await res.json();
        
        if (data.success) {
            Swal.fire('Updated!', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('Error', data.message, 'error');
        }
    }

    function deleteUser(id, name) {
        Swal.fire({
            title: `Delete ${name}?`,
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Yes, delete user'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'delete');
                fd.append('user_id', id);
                const res = await fetch(CONTROLLER, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => location.reload());
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            }
        });
    }

    function resetPassword(id, name) {
        Swal.fire({
            title: 'Reset Password?',
            text: `Default password will be set for ${name}.`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Reset Password'
        }).then(async (result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('action', 'reset_password');
                fd.append('user_id', id);
                const res = await fetch(CONTROLLER, { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) Swal.fire('Success!', data.message, 'success');
            }
        });
    }
    </script>
</body>
</html>
