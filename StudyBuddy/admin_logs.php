<?php
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all admin notifications, ordered by creation time
$stmt = $pdo->query("
    SELECT * FROM admin_notifications
    ORDER BY created_at DESC
");
$notifications = $stmt->fetchAll();

// Calculate statistics
$totalNotifications = count($notifications);
$unreadNotifications = count(array_filter($notifications, fn($n) => !$n['is_read']));
$readNotifications = count(array_filter($notifications, fn($n) => $n['is_read']));

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_as_read'])) {
        $notification_id = $_POST['notification_id'];
        $stmt = $pdo->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ?");
        $stmt->execute([$notification_id]);
        header("Location: admin_logs.php");
        exit;
    } elseif (isset($_POST['delete_notification'])) {
        $delete_id = $_POST['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM admin_notifications WHERE id = ?");
        $stmt->execute([$delete_id]);
        header("Location: admin_logs.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Notifications - Admin Panel</title>
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

.navbar {
    background: var(--dark-gradient) !important;
    box-shadow: var(--shadow);
    padding: 1rem 0;
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
    max-width: 1400px;
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

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card-compact {
    background: white;
    border-radius: 15px;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.5rem;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card-compact::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    transition: width 0.3s ease;
}
.navbar-brand::before {
    content: '📚';
    font-size: 1.8rem;
}
.stat-card-compact.total::before {
    background: var(--info-gradient);
}

.stat-card-compact.warning::before {
    background: var(--warning-gradient);
}

.stat-card-compact.success::before {
    background: var(--success-gradient);
}

.stat-card-compact:hover {
    transform: translateY(-5px);
    box-shadow: var(--shadow-hover);
}

.stat-card-compact:hover::before {
    width: 8px;
}

.stat-card-compact .stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    flex-shrink: 0;
}

.stat-card-compact.total .stat-icon {
    background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(37, 99, 235, 0.1) 100%);
    color: #3b82f6;
}

.stat-card-compact.warning .stat-icon {
    background: linear-gradient(135deg, rgba(251, 191, 36, 0.1) 0%, rgba(245, 158, 11, 0.1) 100%);
    color: #f59e0b;
}

.stat-card-compact.success .stat-icon {
    background: linear-gradient(135deg, rgba(72, 199, 116, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
    color: #10b981;
}

.stat-card-compact .stat-content h5 {
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
}

.stat-card-compact .stat-content h2 {
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
    color: #1f2937;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow);
}

.alert {
    border: none;
    border-radius: 15px;
    padding: 1.5rem;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.alert i {
    font-size: 1.5rem;
}

.alert-info {
    background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
    color: #1e40af;
}

.table-wrapper {
    overflow-x: auto;
    border-radius: 12px;
    box-shadow: 0 0 0 1px #e2e8f0;
}

.table {
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
}

.table thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: none;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 0.5px;
    font-weight: 600;
    padding: 1rem;
    color: #374151;
}

.table thead th:first-child {
    border-top-left-radius: 12px;
}

.table thead th:last-child {
    border-top-right-radius: 12px;
}

.table tbody tr {
    transition: all 0.3s ease;
    border-bottom: 1px solid #e2e8f0;
}

.table tbody tr:hover {
    background: rgba(102, 126, 234, 0.05);
    transform: scale(1.01);
}

.table tbody td {
    padding: 1.25rem 1rem;
    vertical-align: middle;
    color: #1f2937;
    font-size: 0.95rem;
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

.badge.bg-success {
    background: var(--success-gradient) !important;
    border: none;
}

.badge.bg-warning {
    background: var(--warning-gradient) !important;
    border: none;
    color: white;
}

.btn {
    border-radius: 10px;
    padding: 0.6rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
}

.btn-primary {
    background: var(--primary-gradient);
}

.btn-danger {
    background: var(--danger-gradient);
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

@media (max-width: 768px) {
    .page-header h2 {
        font-size: 1.5rem;
    }
    
    .stats-row {
        grid-template-columns: 1fr;
    }
    
    .stat-card-compact {
        padding: 1.25rem;
    }
    
    .stat-card-compact .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 1.5rem;
    }
    
    .stat-card-compact .stat-content h2 {
        font-size: 1.75rem;
    }
    
    .table {
        font-size: 0.875rem;
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
            <a href="admin_users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
            <a href="admin_tasks.php" class="nav-link"><i class="fas fa-tasks"></i> Tasks</a>
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
    <div class="page-header">
        <h2><i class="fas fa-bell"></i> Admin Notifications</h2>
        <p>Monitor system notifications and important alerts</p>
    </div>
    
    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-card-compact total">
            <div class="stat-icon">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="stat-content">
                <h5>Total Notifications</h5>
                <h2><?= $totalNotifications ?></h2>
            </div>
        </div>
        <div class="stat-card-compact warning">
            <div class="stat-icon">
                <i class="fas fa-envelope"></i>
            </div>
            <div class="stat-content">
                <h5>Unread</h5>
                <h2><?= $unreadNotifications ?></h2>
            </div>
        </div>
        <div class="stat-card-compact success">
            <div class="stat-icon">
                <i class="fas fa-envelope-open"></i>
            </div>
            <div class="stat-content">
                <h5>Read</h5>
                <h2><?= $readNotifications ?></h2>
            </div>
        </div>
    </div>
    
    <div class="content-card">
        <?php if (empty($notifications)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>No notifications available.</span>
            </div>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><i class="fas fa-comment-alt"></i> Message</th>
                        <th><i class="fas fa-calendar"></i> Created At</th>
                        <th><i class="fas fa-flag"></i> Status</th>
                        <th><i class="fas fa-cog"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $i => $notification): ?>
                    <tr>
                        <td><strong><?= $i + 1 ?></strong></td>
                        <td><?= htmlspecialchars($notification['message']) ?></td>
                        <td><?= date('M d, Y h:i A', strtotime($notification['created_at'])) ?></td>
                        <td>
                            <?= $notification['is_read'] 
                                ? '<span class="badge bg-success"><i class="fas fa-check"></i> Read</span>' 
                                : '<span class="badge bg-warning"><i class="fas fa-circle"></i> Unread</span>' ?>
                        </td>
                        <td>
                            <?php if (!$notification['is_read']): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                    <button type="submit" name="mark_as_read" class="btn btn-sm btn-primary">
                                        <i class="fas fa-check"></i> Mark as Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                <input type="hidden" name="delete_id" value="<?= $notification['id'] ?>">
                                <button type="submit" name="delete_notification" class="btn btn-sm btn-danger ms-2">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>