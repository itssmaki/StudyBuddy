<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$start_time = $_POST['start_time'];
$end_time = date('Y-m-d H:i:s');
$duration = (strtotime($end_time) - strtotime($start_time)) / 60;

if ($duration > 0) {
    $stmt = $pdo->prepare("INSERT INTO study_sessions (user_id, duration, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, round($duration)]);
}
?>
