<?php
$host = "localhost";   // ulocalhost
$user = "root";        // MySQL username
$pass = "";            // MySQL nga password
$db   = "finalsystem"; // Name sa akong database

$conn = new mysqli($host, $user, $pass, $db);

// Check sa connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
