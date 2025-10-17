<?php
require_once 'config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $message = "<div class='alert alert-danger'><i class='fas fa-exclamation-circle me-2'></i>Passwords do not match.</div>";
    } else {
        // Hash password (use password_hash in production)
        $hashed_password = md5($new_password);

        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);

        if ($stmt->rowCount() > 0) {
            $update = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
            $update->execute([$hashed_password, $username]);
            $message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Password has been reset successfully! <a href='login.php'>Login now</a></div>";
        } else {
            $message = "<div class='alert alert-danger'><i class='fas fa-user-times me-2'></i>Username not found.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - StudyBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/studybuddy-styles.css">
</head>
<body class="page-forgot-password">
    <div class="forgot-container">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-6 col-lg-5">
                    <div class="forgot-card">
                        <div class="forgot-header">
                            <div class="forgot-icon">
                                <i class="fas fa-key"></i>
                            </div>
                            <h3>Reset Password</h3>
                            <p>Create a new password for your account</p>
                        </div>
                        
                        <div class="forgot-body">
                            <?php echo $message; ?>

                            <form method="POST" id="forgotForm">
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-user me-2"></i>Username
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="username" id="username"
                                               placeholder="Enter your username" required>
                                        <span class="input-icon">
                                            <i class="fas fa-id-card"></i>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="form-label">
                                        <i class="fas fa-lock me-2"></i>New Password
                                    </label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="newPassword" 
                                               name="new_password" placeholder="Enter new password" required minlength="6">
                                        <span class="input-icon password-toggle" onclick="togglePassword('newPassword', 'toggleIcon1')">
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
                                               name="confirm_password" placeholder="Confirm new password" required minlength="6">
                                        <span class="input-icon password-toggle" onclick="togglePassword('confirmPassword', 'toggleIcon2')">
                                            <i class="fas fa-eye" id="toggleIcon2"></i>
                                        </span>
                                    </div>
                                    <div class="match-indicator" id="matchIndicator"></div>
                                </div>
                                
                                <button type="submit" class="btn btn-reset w-100">
                                    <i class="fas fa-redo me-2"></i>Reset Password
                                </button>
                            </form>

                            <div class="divider">
                                <span>or</span>
                            </div>

                            <div class="text-center">
                                <a href="login.php" class="back-home">
                                    <i class="fas fa-arrow-left"></i>
                                    <span>Back to Login</span>
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
        document.getElementById('newPassword').addEventListener('input', function() {
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
            const password = document.getElementById('newPassword').value;
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

        document.getElementById('newPassword').addEventListener('input', checkPasswordMatch);
        document.getElementById('confirmPassword').addEventListener('input', checkPasswordMatch);

        // Form validation
        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const password = document.getElementById('newPassword').value;
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