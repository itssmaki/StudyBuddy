<?php
require_once 'config.php';
if (!isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch subjects
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE user_id = ? ORDER BY name ASC");
$stmt->execute([$user_id]);
$subjects = $stmt->fetchAll();

$error = $success = '';
if ($_POST) {
    $subject_id = (int)$_POST['subject_id'];
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $deadline = $_POST['deadline'];
    $estimated_hours = (int)$_POST['estimated_hours'];

    if (empty($title) || empty($deadline) || $subject_id <= 0) {
        $error = "Subject, title, and deadline are required.";
    } elseif (strtotime($deadline) < time()) {
        $error = "Deadline must be in the future.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO tasks (user_id, subject_id, title, description, deadline, estimated_hours) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $subject_id, $title, $description, $deadline, $estimated_hours])) {
            header("Location: dashboard.php?success=Task added successfully!");
            exit;
        } else {
            $error = "Failed to add task. Try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Task - StudyBuddy</title>
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
                <a class="nav-link active" href="add_task.php"><i class="fas fa-plus-circle"></i> Add Task</a>
                <a class="nav-link" href="subjects.php"><i class="fas fa-book"></i> Subjects</a>
                <a class="nav-link" href="timer.php"><i class="fas fa-clock"></i> Timer</a>
                <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="page-header fade-in">
            <h2><i class="fas fa-plus-circle"></i> Add New Task</h2>
            <p>Create a new task and stay organized with your study schedule</p>
        </div>

        <div class="card fade-in">
            <div class="card-body p-4">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (empty($subjects)): ?>
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle"></i> 
                        <strong>No subjects yet!</strong> 
                        <a href="subjects.php" class="alert-link">Add a subject first</a> to organize your tasks.
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <div class="empty-state">
                        <i class="fas fa-book-open"></i>
                        <h4>Start by creating a subject</h4>
                        <p class="text-muted mb-4">Subjects help you organize your tasks by category or course</p>
                        <a href="subjects.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Subject
                        </a>
                    </div>
                <?php else: ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-book text-primary"></i> Subject *
                                </label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject...</option>
                                    <?php foreach ($subjects as $subject): ?>
                                        <option value="<?php echo $subject['id']; ?>">
                                            <?php echo htmlspecialchars($subject['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback">Please select a subject.</div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    <i class="fas fa-calendar text-danger"></i> Deadline *
                                </label>
                                <input type="datetime-local" class="form-control" name="deadline" required>
                                <div class="invalid-feedback">Please set a deadline.</div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-heading text-info"></i> Task Title *
                            </label>
                            <input type="text" class="form-control" name="title" 
                                   placeholder="e.g., Finish Math Assignment Chapter 5" 
                                   required maxlength="200">
                            <div class="invalid-feedback">Please enter a task title.</div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-align-left text-secondary"></i> Description (Optional)
                            </label>
                            <textarea class="form-control" name="description" 
                                      placeholder="Add details about the task, requirements, or notes..." 
                                      rows="4"></textarea>
                            <small class="text-muted">
                                <i class="fas fa-lightbulb"></i> Tip: Add specific details to help you remember what needs to be done
                            </small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label">
                                <i class="fas fa-clock text-warning"></i> Estimated Study Hours (Optional)
                            </label>
                            <input type="number" class="form-control" name="estimated_hours" 
                                   placeholder="e.g., 2" min="0" max="24" value="0">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> How many hours do you think this task will take?
                            </small>
                        </div>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-check"></i> Add Task
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Form validation
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
        })()

        // Set minimum datetime to current time
        const datetimeInput = document.querySelector('input[type="datetime-local"]');
        if (datetimeInput) {
            const now = new Date();
            now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
            datetimeInput.min = now.toISOString().slice(0, 16);
        }

        // Add fade-in animation with delay
        document.querySelectorAll('.fade-in').forEach((el, index) => {
            el.style.animationDelay = `${index * 0.1}s`;
        });
    </script>
</body>
</html>