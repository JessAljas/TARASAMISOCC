<?php
session_start();

// mo Clear sa all session data
$_SESSION = [];
session_unset();
session_destroy();

// dri mo direct if mo logout, back to admin login page
header("Location: admin_login.php");
exit;
?>
