<?php
session_start();

// Clear session sa all data
$_SESSION = [];
session_unset();
session_destroy();

// Redirect back to login page
header("Location: index.php");

if
exit;
