<?php
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

// Fetch all users including is_active
$stmt = $pdo->query("SELECT id, username, email, role, is_active, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();

// Handle messages from redirects
$message = isset($_GET['message']) ? htmlspecialchars($_GET['message']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Management - Admin Panel</title>
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

/* Global Styles */
body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Navbar Styles */
.navbar {
    background: var(--primary-gradient) !important;
    box-shadow: var(--shadow);
    padding: 1rem 0;
}

.navbar.navbar-dark.bg-primary {
    background: var(--primary-gradient) !important;
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

.stat-card-compact.total::before {
    background: var(--primary-gradient);
}

.stat-card-compact.success::before {
    background: var(--success-gradient);
}

.stat-card-compact.danger::before {
    background: var(--danger-gradient);
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
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    color: #667eea;
}

.stat-card-compact.success .stat-icon {
    background: linear-gradient(135deg, rgba(72, 199, 116, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
    color: #10b981;
}

.stat-card-compact.danger .stat-icon {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(220, 38, 38, 0.1) 100%);
    color: #ef4444;
}

.stat-card-compact .stat-content {
    flex: 1;
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

.stat-card-compact .stat-content p {
    font-size: 0.9rem;
    color: #6b7280;
    margin-top: 0.25rem;
}

.content-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--shadow);
}

.search-wrapper {
    position: relative;
    margin-bottom: 1.5rem;
}

.search-wrapper i {
    position: absolute;
    left: 1.25rem;
    top: 50%;
    transform: translateY(-50%);
    color: #667eea;
    font-size: 1.1rem;
    z-index: 1;
}

#searchInput {
    background: white;
    border-radius: 25px;
    padding: 0.875rem 1.25rem 0.875rem 3.25rem;
    border: 2px solid #e5e7eb;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    width: 100%;
}

#searchInput:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    outline: none;
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

.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 45px;
    height: 45px;
    border-radius: 12px;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.badge {
    padding: 0.5rem 1rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
}

.badge.bg-success {
    background: var(--success-gradient) !important;
    border: none;
}

.badge.bg-danger {
    background: var(--danger-gradient) !important;
    border: none;
}

.role-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.85rem;
    background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
    color: #374151;
    border: 2px solid #e5e7eb;
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

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
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

.alert-success {
    background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    color: #065f46;
}

.alert-danger {
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    color: #991b1b;
}

.no-results {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.no-results i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
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
    
    .user-avatar {
        width: 40px;
        height: 40px;
        font-size: 1rem;
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
  <?php if ($message): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <span><?= $message ?></span>
    </div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i>
      <span><?= $error ?></span>
    </div>
  <?php endif; ?>
  
  <div class="page-header">
    <h2><i class="fas fa-users-cog"></i> User Management</h2>
    <p>Manage and monitor all registered users in the system</p>
  </div>
  
  <!-- Statistics -->
  <div class="stats-row">
    <div class="stat-card-compact total">
      <div class="stat-icon">
        <i class="fas fa-users"></i>
      </div>
      <div class="stat-content">
        <h5>Total Users</h5>
        <h2 id="totalUsers"><?= count($users) ?></h2>
      </div>
    </div>
    <div class="stat-card-compact success">
      <div class="stat-icon">
        <i class="fas fa-user-check"></i>
      </div>
      <div class="stat-content">
        <h5>Active Users</h5>
        <h2 id="activeUsers"><?= count(array_filter($users, fn($u) => $u['is_active'])) ?></h2>
      </div>
    </div>
    <div class="stat-card-compact danger">
      <div class="stat-icon">
        <i class="fas fa-user-slash"></i>
      </div>
      <div class="stat-content">
        <h5>Inactive Users</h5>
        <h2 id="inactiveUsers"><?= count(array_filter($users, fn($u) => !$u['is_active'])) ?></h2>
      </div>
    </div>
  </div>
  
  <div class="content-card">
    <!-- Search Input -->
    <div class="search-wrapper">
      <i class="fas fa-search"></i>
      <input type="text" id="searchInput" class="form-control" placeholder="Search by username, email, role, or status...">
    </div>
    
    <div class="table-wrapper">
      <table class="table" id="userTable">
        <thead>
          <tr>
            <th>#</th>
            <th><i class="fas fa-user"></i> User</th>
            <th><i class="fas fa-envelope"></i> Email</th>
            <th><i class="fas fa-shield-alt"></i> Role</th>
            <th><i class="fas fa-toggle-on"></i> Status</th>
            <th><i class="fas fa-calendar"></i> Registered</th>
            <th><i class="fas fa-cog"></i> Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $i => $u): ?>
          <tr>
            <td><strong><?= $i + 1 ?></strong></td>
            <td>
              <div class="user-info">
                <div class="user-avatar">
                  <?= strtoupper(substr($u['username'], 0, 1)) ?>
                </div>
                <strong><?= htmlspecialchars($u['username']) ?></strong>
              </div>
            </td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td>
              <span class="role-badge">
                <i class="fas fa-<?= ($u['role'] ?? 'user') === 'admin' ? 'shield-alt' : 'user' ?>"></i>
                <?= htmlspecialchars($u['role'] ?? 'user') ?>
              </span>
            </td>
            <td>
              <?= $u['is_active'] 
                ? '<span class="badge bg-success"><i class="fas fa-check"></i> Active</span>' 
                : '<span class="badge bg-danger"><i class="fas fa-times"></i> Inactive</span>' ?>
            </td>
            <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
            <td>
              <a href="edit_user.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">
                <i class="fas fa-edit"></i> Edit
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#userTable tbody tr');
    const tableBody = document.querySelector('#userTable tbody');
    
    searchInput.addEventListener('keyup', function() {
        const searchTerm = searchInput.value.toLowerCase();
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            const username = row.querySelector('td:nth-child(2)')?.textContent.toLowerCase() || '';
            const email = row.querySelector('td:nth-child(3)')?.textContent.toLowerCase() || '';
            const role = row.querySelector('td:nth-child(4)')?.textContent.toLowerCase() || '';
            const status = row.querySelector('td:nth-child(5)')?.textContent.toLowerCase() || '';
            
            if (username.includes(searchTerm) || email.includes(searchTerm) || 
                role.includes(searchTerm) || status.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Remove existing no results message if any
        const existingNoResults = tableBody.querySelector('.no-results-row');
        if (existingNoResults) {
            existingNoResults.remove();
        }
        
        // Show no results message if no rows are visible
        if (visibleCount === 0 && searchTerm !== '') {
            const noResultsRow = document.createElement('tr');
            noResultsRow.className = 'no-results-row';
            noResultsRow.innerHTML = `
                <td colspan="7" class="no-results">
                    <i class="fas fa-search"></i>
                    <p>No users found matching "${searchTerm}"</p>
                </td>
            `;
            tableBody.appendChild(noResultsRow);
        }
    });
});
</script>

</body>
</html>