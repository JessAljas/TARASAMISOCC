<?php
session_start();
include '../config/db_connect.php';
include 'tourism_header.php';

// Restrict access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

// Get staff ID from session
$tourism_officer_id = $_SESSION['user']['id'] ?? 0;

if ($tourism_officer_id == 0) {
    die("Staff ID not found in session.");
}

// Fetch staff info from DB
$result = $conn->query("SELECT * FROM tourism_officers WHERE id = $tourism_officer_id");

if ($result && $result->num_rows > 0) {
    $staff = $result->fetch_assoc();
} else {
    die("Tourism staff not found in database.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourism Staff Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-blue-50 min-h-screen flex flex-col font-[Poppins]">


<!-- Welcome Card -->
<div class="flex flex-1 justify-center items-center mt-10">
    <div class="text-center bg-white p-16 rounded-xl shadow-lg m-6">
        <h1 class="text-4xl md:text-5xl font-bold text-green-600 mb-4">
            Welcome, <?= htmlspecialchars($staff['fullname']) ?>!
        </h1>
        <p class="text-xl text-gray-700 mb-6">
            Hello! This is your dashboard where you can manage tourist spots.
        </p>
        <div class="flex justify-center gap-4">
            <a href="manage_request.php" class="bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-list"></i> Manage Requests
            </a>
        </div>
    </div>
</div>

</body>
</html>
