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

    // Realtime Database & Account Status Check
    global $pdo;
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $dbUser = $stmt->fetch();

            if (!$dbUser) {
                // User deleted from database/firebase
                session_unset();
                session_destroy();
                header("Location: " . $redirect_path . "login.php?error=account_deleted");
                exit;
            }

            if ($dbUser['status'] === 'suspended') {
                session_unset();
                session_destroy();
                header("Location: " . $redirect_path . "login.php?error=account_deleted");
                exit;
            } elseif ($dbUser['status'] === 'inactive') {
                session_unset();
                session_destroy();
                header("Location: " . $redirect_path . "login.php?error=account_inactive");
                exit;
            }

            // Sync role if changed
            $_SESSION['role'] = $dbUser['role'];

        } catch (PDOException $e) {
            // Fallback if DB check fails
        }
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
