<?php
require_once 'config.php';
date_default_timezone_set('Asia/Manila');

if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$task_id = intval($_GET['id']);

// Fetch the task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$task_id, $user_id]);
$task = $stmt->fetch();

if (!$task) {
    header("Location: dashboard.php");
    exit;
}

// Fetch subjects for dropdown
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ?");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

$error = '';

if ($_POST) {
    $title = trim($_POST['title']);
    $subject_id = intval($_POST['subject_id']);
    $description = trim($_POST['description']);
    $deadline_input = $_POST['deadline'];
    $estimated_hours = intval($_POST['estimated_hours']);
    $deadline_ts = strtotime($deadline_input);

    if (empty($title)) {
        $error = "Title cannot be empty.";
    } elseif ($subject_id <= 0) {
        $error = "Please select a valid subject.";
    } elseif ($deadline_ts === false) {
        $error = "Invalid deadline format.";
    } elseif ($deadline_ts < time()) {
        $error = "Deadline cannot be set in the past.";
    } else {
        $deadline = date('Y-m-d H:i:s', $deadline_ts);

        $stmt = $pdo->prepare("UPDATE tasks SET title = ?, subject_id = ?, description = ?, deadline = ?, estimated_hours = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$title, $subject_id, $description, $deadline, $estimated_hours, $task_id, $user_id]);

        header("Location: dashboard.php?success=Task updated successfully!");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Task - StudyBuddy</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<link rel="stylesheet" href="css/dashboard-style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
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
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

<div class="container mt-4">
    <div class="page-header fade-in">
        <h2><i class="fas fa-edit"></i> Edit Task</h2>
        <p>Update your task details and keep your schedule organized</p>
    </div>

    <div class="card fade-in">
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-book text-primary"></i> Subject *
                        </label>
                        <select name="subject_id" class="form-select" required>
                            <option value="">Choose a subject...</option>
                            <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $task['subject_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($sub['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback">Please select a subject.</div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label class="form-label">
                            <i class="fas fa-calendar text-danger"></i> Deadline *
                        </label>
                        <input type="datetime-local" name="deadline" class="form-control"
                            min="<?= date('Y-m-d\TH:i') ?>" 
                            value="<?= date('Y-m-d\TH:i', strtotime($task['deadline'])) ?>" required>
                        <div class="invalid-feedback">Please set a valid deadline.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-heading text-info"></i> Task Title *
                    </label>
                    <input type="text" name="title" class="form-control" 
                           value="<?php echo htmlspecialchars($task['title']); ?>" 
                           placeholder="Task title" required maxlength="200">
                    <div class="invalid-feedback">Please enter a task title.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">
                        <i class="fas fa-align-left text-secondary"></i> Description (Optional)
                    </label>
                    <textarea name="description" class="form-control" rows="4" 
                              placeholder="Add details about the task..."><?php echo htmlspecialchars($task['description']); ?></textarea>
                </div>

                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-clock text-warning"></i> Estimated Study Hours (Optional)
                    </label>
                    <input type="number" name="estimated_hours" class="form-control" 
                           value="<?php echo $task['estimated_hours']; ?>" 
                           placeholder="e.g., 2" min="0" max="24">
                    <small class="text-muted">
                        <i class="fas fa-info-circle"></i> How many hours do you think this task will take?
                    </small>
                </div>

                <div class="d-flex gap-2 justify-content-between">
                    <a href="view_task.php?id=<?php echo $task_id; ?>" class="btn btn-outline-info">
                        <i class="fas fa-eye"></i> View Details
                    </a>
                    <div class="d-flex gap-2">
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-check"></i> Update Task
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Task Status Card -->
    <div class="card fade-in mt-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="mb-1">Current Status</h6>
                    <?php if ($task['status'] == 'completed'): ?>
                        <span class="badge bg-success" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-check-circle"></i> Completed
                        </span>
                    <?php else: ?>
                        <span class="badge bg-warning text-dark" style="font-size: 1rem; padding: 0.5rem 1rem;">
                            <i class="fas fa-hourglass-half"></i> Pending
                        </span>
                    <?php endif; ?>
                </div>
                <div class="text-end">
                    <small class="text-muted d-block">Created: <?php echo date('M j, Y', strtotime($task['created_at'])); ?></small>
                    <?php if ($task['updated_at']): ?>
                        <small class="text-muted d-block">Updated: <?php echo date('M j, Y', strtotime($task['updated_at'])); ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict'
    const forms = document.querySelectorAll('.needs-validation')
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })

    // Set min datetime dynamically
    const datetimeInput = document.querySelector('input[type="datetime-local"]');
    if (datetimeInput) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        datetimeInput.min = now.toISOString().slice(0,16);
    }

    // Add fade-in animation with delay
    document.querySelectorAll('.fade-in').forEach((el, index) => {
        el.style.animationDelay = `${index * 0.1}s`;
    });
})();
</script>
</body>
</html>