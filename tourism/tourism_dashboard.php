<?php
session_start();
include '../config/db_connect.php';

// Only ang allow is tourism officers
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

// ==================== HANDLE AJAX VERIFY ug REJECT ====================
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'], $_POST['id'])){
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    // Convert action into sa proper nga status
    if($action==='verify') $status = 'verified';
    elseif($action==='reject') $status = 'rejected';
    else exit; 

    $stmt = $conn->prepare("UPDATE tourist_spots SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $stmt->close();

    // Return updated nga counts sa card
    $counts = [
        'verified'=>$conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='verified'")->fetch_assoc()['total'],
        'pending'=>$conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='pending'")->fetch_assoc()['total'],
        'rejected'=>$conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='rejected'")->fetch_assoc()['total']
    ];
    echo json_encode($counts);
    exit;
}

    // Mo count sa Verified, Pending, ug Rejected spots
    $countVerified = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='verified'")->fetch_assoc()['total'];
    $countPending  = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='pending'")->fetch_assoc()['total'];
    $countRejected = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='rejected'")->fetch_assoc()['total'];

    // ✅ Mo count pud sa pending packages
    $pending_count = $conn->query("SELECT COUNT(*) AS total FROM packages WHERE status='pending'")->fetch_assoc()['total'];

// ==================== HANDLE AJAX SA PAG SEND MESSAGE OR INQUIRIES ====================
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subject'], $_POST['message'], $_POST['sender_id'], $_POST['sender_role'], $_POST['receiver_role'])){
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    $sender_id = intval($_POST['sender_id']);
    $sender_role = $_POST['sender_role'];
    $receiver_role = $_POST['receiver_role'];

    $response = ['success'=>false];

    if($subject && $message && $sender_id && $sender_role && $receiver_role){
        $stmt = $conn->prepare("INSERT INTO inquiries (sender_id, sender_role, receiver_role, subject, message, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("issss", $sender_id, $sender_role, $receiver_role, $subject, $message);
        if($stmt->execute()) $response['success'] = true;
        else $response['error'] = $stmt->error;
        $stmt->close();
    } else {
        $response['error'] = 'All fields are required.';
    }

    echo json_encode($response);
    exit;
}

// ==================== Ensure nga ang new spots naay 'pending' nga status ====================
$conn->query("UPDATE tourist_spots SET status='pending' WHERE status IS NULL");

// Mo count sa Verified, Pending, ug Rejected
$countVerified = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='verified'")->fetch_assoc()['total'];
$countPending = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='pending'")->fetch_assoc()['total'];
$countRejected = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots WHERE status='rejected'")->fetch_assoc()['total'];

// Pagination nga setup
$limit = 6;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filter nga status katung eslect sa dropdown
$filter_status = $_GET['status'] ?? 'all';
$whereClause = "WHERE 1";
if ($filter_status === 'pending') $whereClause .= " AND status='pending'";
elseif ($filter_status === 'verified') $whereClause .= " AND status='verified'";
elseif ($filter_status === 'rejected') $whereClause .= " AND status='rejected'";

// Fetch filtered spots gamit ang owner_name gikan sa table directly
$res = $conn->query("
    SELECT ts.*, ts.owner_name AS posted_by_name
    FROM tourist_spots ts
    $whereClause
    ORDER BY ts.created_at DESC
    LIMIT $limit OFFSET $offset
");


$spots = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $row['status'] = $row['status'] ? $row['status'] : 'pending';
        $row['images'] = [];
        if(!empty($row['image1'])) $row['images'][] = '../uploads/'.$row['image1'];
        if(!empty($row['image2'])) $row['images'][] = '../uploads/'.$row['image2'];
        if(!empty($row['image3'])) $row['images'][] = '../uploads/'.$row['image3'];
        $spots[] = $row;
    }
}

$totalSpots = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots ts $whereClause")->fetch_assoc()['total'];
$totalPages = ceil($totalSpots / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourism Dashboard | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<style>
    #spotModal .modal-content { max-height: 85vh; overflow-y: auto; }
    #messageModal .modal-content { max-height: 70vh; overflow-y: auto; }
</style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-[Poppins]">

<div class="container mx-auto p-6 flex-1">

<!-- Header with Profile nga Dropdown -->
<h1 class="text-2xl font-bold mb-6 flex items-center justify-between">
    <span>Tourism Officer Dashboard</span>
    <div class="flex items-center space-x-3 relative">
        <!-- Add Spot Button -->
        <a href="tourism_add_spots.php" 
           class="bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700 transition">
            + Add Spot
        </a>

       <!-- Manage Packages Button with Badge -->
    <a href="tourism_manage_packages.php" 
    class="relative bg-green-600 text-white text-sm px-3 py-1 rounded hover:bg-green-700 transition flex items-center">
        Manage Packages
        <!-- Badge -->
        <span class="ml-2 bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full">
            <?= $pending_count ?? 0 ?>
        </span>
    </a>

        <!-- Profile Dropdown -->
        <div class="relative">
            <button id="profileBtn" onclick="toggleProfileDropdown()" 
                    class="flex items-center space-x-2 bg-gray-200 hover:bg-gray-300 px-3 py-1.5 rounded transition text-base">
                <i class="fas fa-user text-gray-700 text-base"></i>
                <span class="text-gray-800"><?= htmlspecialchars($_SESSION['user']['fullname'] ?? 'Tourism Staff') ?></span>
                <i class="fas fa-caret-down text-gray-600 text-base"></i>
            </button>

            <!-- Dropdown Menu -->
            <div id="profileDropdown" 
                 class="absolute right-0 mt-2 w-44 bg-white shadow-lg rounded hidden z-50">
                <a href="tourism_profile.php" 
                   class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                    <i class="fas fa-id-badge text-base"></i> View Profile
                </a>
                <a href="javascript:void(0)" onclick="openMessageModal()" 
                   class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                    <i class="fas fa-envelope text-base"></i> Message
                </a>
                <a href="javascript:void(0)" onclick="openLogoutModal()" 
                   class="flex items-center gap-2 px-3 py-2 text-gray-700 hover:bg-gray-100 text-sm">
                    <i class="fas fa-sign-out-alt text-base"></i> Logout
                </a>
            </div>
        </div>
    </div>
</h1>


<!-- Stats -->
<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <!-- Verified Spots -->
    <div class="bg-green-100 p-4 rounded shadow text-center flex flex-col items-center">
        <i class="fas fa-map-marker-alt text-green-600 text-3xl mb-2"></i>
        <h3 class="font-semibold text-lg text-green-800">Verified Spots</h3>
        <p class="text-2xl font-bold text-green-600 mt-2" id="countVerified"><?= $countVerified ?></p>
    </div>

    <!-- Pending Spots -->
    <div class="bg-yellow-100 p-4 rounded shadow text-center flex flex-col items-center">
        <i class="fas fa-hourglass-half text-yellow-600 text-3xl mb-2"></i>
        <h3 class="font-semibold text-lg text-yellow-800">Pending Spots</h3>
        <p class="text-2xl font-bold text-yellow-600 mt-2" id="countPending"><?= $countPending ?></p>
    </div>

    <!-- Rejected Spots -->
    <div class="bg-red-100 p-4 rounded shadow text-center flex flex-col items-center">
        <i class="fas fa-times-circle text-red-600 text-3xl mb-2"></i>
        <h3 class="font-semibold text-lg text-red-800">Rejected Spots</h3>
        <p class="text-2xl font-bold text-red-600 mt-2" id="countRejected"><?= $countRejected ?></p>
    </div>
</div>


<!-- Filter -->
<div class="mb-4">
<form method="GET" class="flex items-center space-x-2">
    <label class="font-semibold">Filter by Status:</label>
    <select name="status" class="border rounded px-2 py-1" onchange="this.form.submit()">
        <option value="all" <?= $filter_status==='all'?'selected':'' ?>>All</option>
        <option value="pending" <?= $filter_status==='pending'?'selected':'' ?>>Pending</option>
        <option value="verified" <?= $filter_status==='verified'?'selected':'' ?>>Verified</option>
        <option value="rejected" <?= $filter_status==='rejected'?'selected':'' ?>>Rejected</option>
    </select>
</form>
</div>

<!-- Spots Table -->
<div class="overflow-x-auto">
<table class="min-w-full bg-white shadow rounded-lg">
<thead class="bg-yellow-600 text-black">
<tr>
    <th class="py-2 px-4 text-left">#</th>
    <th class="py-2 px-4 text-left">Spot Name</th>
    <th class="py-2 px-4 text-left">Location</th>
    <th class="py-2 px-4 text-left">Posted By</th>
    <th class="py-2 px-4 text-left">Status</th>
    <th class="py-2 px-4 text-left">Action</th>
</tr>
</thead>
<tbody id="spotsTableBody">
<?php $i = $offset+1; foreach($spots as $spot): ?>
<tr class="border-b <?= ($spot['status'] !== 'verified') ? 'bg-red-50' : '' ?>" data-id="<?= $spot['id'] ?>">
    <td class="py-4 px-6 font-medium"><?= $i++ ?></td>
    <td class="py-4 px-6"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
    <td class="py-4 px-6"><?= htmlspecialchars($spot['location']) ?></td>
    <td class="py-4 px-6"><?= htmlspecialchars($spot['posted_by_name'] ?? 'N/A') ?></td>
    <td class="py-4 px-6 statusCell">
        <?php if ($spot['status']==='verified'): ?>
            <span class="px-4 py-2 rounded-full text-lg font-bold bg-green-100 text-green-700">
                Verified
            </span>
        <?php elseif ($spot['status']==='rejected'): ?>
            <span class="px-4 py-2 rounded-full text-lg font-bold bg-red-100 text-red-700">
                Rejected
            </span>
        <?php else: ?>
            <span class="px-4 py-2 rounded-full text-lg font-bold bg-yellow-100 text-yellow-700">
                Pending
            </span>
        <?php endif; ?>
    </td>
    <td class="py-4 px-6">
        <button onclick="openModal(<?= $spot['id'] ?>)" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-yellow-500">
            View
        </button>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- Pagination -->
<div class="mt-4 flex justify-center space-x-2">
    <?php if($page>1): ?><a href="?status=<?= $filter_status ?>&page=<?= $page-1 ?>" class="text-blue-600 underline transition">Previous</a><?php endif; ?>
    <?php for($p=1;$p<=$totalPages;$p++):
        $active = $p==$page?' text-black':'text-black'; ?>
        <a href="?status=<?= $filter_status ?>&page=<?= $p ?>" class="px-3 py-1 rounded <?= $active ?>"><?= $p ?></a>
    <?php endfor; ?>
    <?php if($page<$totalPages): ?><a href="?status=<?= $filter_status ?>&page=<?= $page+1 ?>" class="text-blue-600 underline transition">Next</a><?php endif; ?>
</div>
</div>

<!-- Spot Modal -->
<div id="spotModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
<div class="bg-white rounded-lg shadow-lg p-6 w-11/12 md:w-3/4 lg:w-2/3 relative modal-content overflow-y-auto">
    <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">
        <i class="fas fa-times"></i>
    </button>
    <h2 class="text-xl font-bold mb-4" id="modalName"></h2>
    <p><strong>Location:</strong> <span id="modalLocation"></span></p>
    <p><strong>Posted By:</strong> <span id="modalPostedBy"></span></p>
    <p><strong>Description:</strong> <span id="modalDescription"></span></p>
    <p><strong>Entrance Fee:</strong> <span id="modalFee"></span></p>
    <p><strong>Status:</strong> <span id="modalStatus"></span></p>
    <div class="flex flex-wrap gap-2 mt-2" id="modalImages"></div>
    <div id="modalMap" class="w-full h-64 mt-4 rounded"></div>
    <div class="mt-4 flex justify-end space-x-2" id="modalButtons"></div>
</div>
</div>

<!-- Messages Modal -->
<div id="messageModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 md:w-1/2 lg:w-1/3 relative modal-content">
    <button onclick="closeMessageModal()" class="absolute top-2 right-2 text-gray-500 hover:text-gray-800">
        <i class="fas fa-times"></i>
    </button>
    <h2 class="text-xl font-bold mb-4">Send Message to Agency</h2>
    <form id="messageForm">
        <div class="mb-3">
            <label class="block font-semibold mb-1">Subject:</label>
            <input type="text" name="subject" class="w-full border rounded px-3 py-2" required>
        </div>
        <div class="mb-3">
            <label class="block font-semibold mb-1">Message</label>
            <textarea name="message" rows="5" class="w-full border rounded px-3 py-2" required></textarea>
        </div>
        <div class="flex justify-end space-x-2">
    <button type="button" onclick="closeMessageModal()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
        Cancel
    </button>
    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded flex items-center space-x-2 hover:bg-green-700">
        <i class="fas fa-paper-plane"></i>
        <span>Send</span>
    </button>
    </div>
    </form>
  </div>
</div>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden flex items-center justify-center z-50">
  <div class="bg-white p-6 rounded shadow-lg w-80">
    <h2 class="text-lg font-bold mb-4">Confirm Logout</h2>
    <p class="mb-4">Are you sure you want to log out?</p>
    <div class="flex justify-end space-x-3">
      <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
      <a href="../index.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Logout</a>
    </div>
  </div>
</div>

<!-- FOOTER -->
<footer class="bg-green-500 text-white py-6 shadow-inner mt-auto">
  <div class="container mx-auto flex flex-col items-center space-y-4 text-center px-4">
    <div class="flex flex-col items-center space-y-2">
      <div class="flex items-center space-x-2">
        <img src="../img/bee-logo.png" alt="Bee Tours Logo" class="w-12 h-12">
        <h5 class="font-semibold text-lg">Travel Bee Tours</h5>
      </div>
      <div class="flex items-center space-x-2 mt-2">
        <img src="../img/prov-logo.png" alt="Province Logo" class="w-10 h-10">
        <p class="text-sm md:text-base font-medium max-w-xs md:max-w-md">
          Misamisnon Magpuyong Malinawon Malamboon ug Malipayon
        </p>
      </div>
    </div>
    <p class="text-sm mt-2">© 2025 Tara sa Mis.Occ. All rights reserved.</p>
  </div>
</footer>

<script id="spotsData" type="application/json"><?= json_encode($spots) ?></script>
<span id="userId" class="hidden"><?= $_SESSION['user']['id'] ?></span>

<script src="js/main.js"></script>

</body>
</html>
