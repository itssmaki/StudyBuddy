<?php
require_once 'config.php';

// Destroy session and redirect to landing page with success message
session_destroy();
header("Location: index.php?logout=1");
exit;
?>
