<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

// Handle mark as read sa message
if (isset($_GET['read_id'])) {
    $read_id = intval($_GET['read_id']);
    $conn->query("UPDATE inquiries SET status='read' WHERE id=$read_id");
    header("Location: agency_messages.php");
    exit;
}

// Handle delete code
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $conn->query("DELETE FROM inquiries WHERE id=$delete_id");
    header("Location: agency_messages.php");
    exit;
}

// Fetch inquiries with sender info including na ang tourism officers
$sql = "
SELECT i.*,
       CASE 
           WHEN LOWER(i.sender_role) = 'tourist' THEN t.fullname
           WHEN LOWER(i.sender_role) = 'spot_owner' THEN s.fullname
           WHEN LOWER(i.sender_role) = 'tourism_officers' THEN o.fullname
           ELSE i.sender_role
       END AS sender_name,
       CASE
           WHEN LOWER(i.sender_role) = 'tourist' THEN t.email
           WHEN LOWER(i.sender_role) = 'spot_owner' THEN s.email
           WHEN LOWER(i.sender_role) = 'tourism_officers' THEN o.email
           ELSE 'Tourism Staff'
       END AS sender_email,
       i.sender_role
FROM inquiries i
LEFT JOIN tourists t ON i.sender_role = 'tourist' AND i.sender_id = t.id
LEFT JOIN spot_owners s ON i.sender_role = 'spot_owner' AND i.sender_id = s.id
LEFT JOIN tourism_officers o ON i.sender_role = 'tourism_officers' AND i.sender_id = o.id
ORDER BY i.id DESC;
";

$result = $conn->query($sql);

// Helper function para ma display ang sender role nicely
function formatRole($role) {
    return ucfirst(str_replace('_', ' ', strtolower($role)));
}

// Function to return icon based sa ilang mga role
function roleIcon($role) {
    switch(strtolower($role)) {
        case 'tourist': return '👤';
        case 'spot_owner': return '🏠';
        case 'tourism_officers': return '🏛️';
        default: return '💬';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Agency Messages | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
<link rel="stylesheet" href="css/style.css">
</head>
  <body class="flex font-[Poppins]">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

   <div id="mainContent" class="flex-1">
    <main class="max-w-5xl mx-auto mt-8 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-3">
                <span>Inquiries</span>
            </h1>
        </div>
        <?php if ($result && $result->num_rows > 0): ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-6">
      <?php while($row = $result->fetch_assoc()): ?>
      <div class="bg-gradient-to-r from-green-100 to-green-50 shadow-lg rounded-xl overflow-hidden <?= ($row['status'] ?? 'unread')=='unread' ? 'border-l-4 border-yellow-400' : '' ?> max-w-xs mx-auto">
        <div class="card-header flex justify-between items-center p-4" onclick="toggleCard(this)">
          <div>
            <h2 class="text-md font-semibold text-gray-800">
              <?= roleIcon($row['sender_role']) ?> <?= htmlspecialchars($row['sender_name'] ?? 'No name') ?>
            </h2>
            <p class="text-sm text-gray-500">
              <?= htmlspecialchars($row['sender_email'] ?? 'No email found') ?>
            </p>
            <p class="text-xs text-gray-400"><?= formatRole($row['sender_role']) ?></p>
          </div>
          <span class="px-2 py-1 rounded text-xs <?= ($row['status'] ?? 'unread')=='unread' ? 'bg-yellow-400 text-gray-800' : 'bg-green-500 text-white' ?>">
            <?= ucfirst($row['status'] ?? 'unread') ?>
          </span>
        </div>
        <div class="card-body p-4 border-t border-gray-200 hidden">
          <p class="text-sm text-gray-500 mb-1">
            <strong>Subject:</strong> <?= htmlspecialchars($row['subject'] ?: 'No subject') ?>
          </p>
          <p class="text-sm text-gray-500 mb-2">
            Sent: <?= date('M d, Y H:i', strtotime($row['created_at'])) ?>
          </p>
          <p class="text-gray-700 mb-4 text-sm"><?= nl2br(htmlspecialchars($row['message'])) ?></p>
          <div class="flex gap-2 flex-wrap">
            <?php if(($row['status'] ?? 'unread')=='unread'): ?>
              <a href="?read_id=<?= $row['id'] ?>" 
                 class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition">
                 Mark as Read
              </a>
            <?php endif; ?>
            <button 
              class="deleteBtn bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition"
              data-id="<?= $row['id'] ?>">
              Delete
            </button>
          </div>
        </div>
      </div>
      <?php endwhile; ?>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div class="bg-white rounded-lg shadow-lg p-6 w-80 text-center">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Confirm Deletion</h2>
        <p class="text-gray-600 mb-5">Are you sure you want to delete this message?</p>
        <div class="flex justify-center gap-3">
          <button id="cancelDelete" 
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded transition">
            Cancel
          </button>
          <a id="confirmDelete" href="#" 
             class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition">
            Delete
          </a>
        </div>
      </div>
    </div>
    <?php else: ?>
      <p class="text-center text-gray-500 text-lg mt-6">No messages found.</p>
    <?php endif; ?>
  </main>
</div>


<script src="isset/ms.js"></script>
</body>
</html>
