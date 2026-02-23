<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'backend/config.php';

// If already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    $userQuery = "SELECT role FROM users WHERE id = $userId";
    $userResult = $conn->query($userQuery);
    if ($userResult->num_rows > 0) {
        $user = $userResult->fetch_assoc();
        if ($user['role'] === 'admin') {
            header('Location: admin/dashboard.php');
        }
        else {
            header('Location: reports/dashboard.php');
        }
    }
    exit();
}

$errorMessage = '';
$loginAttempted = false;

// Handle login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $loginAttempted = true;
    $employeeId = $conn->real_escape_string($_POST['employee_id']);
    $password = $_POST['password'];

    $query = "SELECT * FROM users WHERE employee_id = '$employeeId'";
    $result = $conn->query($query);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            }
            else {
                header('Location: reports/dashboard.php');
            }
            exit();
        }
        else {
            $errorMessage = 'Invalid credentials';
        }
    }
    else {
        $errorMessage = 'Invalid credentials';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Accomplishment Report System</title>
     <link rel="icon" type="image/png" href="images/nealogo.png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --color-primary: #4f46e5;
        }
        html, body {
            height: 100%;
            overflow: hidden;
        }
        body {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            background: linear-gradient(rgba(15, 23, 42, 0.7), rgba(15, 23, 42, 0.7)), url('images/neabulding.jpg');
            background-position: center;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-font-smoothing: antialiased;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 440px;
            padding: 3rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .logo-section {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logo-circle {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
        }
        .login-title {
            font-size: 1.75rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
        }
        .login-subtitle {
            color: #64748b;
            font-size: 0.875rem;
            font-weight: 500;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--color-primary) 0%, #6366f1 100%);
            color: white;
            padding: 0.875rem;
            border-radius: 0.75rem;
            width: 100%;
            font-weight: 600;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.4);
            filter: brightness(1.1);
        }
        .input-field {
            width: 100%;
            padding: 0.875rem;
            border: 1.5px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
        }
        .input-field:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(60, 80, 224, 0.1);
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #374151;
            font-size: 0.875rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid;
        }
        .alert-error {
            background-color: #fee2e2;
            color: #dc2626;
            border-left-color: #dc2626;
        }
        .alert-info {
            background-color: #dbeafe;
            color: #1e40af;
            border-left-color: #1e40af;
        }
        .divider {
            text-align: center;
            margin: 2rem 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e5e7eb;
        }
        .divider-text {
            background: white;
            padding: 0 1rem;
            position: relative;
            color: #6b7280;
            font-size: 0.875rem;
        }
        .footer-links {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
        }
        .footer-link {
            text-align: center;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #1f2937;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }
        .footer-link:hover {
            background: #f3f4f6;
            border-color: var(--color-primary);
            color: var(--color-primary);
        }
        .page-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(0, 0, 0, 0.7);
            color: white;
            text-align: center;
            padding: 1rem;
            font-size: 0.75rem;
        }
        .security-notice {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 2rem;
            font-size: 0.75rem;
            color: #15803d;
        }
        
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Logo Section -->
        <div class="logo-section">
            <div class="logo-circle">
                <img src="images/nealogo.png" alt="NEA Logo" class="w-full h-full object-contain">
            </div>
            <h1 class="login-title">SAQSD</h1>
        </div>

        <!-- Error Message -->
        <?php if ($loginAttempted && !empty($errorMessage)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php
endif; ?>

        <!-- Info Message
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-2"></i>
            <strong>Login with your credentials.</strong> Both administrators and employees use this login.
        </div> -->

        <!-- Login Form -->
        <form method="POST" class="space-y-4">
            <div class="form-group">
                <label class="form-label">Username</label>
                <input 
                    type="text" 
                    name="employee_id" 
                    required 
                    autofocus 
                    class="input-field" 
                    placeholder="Enter your username"
                    value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>"
                >
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input 
                    type="password" 
                    name="password" 
                    required 
                    class="input-field" 
                    placeholder="Enter your password"
                >
            </div>

            <button type="submit" class="btn-primary">
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <!-- Divider
        <div class="divider">
            <span class="divider-text">Quick Access</span>
        </div> -->

        <!-- Quick Links -->
    </div>

    <!-- Footer -->
    <footer class="page-footer">
        © donquixotedhodz 2026. All rights reserved.
    </footer>
</body>
</html>
