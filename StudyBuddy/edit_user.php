<?php
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: admin_users.php?error=Invalid user ID");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT id, username, email, role, is_active FROM users WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: admin_users.php?error=User not found");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    $stmt->execute([$is_active, $id]);
    $message = "User status updated successfully";
    header("Location: admin_users.php?message=" . urlencode($message));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit User Status - Admin Panel</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #48c774 0%, #10b981 100%);
    --warning-gradient: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
    --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    --dark-gradient: linear-gradient(135deg, #1f2937 0%, #111827 100%);
    --shadow: 0 10px 30px rgba(0,0,0,0.1);
    --shadow-hover: 0 15px 40px rgba(0,0,0,0.15);
}

body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Navbar Styles */
.navbar {
    background: var(--dark-gradient) !important;
    box-shadow: var(--shadow);
    padding: 1rem 0;
}

.navbar.navbar-dark.bg-dark {
    background: var(--dark-gradient) !important;
}

.navbar-brand {
    font-weight: 700;
    font-size: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: transform 0.3s ease;
}

.navbar-brand:hover {
    transform: scale(1.05);
}

.nav-link {
    font-weight: 500;
    transition: all 0.3s ease;
    margin: 0 0.3rem;
    border-radius: 8px;
    padding: 0.5rem 1rem !important;
}

.nav-link:hover {
    background: rgba(255,255,255,0.2);
    transform: translateY(-2px);
}

.nav-link.active {
    background: rgba(255,255,255,0.3);
}

.main-container {
    max-width: 800px;
    margin: 0 auto;
    padding: 2.5rem 1.5rem;
}

.page-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow);
    position: relative;
    overflow: hidden;
}

.page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: var(--primary-gradient);
}

.page-header h2 {
    margin: 0;
    color: #1f2937;
    font-weight: 700;
    font-size: 2rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.page-header h2 i {
    background: var(--primary-gradient);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.page-header p {
    margin: 0.5rem 0 0 0;
    color: #6b7280;
    font-size: 1rem;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2.5rem;
    box-shadow: var(--shadow);
}

.user-profile-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    margin-bottom: 2rem;
}

.user-profile-avatar {
    width: 80px;
    height: 80px;
    border-radius: 20px;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 2rem;
    flex-shrink: 0;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.user-profile-info h3 {
    margin: 0;
    color: #1f2937;
    font-weight: 700;
    font-size: 1.5rem;
}

.user-profile-info p {
    margin: 0.25rem 0 0 0;
    color: #6b7280;
    font-size: 0.95rem;
}

.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.form-label i {
    color: #667eea;
}

.form-control {
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    padding: 0.875rem 1.25rem;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #f9fafb;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
}

.form-control[readonly] {
    background: #f3f4f6;
    color: #6b7280;
    cursor: not-allowed;
}

.status-toggle-wrapper {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 15px;
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.status-toggle-wrapper h5 {
    margin: 0 0 1rem 0;
    color: #1f2937;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.status-toggle-wrapper h5 i {
    color: #667eea;
}

.custom-switch {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: white;
    border-radius: 12px;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
}

.custom-switch:hover {
    border-color: #667eea;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
}

.custom-switch input[type="checkbox"] {
    width: 60px;
    height: 32px;
    cursor: pointer;
    appearance: none;
    background: #e5e7eb;
    border-radius: 50px;
    position: relative;
    transition: all 0.3s ease;
    flex-shrink: 0;
}

.custom-switch input[type="checkbox"]::before {
    content: '';
    position: absolute;
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: white;
    top: 3px;
    left: 3px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.custom-switch input[type="checkbox"]:checked {
    background: var(--success-gradient);
}

.custom-switch input[type="checkbox"]:checked::before {
    left: 31px;
}

.custom-switch label {
    margin: 0;
    font-weight: 600;
    color: #374151;
    cursor: pointer;
    user-select: none;
    flex: 1;
}

.custom-switch .status-indicator {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.custom-switch input[type="checkbox"]:checked ~ .status-indicator {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.custom-switch input[type="checkbox"]:not(:checked) ~ .status-indicator {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.btn {
    border-radius: 12px;
    padding: 0.875rem 2rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    font-size: 1rem;
}

.btn-primary {
    background: var(--primary-gradient);
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-secondary:hover {
    background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.button-group {
    display: flex;
    gap: 1rem;
    margin-top: 2rem;
}
.navbar-brand::before {
    content: '📚';
    font-size: 1.8rem;
}
.alert {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
    animation: slideDown 0.5s ease;
    margin-bottom: 2rem;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.alert i {
    font-size: 1.5rem;
}

.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.info-grid {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

@media (max-width: 768px) {
    .page-header h2 {
        font-size: 1.5rem;
    }
    
    .user-profile-header {
        flex-direction: column;
        text-align: center;
    }
    
    .button-group {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .content-card {
        padding: 1.5rem;
    }
}
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-user-shield"></i> StudyBuddy Admin
        </a>
        <div class="navbar-nav ms-auto">
            <a href="admin_dashboard.php" class="nav-link"><i class="fas fa-users"></i> Dashboard</a>
            <a href="admin_tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a>
            <a href="admin_logs.php" class="nav-link"><i class="fas fa-file-alt"></i> Logs</a>
            <span class="navbar-text text-white ms-3">
                Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>
            </span>
            <a href="logout.php" class="btn btn-outline-light btn-sm ms-2">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="main-container">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            <span><?= htmlspecialchars($error) ?></span>
        </div>
    <?php endif; ?>
    
    <div class="page-header">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h2><i class="fas fa-user-edit"></i> Edit User Status</h2>
                <p>Manage user account status and view user information</p>
            </div>
            <a href="admin_users.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Users
            </a>
        </div>
    </div>
    
    <div class="content-card">
        <div class="user-profile-header">
            <div class="user-profile-avatar">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
            </div>
            <div class="user-profile-info">
                <h3><?= htmlspecialchars($user['username']) ?></h3>
                <p><i class="fas fa-envelope"></i> <?= htmlspecialchars($user['email']) ?></p>
            </div>
        </div>
        
        <form method="POST">
            <div class="info-grid">
                <div class="mb-3">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="text" class="form-control" id="email" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">
                        <i class="fas fa-shield-alt"></i> User Role
                    </label>
                    <input type="text" class="form-control" id="role" value="<?= htmlspecialchars($user['role'] ?? 'user') ?>" readonly>
                </div>
            </div>
            
            <div class="status-toggle-wrapper">
                <h5><i class="fas fa-toggle-on"></i> Account Status</h5>
                <div class="custom-switch">
                    <input type="checkbox" id="is_active" name="is_active" <?= $user['is_active'] ? 'checked' : '' ?>>
                    <label for="is_active">Enable user account access</label>
                    <span class="status-indicator">
                        <i class="fas fa-circle"></i>
                        <span id="statusText"><?= $user['is_active'] ? 'Active' : 'Inactive' ?></span>
                    </span>
                </div>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Update Status
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkbox = document.getElementById('is_active');
    const statusText = document.getElementById('statusText');
    
    checkbox.addEventListener('change', function() {
        statusText.textContent = this.checked ? 'Active' : 'Inactive';
    });
});
</script>
</body>
</html>