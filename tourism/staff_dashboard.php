<?php
session_start();
include '../config/db_connect.php';

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

<!-- Navbar / Header -->
<header class="bg-green-500 text-white shadow p-4 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Tourism Staff Dashboard</h1>
    <button onclick="openModal('logoutModal')" class="bg-green-500 hover:bg-green-500 px-4 py-2 rounded flex items-center gap-2 transition">
        <i class="fas fa-sign-out-alt"></i> Logout
    </button>
</header>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md text-center">
        <h2 class="text-lg font-semibold mb-4 text-red-600">Confirm Logout</h2>
        <p class="mb-6">Are you sure you want to logout?</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeModal('logoutModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <a href="../config/logout.php" class="px-4 py-2 bg-red-500 text-white rounded">Logout</a>
        </div>
    </div>
</div>

<script>
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}
</script>

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
