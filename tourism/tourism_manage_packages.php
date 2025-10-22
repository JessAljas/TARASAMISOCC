<?php
session_start();
include '../config/db_connect.php';
include 'tourism_header.php';

// Only tourism officers
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

// Handle Approve / Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['package_id'], $_POST['action'])) {
    $package_id = intval($_POST['package_id']);
    $action = $_POST['action'];

    $stmt = $conn->prepare("UPDATE packages SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $action, $package_id);
    $stmt->execute();
    $stmt->close();
}

// Pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search & status filter
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$search_sql = "WHERE 1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $search_sql .= " AND (p.title LIKE '%$search%' OR ts.name_of_tourist_spot LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $status_filter = $conn->real_escape_string($status_filter);
    $search_sql .= " AND p.status = '$status_filter'";
}

// Count total packages
$count_sql = "
    SELECT COUNT(DISTINCT p.id) AS total
    FROM packages p
    LEFT JOIN package_destinations pd ON p.id = pd.package_id
    LEFT JOIN tourist_spots ts ON pd.tourist_spot_id = ts.id
    $search_sql
";
$total_packages = $conn->query($count_sql)->fetch_assoc()['total'];
$total_pages = ceil($total_packages / $limit);

// Fetch packages
$sql = "
    SELECT p.id, p.title, p.description, p.price, p.status, p.created_at,
           p.image1, p.image2, p.image3, p.image4,
           GROUP_CONCAT(ts.name_of_tourist_spot ORDER BY pd.stop_order SEPARATOR ', ') AS destinations,
           GROUP_CONCAT(ts.location ORDER BY pd.stop_order SEPARATOR ', ') AS locations
    FROM packages p
    LEFT JOIN package_destinations pd ON p.id = pd.package_id
    LEFT JOIN tourist_spots ts ON pd.tourist_spot_id = ts.id
    $search_sql
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);
$packages = $result->fetch_all(MYSQLI_ASSOC);

// Fetch status counts
$status_counts = $conn->query("
    SELECT status, COUNT(*) as total
    FROM packages
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);
$counts = ['pending'=>0, 'approved'=>0, 'rejected'=>0];
foreach($status_counts as $row){
    $counts[$row['status']] = $row['total'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Tour Packages</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-[Poppins] text-sm">

<!-- Header -->
<header class="text-black p-6 text-center mb-6">
    <h1 class="text-2xl font-bold">Manage Tour Packages</h1>
</header>

<div class="max-w-7xl mx-auto px-6">


    <!-- Status Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <a href="?status=pending" class="bg-yellow-300 text-yellow-800 p-6 rounded-lg shadow hover:bg-yellow-200 transition flex flex-col items-center">
            <i class="fas fa-hourglass-half text-3xl mb-2"></i>
            <span class="font-semibold text-lg">Pending</span>
            <span class="text-2xl font-bold"><?= $counts['pending'] ?></span>
        </a>
        <a href="?status=approved" class="bg-green-500 text-green-800 p-6 rounded-lg shadow hover:bg-green-200 transition flex flex-col items-center">
            <i class="fas fa-check-circle text-3xl mb-2"></i>
            <span class="font-semibold text-lg">Verified</span>
            <span class="text-2xl font-bold"><?= $counts['approved'] ?></span>
        </a>
        <a href="?status=rejected" class="bg-red-200 text-red-800 p-6 rounded-lg shadow hover:bg-red-200 transition flex flex-col items-center">
            <i class="fas fa-times-circle text-3xl mb-2"></i>
            <span class="font-semibold text-lg">Rejected</span>
            <span class="text-2xl font-bold"><?= $counts['rejected'] ?></span>
        </a>
    </div>

    <div class="flex justify-end mb-8">
  <div class="relative w-64">
    <input type="text" id="liveSearch" placeholder="Search packages or destinations..." 
           class="px-10 py-2 border rounded w-full focus:outline-none focus:ring-2 focus:ring-green-500">
    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
      <i class="fas fa-search"></i>
    </span>
  </div>
</div>

<!-- Packages Table -->
<div class="bg-white shadow rounded-lg overflow-hidden mb-8">
  <table id="packagesTable" class="w-full border-collapse text-center min-w-[600px]">
    <thead>
      <tr class="bg-green-300 uppercase tracking-wider">
        <th class="p-3">Title</th>
        <th class="p-3">Price</th>
        <th class="p-3">Status</th>
        <th class="p-3">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($packages as $pkg): ?>
      <tr class="border-b hover:bg-gray-50">
        <td class="p-2 font-semibold"><?= htmlspecialchars($pkg['title']) ?></td>
        <td class="p-2">₱<?= number_format($pkg['price'], 2) ?></td>
        <td class="p-2">
          <?php if ($pkg['status'] == 'pending'): ?>
            <span class="px-2 py-1 rounded bg-yellow-200 text-yellow-700 font-semibold">Pending</span>
          <?php elseif ($pkg['status'] == 'approved'): ?>
            <span class="px-2 py-1 rounded bg-green-300 text-green-700 font-semibold">Verified</span>
          <?php else: ?>
            <span class="px-2 py-1 rounded bg-red-200 text-red-700 font-semibold">Rejected</span>
          <?php endif; ?>
        </td>
        <td class="p-2">
          <button onclick='openModal(<?= json_encode($pkg) ?>)' 
                  class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 transition">
            View
          </button>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($packages)): ?>
      <tr>
        <td colspan="4" class="p-4 text-gray-500">No packages found.</td>
      </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
<script>
const searchInput = document.getElementById("liveSearch");
const table = document.getElementById("packagesTable");
const rows = table.getElementsByTagName("tbody")[0].getElementsByTagName("tr");

searchInput.addEventListener("input", function() {
  const filter = this.value.toLowerCase();
  Array.from(rows).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(filter) ? "" : "none";
  });
});
</script>
    <!-- Pagination -->
    <div class="flex justify-between items-center mb-8">
        <p class="text-gray-700">
            Showing <?= count($packages) ?> of <?= $total_packages ?> results
        </p>
        <div class="flex space-x-2">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($status_filter) ?>" 
                   class="px-4 py-2 border rounded <?= $i == $page ? 'bg-green-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    </div>

<!-- Modal -->
<div id="viewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl w-full max-w-3xl p-6 relative text-sm max-h-[80vh] overflow-y-auto">
        <h2 class="text-xl font-bold mb-4">Package Details</h2>
        <form method="POST">
            <input type="hidden" name="package_id" id="modal_package_id">
            <p><strong>Title:</strong> <span id="modal_title"></span></p>
            <p><strong>Description:</strong></p>
            <p id="modal_description" class="text-gray-700 mb-3"></p>
            <p><strong>Price:</strong> ₱<span id="modal_price"></span></p>
            <p><strong>Locations:</strong> <span id="modal_locations"></span></p>
            <p><strong>Destinations:</strong> <span id="modal_destinations"></span></p>
            <p><strong>Status:</strong> <span id="modal_status"></span></p>
            <p><strong>Date Posted:</strong> <span id="modal_created_at"></span></p>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
                <img id="modal_image1" class="w-full h-32 object-cover rounded shadow" alt="Image 1">
                <img id="modal_image2" class="w-full h-32 object-cover rounded shadow" alt="Image 2">
                <img id="modal_image3" class="w-full h-32 object-cover rounded shadow" alt="Image 3">
                <img id="modal_image4" class="w-full h-32 object-cover rounded shadow" alt="Image 4">
            </div>

            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="closeModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Close</button>
                <button type="submit" name="action" value="rejected" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Reject</button>
                <button type="submit" name="action" value="approved" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Verify</button>
            </div>
        </form>
    </div>
</div>

<script src="js/package.js"></script>
</body>
</html>
