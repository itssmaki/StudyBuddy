<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_GET['id'] ?? null;

if (!$task_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch task details
$stmt = $pdo->prepare("
    SELECT t.*, s.name AS subject_name
    FROM tasks t
    JOIN subjects s ON t.subject_id = s.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: dashboard.php?error=Task not found");
    exit;
}

// Calculate time remaining
$deadline = new DateTime($task['deadline']);
$now = new DateTime();
$interval = $now->diff($deadline);
$is_overdue = $now > $deadline;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Task - <?php echo htmlspecialchars($task['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 2rem 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .task-container {
            max-width: 900px;
            margin: 0 auto;
        }
        
        .task-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .task-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .task-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .task-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            position: relative;
            z-index: 1;
        }
        
        .task-body {
            padding: 2.5rem 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border-left: 4px solid #667eea;
            transition: all 0.3s ease;
        }
        
        .info-card:hover {
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .info-label {
            display: flex;
            align-items: center;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-label i {
            margin-right: 0.75rem;
            color: #667eea;
            font-size: 1.2rem;
        }
        
        .info-content {
            color: #212529;
            font-size: 1.1rem;
            line-height: 1.6;
        }
        
        .description-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .description-card .info-content {
            white-space: pre-wrap;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 1rem;
        }
        
        .status-completed {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
        }
        
        .status-pending {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .deadline-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .time-remaining {
            display: inline-flex;
            align-items: center;
            background: #fff3cd;
            color: #856404;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .time-remaining.overdue {
            background: #f8d7da;
            color: #721c24;
        }
        
        .time-remaining i {
            margin-right: 0.5rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            padding: 2rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .btn-custom {
            padding: 0.75rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }
        
        .btn-edit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .btn-edit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(240, 147, 251, 0.4);
            color: white;
        }
        
        @media (max-width: 768px) {
            .task-header h1 {
                font-size: 1.5rem;
            }
            
            .task-body {
                padding: 1.5rem 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn-custom {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<div class="container task-container">
    <div class="task-card">
        <div class="task-header">
            <h1><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($task['title']); ?></h1>
        </div>
        
        <div class="task-body">
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-book"></i> Subject
                </div>
                <div class="info-content">
                    <?php echo htmlspecialchars($task['subject_name']); ?>
                </div>
            </div>
            
            <div class="description-card">
                <div class="info-label">
                    <i class="fas fa-align-left"></i> Description
                </div>
                <div class="info-content">
                    <?php echo nl2br(htmlspecialchars($task['description'])); ?>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-calendar-alt"></i> Deadline
                </div>
                <div class="deadline-info">
                    <div class="info-content">
                        <?php echo date('F j, Y g:i A', strtotime($task['deadline'])); ?>
                    </div>
                    <?php if ($task['status'] != 'completed'): ?>
                        <span class="time-remaining <?php echo $is_overdue ? 'overdue' : ''; ?>">
                            <i class="fas fa-clock"></i>
                            <?php 
                            if ($is_overdue) {
                                echo "Overdue by " . $interval->days . " day" . ($interval->days != 1 ? 's' : '');
                            } else {
                                echo $interval->days . " day" . ($interval->days != 1 ? 's' : '') . " remaining";
                            }
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-label">
                    <i class="fas fa-flag"></i> Status
                </div>
                <div class="info-content">
                    <span class="status-badge <?php echo $task['status'] == 'completed' ? 'status-completed' : 'status-pending'; ?>">
                        <i class="fas fa-<?php echo $task['status'] == 'completed' ? 'check-circle' : 'clock'; ?>"></i>
                        <?php echo ucfirst($task['status']); ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="action-buttons">
            <a href="edit_task.php?id=<?php echo $task_id; ?>" class="btn btn-custom btn-edit">
                <i class="fas fa-edit"></i> Edit Task
            </a>
            <a href="dashboard.php" class="btn btn-custom btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>