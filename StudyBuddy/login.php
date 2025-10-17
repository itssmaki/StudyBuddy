<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';
$successMessage = '';  // For report success
$showReportForm = false;  // Flag to show report form

// Handle report form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_inactive'])) {
    $reportUsername = trim($_POST['report_username']);
    $reportEmail = trim($_POST['report_email']);

    if (empty($reportUsername) || empty($reportEmail)) {
        $error = "Please fill in all fields for the report.";
    } else {
        // Insert into admin_notifications
        $stmt = $pdo->prepare("INSERT INTO admin_notifications (message, created_at) VALUES (?, NOW())");
        $stmt->execute(["Inactive account report from $reportUsername with email $reportEmail."]);
        
        $successMessage = "Your report has been sent to the administrator. They will review it shortly.";
        $showReportForm = false;  // Hide form after success
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_input'])) {
    $login_input = trim($_POST['login_input']);
    $password = md5(trim($_POST['password']));  // Use password_hash() in production

    if (empty($login_input) || empty($_POST['password'])) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND password = ?");
        $stmt->execute([$login_input, $login_input, $password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            if ($user['is_active'] == 1) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Your account is inactive. ";
                $showReportForm = true;  // Show report form
            }
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - StudyBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/studybuddy-styles.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
            opacity: 0.3;
        }

    </style>
</head>
<body>
    <div class="login-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="login-card">
                        <div class="login-header">
                            <div class="login-icon">
                                <i class="fas fa-user-circle"></i>
                            </div>
                            <h3>Welcome Back!</h3>
                            <p>Login to continue your learning journey</p>
                        </div>
                        
                        <div class="login-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                    <?php if ($showReportForm): ?>
                                        <button class="btn btn-link" onclick="showReportForm()">Contact Administrator</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($successMessage): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $successMessage; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="loginForm">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Username or Email
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="login_input" 
                                               placeholder="Enter your username or email" required>
                                        <span class="input-icon">
                                            <i class="fas fa-at"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-lock me-2"></i>Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="passwordInput" 
                                               name="password" placeholder="Enter your password" required>
                                        <span class="input-icon password-toggle" onclick="togglePassword()">
                                            <i class="fas fa-eye" id="toggleIcon"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <button type="submit" class="btn btn-login w-100">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </button>
                            </form>

                            <?php if ($showReportForm): ?>
                                <div id="reportFormContainer" style="display: none;">
                                    <h5 class="mt-4">Report Inactive Account</h5>
                                    <form method="POST">
                                        <input type="hidden" name="report_inactive" value="1">
                                        <div class="mb-3">
                                            <label for="report_username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="report_username" name="report_username" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="report_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="report_email" name="report_email" required>
                                        </div>
                                        <button type="submit" class="btn btn-primary">Submit Report</button>
                                    </form>
                                </div>
                            <?php endif; ?>

                            <div class="link-group mt-3">
                                <a href="admin_login.php" class="admin-link">
                                    <i class="fas fa-shield-alt"></i>
                                    <span>Admin Login</span>
                                </a>
                            </div>

                            <div class="divider">
                                <span>or</span>
                            </div>

                            <div class="link-group">
                                <p class="mb-2">
                                    <a href="forgot_password.php">
                                        <i class="fas fa-key me-1"></i>Forgot Password?
                                    </a>
                                </p>
                                <p class="mb-2">
                                    Don't have an account? 
                                    <a href="register.php">
                                        <i class="fas fa-user-plus me-1"></i>Register here
                                    </a>
                                </p>
                            </div>

                            <div class="text-center mt-3">
                                <a href="index.php" class="back-home">
                                    <i class="fas fa-arrow-left"></i>
                                    <span>Back to Home</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('passwordInput');
            const toggleIcon = document.getElementById('toggleIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        function showReportForm() {
            document.getElementById('reportFormContainer').style.display = 'block';
        }

        // Add input focus animation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
