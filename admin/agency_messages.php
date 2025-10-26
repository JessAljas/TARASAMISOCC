<?php
session_start();
include '../config/db_connect.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: admin_login.php");
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
<main class="flex flex-col h-[90vh] bg-gray-50 rounded-lg shadow-md overflow-hidden">
  <div class="flex flex-1">
    <!-- LEFT SIDEBAR: Conversation List -->
    <div class="w-1/3 bg-white border-r border-gray-200 overflow-y-auto">
      <h2 class="text-xl font-semibold p-4 border-b bg-green-500 text-white">Messages</h2>
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while($row = $result->fetch_assoc()): ?>
          <div 
            class="p-4 border-b hover:bg-green-50 cursor-pointer transition"
            onclick="openChat(<?= $row['id'] ?>, '<?= htmlspecialchars(addslashes($row['sender_name'])) ?>', '<?= htmlspecialchars(addslashes($row['sender_email'])) ?>', '<?= htmlspecialchars(addslashes($row['message'])) ?>', '<?= htmlspecialchars(addslashes($row['subject'])) ?>', '<?= date('M d, Y H:i', strtotime($row['created_at'])) ?>', '<?= $row['status'] ?>')"
          >
            <div class="flex items-center justify-between">
              <div class="flex flex-col">
                <span class="font-semibold text-gray-800"><?= roleIcon($row['sender_role']) ?> <?= htmlspecialchars($row['sender_name'] ?? 'No name') ?></span>
                <span class="text-xs text-gray-500 truncate"><?= htmlspecialchars($row['sender_email'] ?? 'No email') ?></span>
              </div>
              <span class="text-xs <?= ($row['status'] ?? 'unread')=='unread' ? 'bg-yellow-300 text-gray-800' : 'bg-green-500 text-white' ?> px-2 py-1 rounded-full">
                <?= ucfirst($row['status'] ?? 'unread') ?>
              </span>
            </div>
            <p class="text-sm text-gray-600 mt-1 line-clamp-1"><?= htmlspecialchars($row['message']) ?></p>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center text-gray-500 mt-10">No messages found.</p>
      <?php endif; ?>
    </div>

    <!-- RIGHT CHAT AREA -->
<div class="flex-1 bg-gray-100 flex flex-col justify-between">
  <!-- Header -->
  <div id="chatHeader" class="p-3 border-b bg-white hidden">
    <h2 class="text-base font-semibold text-gray-800" id="chatName"></h2>
    <p class="text-xs text-gray-500" id="chatEmail"></p>
  </div>
  
  <!-- Body -->
  <div id="chatBody" class="flex-1 overflow-y-auto p-4 hidden">
    <div class="bg-green-100 p-3 rounded-md mb-3">
      <p class="text-gray-700 text-sm" id="chatSubject"></p>
      <p class="text-xs text-gray-500" id="chatDate"></p>
    </div>
    <div class="bg-white p-3 rounded-md shadow">
      <p class="text-gray-700 text-sm" id="chatMessage"></p>
    </div>
  </div>

  <!-- Footer -->
  <div id="chatFooter" class="p-3 border-t bg-white flex justify-end gap-2 hidden">
    <a id="markReadBtn" href="#" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-sm">Mark as Read</a>
    <button id="deleteBtn" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-sm">Delete</button>
  </div>
</div>

  </div>
</main>

</div>

<script>
function openChat(id, name, email, message, subject, date, status) {
  document.getElementById('chatHeader').classList.remove('hidden');
  document.getElementById('chatBody').classList.remove('hidden');
  document.getElementById('chatFooter').classList.remove('hidden');

  document.getElementById('chatName').innerText = name;
  document.getElementById('chatEmail').innerText = email;
  document.getElementById('chatSubject').innerText = "Subject: " + (subject || "No subject");
  document.getElementById('chatDate').innerText = "Sent: " + date;
  document.getElementById('chatMessage').innerText = message;

  document.getElementById('markReadBtn').href = "?read_id=" + id;
  document.getElementById('deleteBtn').onclick = function() {
    if(confirm("Are you sure you want to delete this message?")) {
      window.location.href = "?delete_id=" + id;
    }
  };
}
</script>

<script src="isset/ms.js"></script>
</body>
</html>
