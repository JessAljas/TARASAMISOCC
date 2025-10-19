<?php 
session_start(); 
include '../config/db_connect.php'; 

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

// Fetch recent bookings
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
    <link rel="stylesheet" href="css/style.css">
<style>
body { margin:0; font-family:sans-serif; }
#mainContent { transition: margin-left 0.3s; }
.dashboard-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; }
.dashboard-card { background-color: white; padding: 1.5rem; border-radius: 0.5rem; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
.search-bar { border:1px solid #ccc; padding:.5rem; border-radius:.25rem; }
.status-approved { background-color:#22c55e;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-pending { background-color:#f97316;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-completed { background-color:#3b82f6;color:white;padding:.25rem .5rem;border-radius:.25rem;text-align:center; }
.status-other { background-color:#e5e7eb; color:#374151; padding:.25rem .5rem; border-radius:.25rem; text-align:center; }


</style>
</head>
<body class="bg-gray-100 flex font-[Poppins]">

    <?php include 'sidebar.php'; ?>
    <div id="mainContent" class="flex-1">
    <main class="max-w-5xl mx-auto mt-1">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-3">
        <span>Add New Tour Packages</span>
      </h1>
    </div>

  <!-- Dashboard Cards -->
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

  <!-- Scrollable Recent Bookings Table -->
  <div class="mt-6 bg-white p-4 rounded shadow scrollable-table">
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
      <th class="px-3 py-2 border">Reference No.</th>
      <th class="px-3 py-2 border">Tourist</th>
      <th class="px-3 py-2 border">Package</th>
      <th class="px-3 py-2 border">Status</th>
      <th class="px-3 py-2 border">Pax</th>
      <th class="px-3 py-2 border">Date Book</th>
    </tr>
  </thead>

  <tbody>
    <?php while($row = $recent_bookings->fetch_assoc()): ?>
    <?php 
      $status = strtolower($row['status'] ?? '');
      if($status === 'approved') $status_class = 'status-approved';
      elseif($status === 'pending') $status_class = 'status-pending';
      elseif($status === 'completed') $status_class = 'status-completed';
      elseif($status === 'cancelled') $status_class = 'status-cancelled';
      else $status_class = 'status-other';
    ?>
    
    <tr class="border">
      <!-- Reference Number with Active Badge -->
      <td class="px-3 py-2 border text-center">
        <span class="inline-flex items-center bg-yellow-200 text-green-800 text-xs font-semibold px-3 py-1 rounded-full border border-green-300 shadow-sm">
          <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
          <?= htmlspecialchars($row['reference_number']) ?>
        </span>
      </td>

      <td class="px-3 py-2 border"><?= htmlspecialchars($row['fullname']) ?></td>
      <td class="px-3 py-2 border"><?= htmlspecialchars($row['package_title']) ?></td>
      <td class="px-3 py-2 border text-center">
        <span class="<?= $status_class ?>"><?= ucfirst($status ?: 'Pending') ?></span>
      </td>
      <td class="px-3 py-2 border text-center"><?= intval($row['pax']) ?></td>
      <td class="px-3 py-2 border text-center"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
    </tr>
    <?php endwhile; ?>
  </tbody>
</table>


<script src="isset/script.js"></script>

</body>
</html>
