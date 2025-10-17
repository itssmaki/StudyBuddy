<?php
require_once 'config.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$logout_message = '';
if (isset($_GET['logout'])) {
    $logout_message = '<div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>Success!</strong> Logged out successfully! <a href="login.php" class="alert-link">Login again?</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StudyBuddy - Your Smart Learning Companion</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/studybuddy-styles.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
            z-index: 0;
        }
    </style>
</head>
<body>
    <div class="container mt-5 py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-9">
                <div class="hero-card">
                    <?php echo $logout_message; ?>
                    
                    <div class="text-center mb-5">
                        <div class="floating">
                            <h1 class="logo-text">
                                <i class="fas fa-book-reader"></i> StudyBuddy
                            </h1>
                        </div>
                        <p class="tagline">Your Smart Learning Companion</p>
                        <p class="subtitle">Organize tasks, track progress, and stay motivated with dedicated academic tools</p>
                    </div>

                    <div class="text-center mb-5">
                        <a href="login.php" class="btn btn-custom btn-login me-3">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="register.php" class="btn btn-custom btn-register">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </div>
                    
                    <hr class="divider">
                    
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="feature-card text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-list-check"></i>
                                </div>
                                <h5>Powerful Features</h5>
                                <ul class="feature-list list-unstyled">
                                    <li>Task Planner by Subject</li>
                                    <li>Smart Reminders</li>
                                    <li>Pomodoro Timer</li>
                                    <li>Progress Reports</li>
                                    <li>Motivational Quotes</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="feature-card text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-graduation-cap"></i>
                                </div>
                                <h5>For Students</h5>
                                <p class="text-muted">
                                    Perfect for high school, college, or online learners. Manage assignments, 
                                    exams, and study sessions effortlessly with our intuitive interface designed 
                                    specifically for academic success.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="feature-card text-center">
                                <div class="feature-icon">
                                    <i class="fas fa-rocket"></i>
                                </div>
                                <h5>Why StudyBuddy?</h5>
                                <p class="text-muted">
                                    Tailored specifically for academics, reducing stress with focused tools 
                                    that go beyond generic productivity apps. Experience learning optimization 
                                    built by students, for students.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>