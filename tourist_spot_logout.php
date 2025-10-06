<?php
session_start();

// Clear sa all session data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect back to admin login page
header("Location: tourist_spot_login.php");
exit;
?>
