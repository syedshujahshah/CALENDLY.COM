<?php
require_once 'db.php';
startSession();

// Destroy session
session_destroy();

// Redirect to home page
echo "<script>window.location.href = 'index.php';</script>";
exit;
?>
