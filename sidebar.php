<?php
// Get sa pending QR payments nga count
$pending_qr_count = $conn->query("SELECT COUNT(*) AS total FROM pay_via_qr WHERE status='pending'")->fetch_assoc()['total'] ?? 0;
$unread_msg_count = $conn->query("SELECT COUNT(*) AS total FROM inquiries WHERE receiver_role='agency' AND status='unread'")->fetch_assoc()['total'] ?? 0;
?>

<!-- sidebar.php -->
<aside id="sidebar" class="bg-green-600 text-white fixed h-full w-64 flex flex-col z-50">
  <div class="p-6 flex flex-col items-center border-b border-green-500 relative">
    <img src="img/bee-logo.png" alt="Logo" class="h-16 w-16 rounded-full border-2 border-white mb-3">
    <h1 class="text-xl font-bold text-center">Tara sa Mis.Occ</h1>
    <span class="text-sm mt-1">Agency Dashboard</span>
    <button onclick="toggleSidebar()" class="absolute top-3 right-3 text-white hover:text-gray-200 md:block">
      <i id="closeIcon" class="fas fa-times"></i>
    </button>
  </div>

 <nav class="flex-1 p-4 space-y-2 overflow-y-auto bg-green-600 text-white">

  <a href="agency_dashboard.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-home w-6 text-center"></i> <span class="font-medium">Dashboard</span>
  </a>

  <a href="agency_add_package.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-box-open w-6 text-center"></i> <span class="font-medium">Packages</span>
  </a>

<a href="agency_qr_booking.php?status=pending" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition relative">
    <i class="fas fa-qrcode w-6 text-center"></i>
    <span class="font-medium">QR Payments</span>
    <?php if($pending_qr_count > 0): ?>
        <span class="absolute right-4 top-1 bg-red-600 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center">
            <?= $pending_qr_count ?>
        </span>
    <?php endif; ?>
</a>

  <a href="payments.php?status=received" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-credit-card w-6 text-center"></i> <span class="font-medium">PayMongo</span>
  </a>

   <a href="agency_registered_tourist.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-users w-6 text-center"></i> <span class="font-medium">Tourists</span>
  </a>
    
   <a href="agency_add_tourist_spots.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-map-marker-alt w-6 text-center"></i> <span class="font-medium">Tourist Spots</span>
  </a>

  <a href="agency_registered_tourist_spots.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition">
    <i class="fas fa-user-tie w-6 text-center"></i> <span class="font-medium">Spot Owners</span>
  </a>
   
  <a href="agency_messages.php" class="flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-green-600 transition relative">
    <i class="fas fa-envelope w-6 text-center"></i> 
    <span class="font-medium">Inquiries</span>
    <?php if($unread_msg_count > 0): ?>
        <span id="msg-badge" class="absolute top-1 right-4 bg-red-600 text-xs font-bold px-2 py-0.5 rounded-full flex items-center justify-center">
            <?= $unread_msg_count ?>
        </span>
    <?php endif; ?>
</a>

</nav>


 <!-- Logout nga Button -->
<div class="p-4 border-t border-green-500">
  <button onclick="openLogoutModal()" class="w-full flex items-center gap-2 justify-center px-4 py-2 bg-red-500 rounded hover:bg-red-600 transition">
    <i class="fas fa-sign-out-alt w-6 text-center"></i> Logout
  </button>
</div>



</aside>
</aside> 

<!-- Logout Modal (move OUTSIDE sidebar) -->
<div id="logoutModal" class="fixed inset-0 z-[60] hidden bg-black bg-opacity-50 flex items-center justify-center p-4">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-xs p-6 text-center">
    <h2 class="text-lg font-semibold mb-4 text-black">Confirm Logout</h2>
    <p class="text-gray-700 mb-6">Are you sure you want to logout?</p>
    <div class="flex justify-center gap-4">
      <button onclick="closeLogoutModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
      <a href="admin_login.php" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Logout</a>
    </div>
  </div>
</div>

<script>
function openLogoutModal() {
    document.getElementById('logoutModal').classList.remove('hidden');
    document.body.classList.add('overflow-hidden'); // Prevent background scroll
}

function closeLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}
</script>
