<?php
session_start();

// Check if a role is set before destroying the session
if (isset($_SESSION['role'])) {
    $role = $_SESSION['role']; // e.g., 'agency', 'spot_owner', or 'tourism_officer'

    // Clear session data
    $_SESSION = [];
    session_unset();
    session_destroy();

    // Redirect based on role
    if ($role === 'agency') {
        header("Location: agency_login.php");
    } elseif ($role === 'spot_owner') {
        header("Location: spot_owner_login.php");
    } elseif ($role === 'tourism_officer') {
        header("Location: tourism_officer_login.php");
    } else {
        // Fallback to general login if role is unknown
        header("Location: index.php");
    }

    exit;
} else {
    // No role found in session, just clear and redirect to general login
    $_SESSION = [];
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}
