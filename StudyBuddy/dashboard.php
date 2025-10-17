<?php
require_once 'config.php';
date_default_timezone_set('Asia/Manila'); // set this to your local timezone
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch subjects
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ?");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

// Fetch tasks (with join to subjects)
$stmt = $pdo->prepare("SELECT t.*, s.name as subject_name FROM tasks t JOIN subjects s ON t.subject_id = s.id WHERE t.user_id = ? ORDER BY t.deadline ASC");
$stmt->execute([$user_id]);
$tasks = $stmt->fetchAll();

// Progress stats
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM tasks WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_tasks = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as completed FROM tasks WHERE user_id = ? AND status = 'completed'");
$stmt->execute([$user_id]);
$completed_tasks = $stmt->fetch()['completed'];

$stmt = $pdo->prepare("SELECT SUM(duration) as total_minutes FROM study_sessions WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_minutes = $stmt->fetch()['total_minutes'] ?? 0;

// Random quote
$stmt = $pdo->query("SELECT * FROM quotes WHERE is_active = 1 ORDER BY RAND() LIMIT 1");
$quote = $stmt->fetch();

// Handle task completion
if ($_POST && isset($_POST['complete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    header("Location: dashboard.php?success=Task completed!");
    exit;
}
// Handle uncomplete task
if ($_POST && isset($_POST['uncomplete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $pdo->prepare("UPDATE tasks SET status = 'pending' WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    header("Location: dashboard.php?success=Task marked as pending again!");
    exit;
}
// Handle task deletion
if ($_POST && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];
    $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND user_id = ?");
    $stmt->execute([$task_id, $user_id]);
    header("Location: dashboard.php?success=Task deleted successfully!");
    exit;
}
// Handle task important
if (isset($_POST['toggle_important'])) {
    $taskId = $_POST['task_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus == 1) ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE tasks SET is_important = ? WHERE id = ?");
    $stmt->execute([$newStatus, $taskId]);

    header("Location: dashboard.php?success=Task set to important successfully!");
    exit;
}
if (isset($_POST['toggle_important'])) {
    $taskId = $_POST['task_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus == 1) ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE tasks SET is_important = ? WHERE id = ?");
    $stmt->execute([$newStatus, $taskId]);

    header("Location: dashboard.php?success=Task set to important successfully!");
    exit;
}
if (isset($_POST['toggle_unimportant'])) {
    $taskId = $_POST['task_id'];
    $currentStatus = $_POST['current_status'];
    $newStatus = ($currentStatus == 1) ? 0 : 1;
    $stmt = $pdo->prepare("UPDATE tasks SET is_important = ? WHERE id = ?");
    $stmt->execute([$newStatus, $taskId]);

    header("Location: dashboard.php?success=Task unmark as important successfully!");
    exit;
}
// Upcoming tasks (next 24 hours)
$upcoming_tasks = array_filter($tasks, function($task) {
    $deadline_ts = strtotime($task['deadline']);
    $now_ts = time();
    $diff_hours = ($deadline_ts - $now_ts) / 3600;
    return $diff_hours > 0 && $diff_hours <= 24 && $task['status'] == 'pending';
});

// Overdue tasks
$overdue_tasks = array_filter($tasks, function($task) {
    return strtotime($task['deadline']) < time() && $task['status'] !== 'completed';
});

$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>StudyBuddy Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #48c774 0%, #10b981 100%);
        --warning-gradient: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        --info-gradient: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        --danger-gradient: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        --shadow: 0 10px 30px rgba(0,0,0,0.1);
        --shadow-hover: 0 15px 40px rgba(0,0,0,0.15);
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        min-height: 100vh;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
        background: var(--primary-gradient) !important;
        box-shadow: var(--shadow);
        padding: 1rem 0;
    }

    .navbar-brand {
        font-weight: 700;
        font-size: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .navbar-brand::before {
        content: '📚';
        font-size: 1.8rem;
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

    .card {
        border: none;
        border-radius: 20px;
        box-shadow: var(--shadow);
        transition: all 0.3s ease;
        overflow: hidden;
    }

    .card:hover {
        box-shadow: var(--shadow-hover);
        transform: translateY(-5px);
    }

    .stat-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        text-align: center;
        position: relative;
        overflow: hidden;
        transition: all 0.3s ease;
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        opacity: 0.1;
        transition: opacity 0.3s ease;
    }

    .stat-card:hover::before {
        opacity: 0.15;
    }

    .stat-card.success::before {
        background: var(--success-gradient);
    }

    .stat-card.info::before {
        background: var(--info-gradient);
    }

    .stat-card.warning::before {
        background: var(--warning-gradient);
    }

    .stat-card h5 {
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 1rem;
    }

    .stat-card h2 {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 0;
        background: var(--primary-gradient);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .stat-card .icon {
        position: absolute;
        top: 1rem;
        right: 1rem;
        font-size: 3rem;
        opacity: 0.1;
    }

    .quote-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 20px;
        padding: 2.5rem;
        position: relative;
        overflow: hidden;
    }

    .quote-card::before {
        content: '"';
        position: absolute;
        top: -20px;
        left: 20px;
        font-size: 120px;
        opacity: 0.1;
        font-family: Georgia, serif;
    }

    .quote-card .lead {
        font-size: 1.3rem;
        font-style: italic;
        line-height: 1.8;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }

    .quote-card .blockquote-footer {
        color: rgba(255,255,255,0.9);
        font-size: 1rem;
    }

    .alert {
        border: none;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    }

    .alert-warning {
        background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        color: #92400e;
    }

    .upcoming-deadline-item {
        padding: 0.8rem;
        background: rgba(255,255,255,0.5);
        border-radius: 10px;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .upcoming-deadline-item::before {
        content: '⏰';
        font-size: 1.2rem;
    }

    .table {
        margin: 0;
    }

    .table thead th {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: none;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 0.5px;
        font-weight: 600;
        padding: 1rem;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: rgba(102, 126, 234, 0.05);
        transform: scale(1.01);
    }

    .btn {
        border-radius: 10px;
        padding: 0.5rem 1.2rem;
        font-weight: 600;
        transition: all 0.3s ease;
        border: none;
    }

    .btn-primary {
        background: var(--primary-gradient);
    }

    .btn-success {
        background: var(--success-gradient);
    }

    .btn-warning {
        background: var(--warning-gradient);
        color: white;
    }

    .btn-info {
        background: var(--info-gradient);
    }

    .btn-danger {
        background: var(--danger-gradient);
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .badge {
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-weight: 600;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
    }

    .empty-state i {
        font-size: 5rem;
        color: #cbd5e1;
        margin-bottom: 1.5rem;
    }

    .empty-state h4 {
        color: #64748b;
        margin-bottom: 1rem;
    }

    .pomodoro-notification {
        position: fixed;
        top: 80px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        min-width: 400px;
        animation: slideDown 0.5s ease;
    }
    /* highlight important tasks */
    .important-task {
        background: rgba(255, 230, 128, 0.4);
        box-shadow: 0 0 8px rgba(255, 215, 0, 0.6);
    }
    /* highlight important tasks */
    .important-task {
        background: rgba(255, 230, 128, 0.4);
        box-shadow: 0 0 8px rgba(255, 215, 0, 0.6);
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateX(-50%) translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-bottom: none;
        padding: 1.5rem;
    }

    .card-header h5 {
        margin: 0;
        font-weight: 700;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .stat-card h2 {
            font-size: 2rem;
        }
        
        .pomodoro-notification {
            min-width: 90%;
        }
    }
</style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="add_task.php"><i class="fas fa-plus-circle"></i> Add Task</a>
                <a class="nav-link" href="subjects.php"><i class="fas fa-book"></i> Subjects</a>
                <a class="nav-link" href="timer.php"><i class="fas fa-clock"></i> Timer</a>
                <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
</nav>

<div class="container mt-4">

<?php if ($success_msg): ?>
<div class="alert alert-success alert-dismissible fade show fade-in" role="alert">
    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?php if ($error_msg): ?>
<div class="alert alert-danger alert-dismissible fade show fade-in" role="alert">
    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_msg); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Overdue Alerts -->
<?php if (!empty($overdue_tasks)): ?>
<div class="alert alert-danger fade-in" role="alert">
    <h6><i class="fas fa-exclamation-circle"></i> Overdue Tasks (<?php echo count($overdue_tasks); ?>)</h6>
    <ul class="mb-0">
        <?php foreach ($overdue_tasks as $t): ?>
        <li><?php echo htmlspecialchars($t['title']); ?> — Due <?php echo date('M d, Y h:i A', strtotime($t['deadline'])); ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<!-- Upcoming Deadlines -->
<?php if (!empty($upcoming_tasks)): ?>
<div class="alert alert-warning fade-in" role="alert">
    <h6><i class="fas fa-exclamation-triangle"></i> Upcoming Deadlines (<?php echo count($upcoming_tasks); ?>)</h6>
    <div class="mt-2">
        <?php foreach (array_slice($upcoming_tasks,0,3) as $task): ?>
        <div class="upcoming-deadline-item">
            <strong><?php echo htmlspecialchars($task['title']); ?></strong> — Due: <?php echo date('M d, Y h:i A', strtotime($task['deadline'])); ?>
            <span class="badge bg-dark"><?php echo htmlspecialchars($task['subject_name']); ?></span>
        </div>
        <?php endforeach; ?>
    </div>
    <a href="notifications.php" class="btn btn-sm btn-warning mt-2">
        <i class="fas fa-eye"></i> View All Notifications
    </a>
</div>
<?php endif; ?>

<!-- Motivational Quote -->
<div class="quote-card mb-4 fade-in">
    <blockquote class="blockquote mb-0">
        <p class="lead"><?php echo htmlspecialchars($quote['quote']); ?></p>
        <footer class="blockquote-footer mt-3">— <?php echo htmlspecialchars($quote['author']); ?></footer>
    </blockquote>
</div>

<!-- Progress Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3 fade-in">
        <div class="stat-card success">
            <i class="fas fa-tasks icon"></i>
            <h5>Total Tasks</h5>
            <h2><?php echo $total_tasks; ?></h2>
        </div>
    </div>
    <div class="col-md-4 mb-3 fade-in">
        <div class="stat-card info">
            <i class="fas fa-check-circle icon"></i>
            <h5>Completed</h5>
            <h2><?php echo $completed_tasks; ?></h2>
            <p class="mb-0 text-muted"><?php echo $total_tasks>0?round(($completed_tasks/$total_tasks)*100,1):0; ?>% Complete</p>
        </div>
    </div>
    <?php
    $stmt = $pdo->prepare("SELECT COUNT(*) as important FROM tasks WHERE user_id = ? AND is_important = 1");
    $stmt->execute([$user_id]);
    $important_tasks = $stmt->fetch()['important'];
    ?>
    <div class="col-md-4 mb-3 fade-in">
        <div class="stat-card warning">
            <i class="fas fa-star icon"></i>
            <h5>Important Tasks</h5>
            <h2><?php echo $important_tasks; ?></h2>
        </div>
    </div>
</div>

<!-- Task Table -->
<div class="card fade-in">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5><i class="fas fa-list-check"></i> Task Planner</h5>
        <a href="add_task.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Task</a>
    </div>
    <div class="card-body">
        <?php if (empty($tasks)): ?>
        <div class="empty-state">
            <i class="fas fa-clipboard-list"></i>
            <h4>No tasks yet!</h4>
            <a href="subjects.php" class="btn btn-outline-primary me-2"><i class="fas fa-book"></i> Add Subject</a>
            <a href="add_task.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add Task</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($tasks as $task):
                    $dueDate = new DateTime($task['deadline']);
                    $now = new DateTime();
                    $isOverdue = $dueDate < $now && $task['status'] !== 'completed';
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars($task['subject_name']); ?></span></td>
                    <td class="task-deadline" data-deadline="<?php echo $task['deadline']; ?>">
                        <span class="<?php echo $isOverdue?'text-danger fw-bold':''; ?>">
                            <?php echo $dueDate->format('M d, Y h:i A'); ?>
                            <?php if($isOverdue) echo ' <small>(Overdue)</small>'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if($task['status']=='completed'): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i> Completed</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half"></i> Pending</span>
                        <?php endif; ?>
                    </td>
                    <td>
                            <!-- View -->
                            <a href="view_task.php?id=<?php echo $task['id']; ?>" 
                            class="btn btn-sm btn-action btn-info" 
                            title="View Task">
                                <i class="fas fa-eye"></i>
                            </a>

                            <!-- Edit -->
                            <a href="edit_task.php?id=<?php echo $task['id']; ?>" 
                            class="btn btn-sm btn-action btn-primary" 
                            title="Edit Task">
                                <i class="fas fa-edit"></i>
                            </a>

                            <!-- Important Toggle -->
                            <?php if(!$task['is_important']): ?>
                                <!-- Mark as Important -->
                                <form method="POST" style="display:inline;" onsubmit="return confirmImportant('Mark this task as important?');">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $task['is_important']; ?>">
                                    <button type="submit" name="toggle_important" 
                                            class="btn btn-sm btn-action btn-warning btn-outline-warning" 
                                            title="Mark as Important">
                                        <i class="far fa-star"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <!-- Unmark as Important -->
                                <form method="POST" style="display:inline;" onsubmit="return confirmUnimportant('Remove this task from important?');">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $task['is_important']; ?>">
                                    <button type="submit" name="toggle_unimportant" 
                                            class="btn btn-sm btn-action btn-warning text-white" 
                                            title="Unmark as Important">
                                        <i class="fas fa-star"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                        <?php if($task['status']=='pending'): ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirmComplete();">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" name="complete_task" class="btn btn-sm btn-action btn-success"><i class="fas fa-check"></i></button>
                        </form>
                        <?php else: ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirmRedo();">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" name="uncomplete_task" class="btn btn-sm btn-action btn-secondary"><i class="fas fa-undo"></i></button>
                        </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this task?');">
                            <input type="hidden" name="delete_task" value="1">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    const state = JSON.parse(localStorage.getItem('pomodoroState') || '{}');

    if (state && state.startTime && state.duration && !isNaN(state.startTime) && !isNaN(state.duration)) {
        const elapsed = Math.floor((Date.now() - state.startTime) / 1000);
        const remaining = state.duration - elapsed;
        const minsLeft = Math.max(0, Math.floor(remaining / 60));

        if (remaining > 0) {
            const sessionType = state.isFocus ? 'Focus' : 'Break';
            const notifDiv = document.createElement('div');
            notifDiv.className = 'alert alert-info alert-dismissible fade show pomodoro-notification';
            notifDiv.innerHTML = `
                <h6><i class="fas fa-tomato"></i> Active Pomodoro Session</h6>
                <p class="mb-0">
                    Your <strong>${sessionType}</strong> session is running — 
                    <strong>${minsLeft} minutes</strong> remaining.
                </p>
                <a href="timer.php" class="btn btn-sm btn-primary mt-2">
                    <i class="fas fa-clock"></i> Go to Timer
                </a>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notifDiv);
        } else {
            localStorage.removeItem('pomodoroState'); 
        }
    }

    // Add animation delays to cards
    document.querySelectorAll('.fade-in').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const upcomingTasks = <?php echo json_encode($upcoming_tasks); ?>;

    if (!("Notification" in window)) return;

    Notification.requestPermission().then(permission => {
        if (permission !== "granted") return;

        const now = new Date();
        upcomingTasks.forEach(task => {
            const deadline = new Date(task.deadline);
            const diffMs = deadline - now;
            const oneHourMs = 3600000;

            if(diffMs <= 0) return; // already past

            // Notify immediately if less than 1 hour left
            if(diffMs <= oneHourMs) {
                new Notification("Upcoming Task!", {
                    body: `Task "${task.title}" is due soon!`,
                    icon: "https://cdn-icons-png.flaticon.com/512/2991/2991148.png"
                });
            } else {
                // Notify 1 hour before deadline
                setTimeout(() => {
                    new Notification("Upcoming Task!", {
                        body: `Task "${task.title}" is due in 1 hour.`,
                        icon: "https://cdn-icons-png.flaticon.com/512/2991/2991148.png"
                    });
                }, diffMs - oneHourMs);
            }
        });
    });
});
</script>

<script>
function updateOverdueTasks() {
    const now = new Date();
    document.querySelectorAll('.task-deadline').forEach(el => {
        const deadlineStr = el.getAttribute('data-deadline');
        const deadline = new Date(deadlineStr);
        const span = el.querySelector('span');

        if (deadline < now && !span.classList.contains('text-danger')) {
            span.classList.add('text-danger','fw-bold');
            span.innerHTML = span.innerHTML + ' <small>(Overdue)</small>';
        }
    });
}

// Initial check
updateOverdueTasks();
// Repeat every minute
setInterval(updateOverdueTasks, 60000);
</script>
<script>
    function confirmImportant() {
    return confirm("Mark this task as important?");
    }
    function confirmUnimportant() {
        return confirm("Remove this task from important?");
    }
    function confirmComplete() {
        return confirm("Are you sure you want to mark this task as completed?");
    }

    function confirmRedo() {
        return confirm("Are you sure you want to mark this task as pending again?");
    }
</script>
</body>
</html>
