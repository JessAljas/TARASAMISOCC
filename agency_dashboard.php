<?php 
session_start(); 
include 'db_connect.php';  

// Redirect if wala ka logged in
if (!isset($_SESSION['user'])) {
    header("Location: admin_login.php");
    exit;
}


// Booking counts sa card
$total_bookings_pending   = $conn->query("SELECT COUNT(*) AS total FROM pay_via_qr WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$total_bookings_approved  = $conn->query("SELECT COUNT(*) AS total FROM pay_via_qr WHERE status='approved'")->fetch_assoc()['total'] ?? 0;
$total_bookings_completed = $conn->query("SELECT COUNT(*) AS total FROM pay_via_qr WHERE status='completed'")->fetch_assoc()['total'] ?? 0;
$total_payments_received  = $conn->query("SELECT SUM(total) AS total FROM pay_via_qr WHERE status IN ('approved','completed')")->fetch_assoc()['total'] ?? 0;

// Pag fetch sa recent bookings with tourist and package info
$recent_bookings_stmt = $conn->prepare("
    SELECT pv.id, pv.reference_number, pv.status, pv.pax, pv.total, pv.created_at, t.fullname, p.title AS package_title
    FROM pay_via_qr pv
    JOIN tourists t ON pv.tourist_id = t.id
    JOIN packages p ON pv.package_id = p.id
    ORDER BY pv.created_at DESC
    LIMIT 10
");
$recent_bookings_stmt->execute();
$recent_bookings = $recent_bookings_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agency Dashboard</title>

<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />

<style>
body { margin:0; font-family:sans-serif; }
#mainContent { transition: margin-left 0.3s; }
.dashboard-cards { position: sticky; top:0; background:#f9fafb; padding:1.5rem; z-index:20; display:flex; flex-wrap:wrap; gap:1rem; justify-content:center; }
.dashboard-card { flex:1 1 22%; min-width:200px; background:#fff; padding:1rem; border-radius:.5rem; box-shadow:0 4px 6px rgba(0,0,0,.1); text-align:center; }
.search-bar { border:1px solid #ccc; padding:.5rem; border-radius:.25rem; }
.status-approved { background-color:#22c55e;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-pending { background-color:#f97316;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-completed { background-color:#3b82f6;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-other { background-color:#e5e7eb; color:#374151; padding:.25rem .5rem; border-radius:.25rem; text-align:center; }
</style>
</head>
<body class="bg-gray-100 flex font-[Poppins]">

<!-- Ang Sidebar code -->
<div id="sidebar" class="w-64 bg-green-500 text-white min-h-screen sticky top-0 flex-shrink-0 hidden md:flex flex-col">
    <?php include 'sidebar.php'; ?>
</div>

<!-- Menu button  -->
<button id="menuBtn" onclick="toggleSidebar()" class="fixed top-3 left-3 z-50 bg-green-500 text-white p-2 rounded-md md:hidden flex items-center gap-2">
  <i id="menuIcon" class="fas fa-bars"></i>
</button>

<!-- Ang main content sa page-->
<div id="mainContent" class="flex-1 p-6 transition-all duration-300">

  <!-- The dashboard Cards -->
  <div class="dashboard-cards">
    <div class="dashboard-card bg-yellow-100">
      <i class="fas fa-hourglass-half text-4xl text-yellow-500 mb-3"></i>
      <h2 class="text-lg font-semibold text-gray-700">Pending Bookings</h2>
      <p class="text-3xl font-bold text-yellow-600 mt-2"><?= $total_bookings_pending ?></p>
    </div>

    <div class="dashboard-card bg-pink-50">
      <i class="fas fa-check-double text-4xl text-pink-500 mb-3"></i>
      <h2 class="text-lg font-semibold text-gray-700">Approved Bookings</h2>
      <p class="text-3xl font-bold text-pink-600 mt-2"><?= $total_bookings_approved ?></p>
    </div>

    <div class="dashboard-card bg-blue-100">
      <i class="fas fa-clipboard-check text-4xl text-blue-600 mb-3"></i>
      <h2 class="text-lg font-semibold text-gray-700">Completed Bookings</h2>
      <p class="text-3xl font-bold text-blue-700 mt-2"><?= $total_bookings_completed ?></p>
    </div>

    <div class="dashboard-card bg-green-100">
      <i class="fas fa-money-bill-wave text-4xl text-green-700 mb-3"></i>
      <h2 class="text-lg font-semibold text-gray-700">Total Revenue</h2>
      <p class="text-3xl font-bold text-green-700 mt-2">₱<?= number_format($total_payments_received, 2) ?></p>
    </div>
  </div>

  <!-- The recent nga Bookings Table -->
  <div class="mt-6 bg-white p-4 rounded shadow overflow-x-auto">
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-xl font-bold">Recent Bookings</h2>
      <div class="flex items-center gap-2">
        <label for="searchInput" class="font-semibold">Search:</label>
        <input type="text" id="searchInput" class="search-bar" placeholder="Reference No.." onkeyup="filterTable()">
      </div>
    </div>

    <table class="min-w-full text-left border border-gray-200" id="bookingsTable">
      <thead>
        <tr class="bg-gray-100">
          <th class="px-3 py-2 border">Tourist</th>
          <th class="px-3 py-2 border">Package</th>
          <th class="px-3 py-2 border">Reference No.</th>
          <th class="px-3 py-2 border">Status</th>
          <th class="px-3 py-2 border">Pax</th>
          <th class="px-3 py-2 border">Date Book</th>
        </tr>
      </thead>
      <tbody>
        <?php while($row = $recent_bookings->fetch_assoc()): ?>
        <tr class="border">
          <td class="px-3 py-2 border"><?= htmlspecialchars($row['fullname']) ?></td>
          <td class="px-3 py-2 border"><?= htmlspecialchars($row['package_title']) ?></td>
          <td class="px-3 py-2 border"><?= htmlspecialchars($row['reference_number']) ?></td>
          <td class="px-3 py-2 border">
            <?php 
            $status = strtolower($row['status'] ?? '');
            if($status === 'approved') {
                $status_class = 'status-approved';
            } elseif($status === 'pending') {
                $status_class = 'status-pending';
            } elseif($status === 'completed') {
                $status_class = 'status-completed';
            } else {
                $status_class = 'status-other';
            }
            ?>
            <span class="<?= $status_class ?>"><?= ucfirst($status ?: 'Pending') ?></span>
          </td>
          <td class="px-3 py-2 border"><?= intval($row['pax']) ?></td>
          <td class="px-3 py-2 border"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

</div>

<script src="script.js"></script>

</body>
</html>
