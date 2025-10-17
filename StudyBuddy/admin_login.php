<?php
session_start();
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();

        if ($admin && hash('sha256', $password) === $admin['password']) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header("Location: admin_dashboard.php");
            exit;
        } else {
            $error = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - StudyBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/studybuddy-styles.css">
    <style>
        :root {
            --admin-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        }

        body {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            position: relative;
            overflow-y: auto; 
            overflow-x: hidden; 
            padding: 3rem 1rem; 
        }
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 107, 107, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(238, 90, 111, 0.1) 0%, transparent 50%);
            animation: gradientShift 10s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% {
                opacity: 0.5;
            }
            50% {
                opacity: 1;
            }
        }

        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><defs><pattern id="grid" width="100" height="100" patternUnits="userSpaceOnUse"><path d="M 100 0 L 0 0 0 100" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="1"/></pattern></defs><rect width="100%" height="100%" fill="url(%23grid)"/></svg>');
        }

        .admin-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 700px;
            padding: 2rem;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-card">
            <div class="admin-header">
                <div class="shield-container">
                    <div class="shield">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                </div>
                <h4>StudyBuddy Admin</h4>
                <p>Secure Administrator Access</p>
            </div>
            
            <div class="admin-body">
                <div class="security-badge">
                    <i class="fas fa-lock"></i>
                    <span>Protected Area - Admin Access Only</span>
                </div>

                <?php if ($error): ?>
                    <div class="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="adminLoginForm">
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-user-shield me-2"></i>Admin Username
                        </label>
                        <div class="input-group">
                            <input type="text" class="form-control" name="username" 
                                   placeholder="Enter admin username" required autofocus>
                            <span class="input-icon">
                                <i class="fas fa-user"></i>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">
                            <i class="fas fa-key me-2"></i>Admin Password
                        </label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="adminPassword" 
                                   name="password" placeholder="Enter admin password" required>
                            <span class="input-icon password-toggle" onclick="togglePassword()">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-admin w-100">
                        <i class="fas fa-sign-in-alt me-2"></i>Secure Login
                    </button>
                </form>

                <div class="divider">
                    <span>Access Control</span>
                </div>

                <div class="info-text">
                    <i class="fas fa-info-circle me-1"></i>
                    This area is restricted to authorized administrators only
                </div>

                <div class="text-center">
                    <a href="login.php" class="back-link">
                        <i class="fas fa-arrow-left"></i>
                        <span>User Login</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('adminPassword');
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

        // Add input focus animation
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Add shield rotation on form submit
        document.getElementById('adminLoginForm').addEventListener('submit', function() {
            const shield = document.querySelector('.shield');
            shield.style.animation = 'none';
            setTimeout(() => {
                shield.style.animation = 'shieldPulse 2s ease-in-out infinite, spinOnce 0.5s ease-out';
            }, 10);
        });
    </script>
    <style>
        @keyframes spinOnce {
            from {
                transform: rotate(0deg) scale(1);
            }
            to {
                transform: rotate(360deg) scale(1.05);
            }
        }
    </style>
</body>
</html>