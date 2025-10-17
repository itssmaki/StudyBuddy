<?php
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Prepare user stats (task count, completed tasks, total study time)
$userStats = [];
foreach ($users as $user) {
    // Count all tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $taskCount = $stmt->fetchColumn();

    // Count completed tasks
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id = ? AND status = 'completed'");
    $stmt->execute([$user['id']]);
    $completedCount = $stmt->fetchColumn();

    // Sum all study session durations in minutes (kept for potential future use)
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0)
        FROM study_sessions WHERE user_id = ?
    ");
    $stmt->execute([$user['id']]);
    $totalTime = $stmt->fetchColumn();  // Still calculated, but not used in display

    $userStats[] = [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'created_at' => $user['created_at'],
        'tasks' => $taskCount,
        'completed' => $completedCount,
        'time' => $totalTime  // Kept in array, but not displayed
    ];
}

// Overall platform statistics
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalTasks = $pdo->query("SELECT COUNT(*) FROM tasks")->fetchColumn();
$completedTasks = $pdo->query("SELECT COUNT(*) FROM tasks WHERE status='completed'")->fetchColumn();

// Total study time in minutes across all users (still calculated, but not used)
$totalStudyTime = $pdo->query("
    SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)), 0) FROM study_sessions
")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard - StudyBuddy</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard-style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-user-shield"></i> StudyBuddy Admin
        </a>
        <div class="navbar-nav ms-auto">
            <a href="admin_users.php" class="nav-link"><i class="fas fa-users"></i> Users</a>
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

<div class="container mt-4">
    <div class="page-header fade-in">
        <h2><i class="fas fa-chart-line"></i> Admin Dashboard</h2>
        <p>Monitor platform statistics and user activity</p>
    </div>

    <!-- Stats Overview -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 fade-in">
            <div class="stat-card success">
                <i class="fas fa-users icon"></i>
                <h5>Total Users</h5>
                <h2><?= $totalUsers ?></h2>
            </div>
        </div>
        <div class="col-md-4 mb-3 fade-in">
            <div class="stat-card warning">
                <i class="fas fa-check-circle icon"></i>
                <h5>Completed Tasks</h5>
                <h2><?= $completedTasks ?></h2>
                <p class="mb-0 text-muted">
                    <?= $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 1) : 0 ?>% Complete
                </p>
            </div>
        </div>
        <div class="col-md-4 mb-3 fade-in">
            <div class="stat-card info">
                <i class="fas fa-tasks icon"></i>
                <h5>Total Tasks</h5>
                <h2><?= $totalTasks ?></h2>
            </div>
        </div>
    </div>

    <!-- User Overview Table -->
    <div class="card fade-in mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5><i class="fas fa-users"></i> User Overview</h5>
            <input type="text" id="searchInput" class="form-control w-25" placeholder="🔍 Search users...">
        </div>
        <div class="card-body">
            <?php if (empty($userStats)): ?>
                <div class="empty-state">
                    <i class="fas fa-users-slash"></i>
                    <h4>No registered users found</h4>
                    <p class="text-muted">Users will appear here once they register.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle" id="userTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Registered</th>
                                <th>Total Tasks</th>
                                <th>Completed</th>
                                <!-- Removed Study Time (min) column -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($userStats as $i => $u): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                                <td><?= htmlspecialchars($u['email']) ?></td>
                                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                                <td><span class="badge bg-info"><?= $u['tasks'] ?></span></td>
                                <td><span class="badge bg-success"><?= $u['completed'] ?></span></td>
                                <!-- Removed Study Time (min) cell -->
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6 mb-4 fade-in">
            <div class="chart-container">
                <h5><i class="fas fa-chart-bar"></i> Platform Overview</h5>
                <canvas id="taskChart"></canvas>
            </div>
        </div>
        <div class="col-md-6 mb-4 fade-in">
            <div class="chart-container">
                <h5><i class="fas fa-chart-pie"></i> Task Completion</h5>
                <canvas id="completionChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
// Search filter
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#userTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
    });
});

// Bar Chart (Removed 'Study Hours' label and data point)
new Chart(document.getElementById('taskChart'), {
    type: 'bar',
    data: {
        labels: ['Users', 'Tasks', 'Completed Tasks'],  // Removed 'Study Hours'
        datasets: [{
            label: 'StudyBuddy Stats',
            data: [<?= $totalUsers ?>, <?= $totalTasks ?>, <?= $completedTasks ?>],  // Removed the fourth data point
            backgroundColor: [
                'rgba(102,126,234,0.8)',  // Users
                'rgba(251,191,36,0.8)',   // Tasks
                'rgba(16,185,129,0.8)'    // Completed Tasks
            ],
            borderWidth: 2,
            borderRadius: 12
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    }
});

// Pie Chart (Unchanged)
new Chart(document.getElementById('completionChart'), {
    type: 'doughnut',
    data: {
        labels: ['Completed', 'Pending'],
        datasets: [{
            data: [<?= $completedTasks ?>, <?= $totalTasks - $completedTasks ?>],
            backgroundColor: ['#10b981', '#ef4444'],
            hoverOffset: 10
        }]
    },
    options: {
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 14 } } }
        }
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
