<?php
require_once 'config.php';
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Prevent inactivating self
    if ($id == $_SESSION['admin_id']) {
        header("Location: admin_users.php?error=You cannot inactivate yourself");
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        header("Location: admin_users.php?error=User not found");
        exit;
    }
    
    // Set user as inactive
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);
    
    header("Location: admin_users.php?message=User inactivated successfully");
    exit;
} else {
    header("Location: admin_users.php?error=Invalid user ID");
    exit;
}
