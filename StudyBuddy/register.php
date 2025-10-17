<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = $message = '';
if ($_POST) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate required fields
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $hashed_password = md5($password); // ⚠️ Use password_hash() for stronger security in production

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->rowCount() > 0) {
            $error = "Username or email already exists.";
        } else {
            // Insert new user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $hashed_password])) {
                $message = "Registration successful! <a href='login.php'>Login now</a>.";
            } else {
                $error = "Registration failed. Try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - StudyBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/studybuddy-styles.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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

        .register-container {
            position: relative;
            z-index: 1;
            width: 100%;
            padding: 2rem 0;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="register-card">
                        <div class="register-header">
                            <div class="register-icon">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3>Join StudyBuddy</h3>
                            <p>Create your free account to get started</p>
                        </div>
                        
                        <div class="register-body">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($message): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="registerForm">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="username" id="username"
                                               placeholder="Choose a username" required>
                                        <span class="input-icon">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email
                                    </label>
                                    <div class="input-group">
                                        <input type="email" class="form-control" name="email" id="email"
                                               placeholder="Enter your email" required>
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
                                        <input type="password" class="form-control" id="password" 
                                               name="password" placeholder="Create a password" required minlength="6">
                                        <span class="input-icon password-toggle" onclick="togglePassword('password', 'toggleIcon1')">
                                            <i class="fas fa-eye" id="toggleIcon1"></i>
                                        </span>
                                    </div>
                                    <div class="password-strength">
                                        <div class="password-strength-bar" id="strengthBar"></div>
                                    </div>
                                    <div class="password-hint">
                                        <i class="fas fa-info-circle me-1"></i>Minimum 6 characters
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-lock me-2"></i>Confirm Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirmPassword" 
                                               name="confirm_password" placeholder="Confirm your password" required minlength="6">
                                        <span class="input-icon password-toggle" onclick="togglePassword('confirmPassword', 'toggleIcon2')">
                                            <i class="fas fa-eye" id="toggleIcon2"></i>
                                        </span>
                                    </div>
                                    <div class="match-indicator" id="matchIndicator"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-register w-100">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </button>
                            </form>

                            <div class="divider">
                                <span>or</span>
                            </div>

                            <div class="link-group">
                                <p class="mb-2">
                                    Already have an account? 
                                    <a href="login.php">
                                        <i class="fas fa-sign-in-alt me-1"></i>Login here
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
        function togglePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = document.getElementById(iconId);
            
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

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            let strength = 0;

            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[A-Z]/.test(password) && /[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Password match indicator
        function checkPasswordMatch() {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const matchIndicator = document.getElementById('matchIndicator');

            if (confirmPassword.length === 0) {
                matchIndicator.innerHTML = '';
                return;
            }

            if (password === confirmPassword) {
                matchIndicator.innerHTML = '<i class="fas fa-check-circle me-1"></i>Passwords match';
                matchIndicator.className = 'match-indicator match-success';
            } else {
                matchIndicator.innerHTML = '<i class="fas fa-times-circle me-1"></i>Passwords do not match';
                matchIndicator.className = 'match-indicator match-error';
            }
        }

        document.getElementById('password').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }
        });

        // Input focus animation
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