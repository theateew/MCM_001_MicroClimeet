<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function check_auth($required_role = null) {
    // Determine the base path for redirection
    $redirect_path = (defined('AUTH_BASE') ? AUTH_BASE : '');
    
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . $redirect_path . "login.php");
        exit;
    }
    
    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        // If logged in but accessing the wrong section, redirect accordingly
        if ($_SESSION['role'] === 'admin') {
            header("Location: " . $redirect_path . "admin/index.php");
        } else {
            header("Location: " . $redirect_path . "petani/index.php");
        }
        exit;
    }
}
?>
