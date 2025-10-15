<?php
session_start();

// Check if session exists
if (isset($_SESSION['user'])) {
    $user_role = $_SESSION['user']['role'];

    // Unset all session variables
    $_SESSION = array();

    // If using cookies, destroy the session cookie too
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destroy session
    session_destroy();

    // Redirect based on role
    switch ($user_role) {
        case 'agency':
            header("Location: ../index.php");
            break;
        case 'spot_owner':
        case 'tourism_officer':
        case 'tourist':
        default:
            header("Location: ../index.php");
            break;
    }
    exit;
} else {
    // Fallback if no session exists
    header("Location: ../index.php");
    exit;
}
?>
