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
    if (isset($_POST['add_subject'])) {
        $name = trim($_POST['name']);
        if (empty($name)) {
            $error = "Subject name is required.";
        } elseif (strlen($name) > 100) {
            $error = "Subject name too long.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO subjects (user_id, name) VALUES (?, ?)");
            if ($stmt->execute([$user_id, $name])) {
                $success = "Subject added successfully!";
                header("Location: subjects.php?success=1");
                exit;
            } else {
                $error = "Failed to add subject. It may already exist.";
            }
        }
    } elseif (isset($_POST['delete_subject'])) {
        $subject_id = (int)$_POST['subject_id'];
        $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ? AND user_id = ?");
        if ($stmt->execute([$subject_id, $user_id])) {
            $success = "Subject deleted successfully! (Related tasks removed)";
            header("Location: subjects.php?success=1");
            exit;
        } else {
            $error = "Failed to delete subject.";
        }
    }
}

if (isset($_GET['success'])) {
    $success = "Action completed!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - StudyBuddy</title>
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
            background: white;
        }

        .card:hover {
            box-shadow: var(--shadow-hover);
            transform: translateY(-5px);
        }

        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: none;
            padding: 1.5rem;
        }

        .card-header h5 {
            margin: 0 0 0.5rem 0;
            font-weight: 700;
            color: #1f2937;
            font-size: 1.5rem;
        }

        .card-header p {
            margin: 0;
            color: #64748b;
        }

        .alert {
            border: none;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
        }

        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e5e7eb;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }

        .input-group .btn {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
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

        .btn-danger {
            background: var(--danger-gradient);
        }

        .btn-outline-secondary {
            border: 2px solid #e5e7eb;
            color: #6b7280;
        }

        .btn:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .table-responsive {
            border-radius: 15px;
            overflow: hidden;
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
            color: #1f2937;
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .table tbody tr:last-child {
            border-bottom: none;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
        }

        .badge.bg-info {
            background: var(--info-gradient) !important;
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

        .empty-state p {
            color: #64748b;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        .empty-state .input-group {
            max-width: 600px;
            margin: 0 auto;
        }

        .subject-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: var(--primary-gradient);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .subject-name-cell {
            display: flex;
            align-items: center;
        }

        @media (max-width: 768px) {
            .table {
                font-size: 0.875rem;
            }

            .subject-icon {
                width: 32px;
                height: 32px;
                font-size: 1rem;
            }

            .btn-sm {
                padding: 0.4rem 0.8rem;
                font-size: 0.75rem;
            }
        }
    </style>
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
                <a class="nav-link active" href="subjects.php"><i class="fas fa-book"></i> Subjects</a>
                <a class="nav-link" href="timer.php"><i class="fas fa-clock"></i> Timer</a>
                <a class="nav-link" href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4 mb-5">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-layer-group me-2"></i>Subject Organizer</h5>
                <p class="mb-0 text-muted">Sort and manage your academic subjects</p>
            </div>
            <div class="card-body">
                <?php if ($success): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Add Subject Form -->
                <form method="POST" class="mb-4">
                    <div class="input-group">
                        <input type="text" class="form-control" name="name" placeholder="New Subject (e.g., Math, Biology)" required maxlength="100">
                        <button type="submit" name="add_subject" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Subject
                        </button>
                    </div>
                </form>

                <!-- Subjects List -->
                <?php if (empty($subjects)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No subjects yet. Add one to start organizing tasks!</p>
                        <form method="POST">
                            <div class="input-group">
                                <input type="text" class="form-control" name="name" placeholder="e.g., Mathematics" required maxlength="100">
                                <button type="submit" name="add_subject" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Add First Subject
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Subject Name</th>
                                    <th>Created</th>
                                    <th>Tasks Count</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $subject): 
                                    // Count tasks for this subject
                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tasks WHERE subject_id = ? AND user_id = ?");
                                    $stmt->execute([$subject['id'], $user_id]);
                                    $task_count = $stmt->fetch()['count'];
                                    
                                    // Get first letter for icon
                                    $first_letter = strtoupper(substr($subject['name'], 0, 1));
                                ?>
                                <tr>
                                    <td>
                                        <div class="subject-name-cell">
                                            <div class="subject-icon"><?php echo $first_letter; ?></div>
                                            <strong><?php echo htmlspecialchars($subject['name']); ?></strong>
                                        </div>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($subject['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-info">
                                            <i class="fas fa-tasks me-1"></i><?php echo $task_count; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($task_count > 0): ?>
                                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                                <i class="fas fa-lock me-1"></i>Delete (Has Tasks)
                                            </button>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this subject? All related tasks will be removed.');">
                                                <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                                <button type="submit" name="delete_subject" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>