<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch overdue tasks (past deadline, pending)
$stmt = $pdo->prepare("
    SELECT t.*, s.name as subject_name 
    FROM tasks t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE t.user_id = ? AND t.status = 'pending' AND t.deadline < CURDATE() 
    ORDER BY t.deadline ASC
");
$stmt->execute([$user_id]);
$overdue_tasks = $stmt->fetchAll();

// Fetch upcoming tasks (due within 7 days, pending)
$stmt = $pdo->prepare("
    SELECT t.*, s.name as subject_name 
    FROM tasks t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE t.user_id = ? AND t.status = 'pending' AND t.deadline >= CURDATE() AND t.deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ORDER BY t.deadline ASC
");
$stmt->execute([$user_id]);
$upcoming_tasks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - StudyBuddy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/dashboard-style.css">
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

        .page-header {
            margin: 2rem 0;
        }

        .page-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: #64748b;
            font-size: 1.1rem;
        }

        /* Notification Enable Section */
        .notif-enable-section {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            text-align: center;
            border: 2px solid rgba(102, 126, 234, 0.2);
            transition: all 0.3s ease;
        }

        .notif-enable-section:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .notif-icon {
            font-size: 4rem;
            margin-bottom: 1.5rem;
        }

        .btn-enable-notif {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2.5rem;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: 50px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
            transition: all 0.3s ease;
        }

        .btn-enable-notif:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(102, 126, 234, 0.4);
        }

        .btn-enable-notif.btn-success {
            background: var(--success-gradient);
        }

        /* Notification Cards */
        .notification-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .notification-card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 2px solid rgba(0, 0, 0, 0.05);
        }

        .section-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            box-shadow: var(--shadow);
        }

        .icon-overdue {
            background: var(--danger-gradient);
        }

        .icon-upcoming {
            background: var(--warning-gradient);
        }

        .icon-tips {
            background: var(--info-gradient);
        }

        .section-header h5 {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            flex-grow: 1;
        }

        .badge-danger-custom,
        .badge-warning-custom {
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .badge-danger-custom {
            background: linear-gradient(135deg, #fecaca, #fca5a5);
            color: #991b1b;
        }

        .badge-warning-custom {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            color: #92400e;
        }

        /* Task Items */
        .overdue-item,
        .task-item {
            background: #f8fafc;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            border-left: 4px solid transparent;
            transition: all 0.3s ease;
        }

        .overdue-item {
            border-left-color: #ef4444;
        }

        .task-item {
            border-left-color: #f59e0b;
        }

        .overdue-item:hover,
        .task-item:hover {
            transform: translateX(8px);
            box-shadow: var(--shadow);
        }

        .overdue-title,
        .task-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.8rem;
        }

        .task-meta {
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: #64748b;
        }

        .task-meta span {
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .task-meta i {
            color: #667eea;
        }

        .task-description {
            margin-top: 1rem;
            padding: 1rem;
            background: rgba(102, 126, 234, 0.05);
            border-radius: 12px;
            color: #475569;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .badge-days {
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 700;
            font-size: 0.85rem;
            white-space: nowrap;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        /* Buttons */
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

        .btn-action {
            padding: 0.9rem 2rem;
            font-weight: 700;
            border-radius: 50px;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
        }

        .empty-state i {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 1.5rem;
        }

        .empty-state h5 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
            color: #64748b;
        }

        /* Tips Section */
        .tips-section {
            margin-top: 1rem;
        }

        .tips-list {
            list-style: none;
            padding: 0;
        }

        .tips-list li {
            padding: 1.2rem 1.5rem;
            margin-bottom: 1rem;
            background: #f8fafc;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            transition: all 0.3s ease;
            border-left: 3px solid #8b5cf6;
        }

        .tips-list li:hover {
            transform: translateX(8px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .tips-list li i {
            color: #10b981;
            font-size: 1.3rem;
            margin-top: 0.2rem;
            flex-shrink: 0;
        }

        .tips-list li span {
            color: #475569;
            line-height: 1.6;
            font-size: 1rem;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-header h2 {
                font-size: 2rem;
            }

            .notification-card {
                padding: 1.5rem;
            }

            .section-header {
                flex-wrap: wrap;
            }

            .task-meta {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-action {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">StudyBuddy</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav ms-auto">
                    <a class="nav-link" href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    <a class="nav-link" href="subjects.php"><i class="fas fa-book"></i> Subjects</a>
                    <a class="nav-link" href="timer.php"><i class="fas fa-clock"></i> Timer</a>
                    <a class="nav-link active" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="page-header">
            <h2><i class="fas fa-bell"></i> Smart Notifications</h2>
            <p>Stay on top of your deadlines with intelligent reminders</p>
        </div>

        <!-- Enable Browser Notifications -->
        <div class="notif-enable-section">
            <div class="notif-icon">
                <i class="fas fa-bell-concierge"></i>
            </div>
            <button id="enable-notifs" class="btn btn-enable-notif">
                <i class="fas fa-bell me-2"></i> Enable Browser Notifications
            </button>
            <p class="text-muted mt-3 mb-0">
                <i class="fas fa-info-circle me-1"></i>
                Get real-time alerts for deadlines, even when the app is in the background
            </p>
        </div>

        <!-- Overdue Tasks -->
        <?php if (!empty($overdue_tasks)): ?>
            <div class="notification-card">
                <div class="section-header">
                    <div class="section-icon icon-overdue">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Overdue Tasks</h5>
                    <span class="badge badge-danger-custom ms-auto"><?php echo count($overdue_tasks); ?> Task<?php echo count($overdue_tasks) != 1 ? 's' : ''; ?></span>
                </div>
                
                <?php foreach ($overdue_tasks as $task): ?>
                    <div class="overdue-item">
                        <div class="overdue-title"><?php echo htmlspecialchars($task['title']); ?></div>
                        <div class="task-meta mt-2">
                            <span><i class="fas fa-book me-1"></i><?php echo htmlspecialchars($task['subject_name']); ?></span>
                            <span><i class="fas fa-calendar-times me-1"></i>Due: <?php echo $task['deadline']; ?></span>
                        </div>
                        <?php if (!empty($task['description'])): ?>
                            <div class="task-description">
                                <i class="fas fa-align-left me-2"></i><?php echo htmlspecialchars($task['description']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                
                <div class="text-center mt-3">
                    <a href="dashboard.php" class="btn btn-danger btn-action">
                        <i class="fas fa-tasks me-2"></i>Complete These Tasks
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Upcoming Tasks -->
        <?php if (!empty($upcoming_tasks)): ?>
            <div class="notification-card">
                <div class="section-header">
                    <div class="section-icon icon-upcoming">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h5>Upcoming Tasks</h5>
                    <span class="badge badge-warning-custom ms-auto"><?php echo count($upcoming_tasks); ?> Task<?php echo count($upcoming_tasks) != 1 ? 's' : ''; ?></span>
                </div>
                
                <?php foreach ($upcoming_tasks as $task): ?>
                    <?php 
                    $deadline = new DateTime($task['deadline']);
                    $now = new DateTime();
                    $days = $deadline->diff($now)->days;
                    $badge_class = $days <= 1 ? 'badge-danger-custom' : 'badge-warning-custom';
                    ?>
                    <div class="task-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="task-title"><?php echo htmlspecialchars($task['title']); ?></div>
                                <div class="task-meta">
                                    <span><i class="fas fa-book me-1"></i><?php echo htmlspecialchars($task['subject_name']); ?></span>
                                    <span><i class="fas fa-calendar-alt me-1"></i><?php echo $task['deadline']; ?></span>
                                </div>
                                <?php if (!empty($task['description'])): ?>
                                    <div class="task-description">
                                        <i class="fas fa-align-left me-2"></i><?php echo htmlspecialchars($task['description']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="badge-days <?php echo $badge_class; ?>">
                                <?php echo $days; ?> day<?php echo $days != 1 ? 's' : ''; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="notification-card">
                <div class="empty-state">
                    <i class="fas fa-check-circle"></i>
                    <h5>All Clear!</h5>
                    <p class="text-muted mb-4">You're all caught up. Great job staying organized!</p>
                    <a href="add_task.php" class="btn btn-primary btn-action">
                        </i>Add a New Task
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tips Section -->
        <div class="notification-card">
            <div class="section-header">
                <div class="section-icon icon-tips">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h5>Productivity Tips</h5>
            </div>
            <div class="tips-section">
                <ul class="tips-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Set reminders for tasks due in 1-2 days to avoid last-minute stress</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Use the Pomodoro timer for focused study sessions on these tasks</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Mark tasks as completed on the dashboard to track your progress</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <span>Break large tasks into smaller chunks for better time management</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const notifButton = document.getElementById('enable-notifs');

        // Load saved preference from localStorage
        let notifEnabled = localStorage.getItem('studybuddy_notifications') === 'enabled';

        // Update button state on load
        function updateButtonState() {
            if (notifEnabled) {
                notifButton.textContent = 'Notifications Enabled ✓';
                notifButton.classList.remove('btn-outline-primary');
                notifButton.classList.add('btn-success');
            } else {
                notifButton.textContent = 'Enable Browser Notifications';
                notifButton.classList.remove('btn-success');
                notifButton.classList.add('btn-outline-primary');
            }
        }
        updateButtonState();

        // Toggle Notifications
        notifButton?.addEventListener('click', function() {
            if (!('Notification' in window)) {
                alert('Your browser does not support notifications.');
                return;
            }

            if (!notifEnabled) {
                // Enable notifications
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        notifEnabled = true;
                        localStorage.setItem('studybuddy_notifications', 'enabled');
                        updateButtonState();
                        alert('Notifications enabled. You can receive reminders.');

                        // Demo notification
                        <?php if (!empty($upcoming_tasks)): ?>
                            new Notification('StudyBuddy Reminder', {
                                body: 'Your next task "<?php echo addslashes($upcoming_tasks[0]['title']); ?>" is due in <?php 
                                    $days = (new DateTime($upcoming_tasks[0]['deadline']))->diff(new DateTime())->days;
                                    echo $days . ' day' . ($days != 1 ? 's' : '');
                                ?>!',
                                icon: 'https://via.placeholder.com/32/007bff/ffffff?text=📚',
                                tag: 'studybuddy-reminder'
                            });
                        <?php else: ?>
                            new Notification('StudyBuddy', {
                                body: 'Notifications enabled! You’ll get alerts for deadlines.',
                                icon: 'https://via.placeholder.com/32/007bff/ffffff?text=⏰'
                            });
                        <?php endif; ?>
                    } else {
                        alert('Permission denied. Notifications not enabled.');
                    }
                });
            } else {
                // Disable notifications
                notifEnabled = false;
                localStorage.setItem('studybuddy_notifications', 'disabled');
                updateButtonState();
                alert('Notifications disabled. You won’t receive reminders.');
            }
        });

        // Periodic reminders (demo: every 30s)
        setInterval(() => {
            if (notifEnabled && Notification.permission === 'granted') {
                <?php if (!empty($upcoming_tasks) || !empty($overdue_tasks)): ?>
                    new Notification('StudyBuddy Alert', {
                        body: <?php 
                            if (!empty($overdue_tasks)) {
                                echo '"' . addslashes($overdue_tasks[0]['title']) . ' is overdue! Complete it now."';
                            } else {
                                echo '"Check your upcoming tasks to stay on track."';
                            }
                        ?>,
                        icon: 'https://via.placeholder.com/32/ff0000/ffffff?text=⚠️',
                        tag: 'studybuddy-check'
                    });
                <?php endif; ?>
            }
        }, 30000);
    </script>
</body>
</html>