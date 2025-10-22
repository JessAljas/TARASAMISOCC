<?php
// spot_owner_header.php
?>
<header class="bg-green-500 text-white shadow">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex justify-between items-center h-20 py-4">
    <!-- Logo + Title -->
    <div class="flex items-center gap-3 text-3xl font-bold">
      <img src="../img/spt.png" alt="Logo" class="w-16 h-16 rounded-full object-cover">
      <span class="text-yellow-300">𝓜𝔂 𝓣𝓸𝓾𝓻𝓲𝓼𝓽 𝓢𝓹𝓸𝓽𝓼</span>
    </div>

    <!-- Navbar Links -->
    <nav class="flex gap-4 text-lg">
      <a href="tourist_spot_owner_dashboard.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-home"></i> HOME
      </a>
      <a href="add.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-plus"></i> ADD
      </a>
      <a href="tourist_spot_manage.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-edit"></i> MANAGE
      </a>
      <button onclick="openLogoutModal()" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-sign-out-alt"></i> LOGOUT
      </button>
    </nav>
  </div>
</header>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center" role="dialog" aria-modal="true">
  <div class="bg-white p-6 rounded w-[95%] max-w-md mx-auto text-center relative">
    <button onclick="closeLogoutModal()" class="absolute top-2 right-2 text-gray-600"><i class="fas fa-times"></i></button>
    <h2 class="text-lg font-semibold mb-4 text-red-600">Confirm Logout</h2>
    <p class="mb-4">Are you sure you want to log out?</p>
    <div class="flex justify-center gap-4">
      <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
      <a href="../config/logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">Logout</a>
    </div>
  </div>
</div>

<script>
function openLogoutModal() {
  document.getElementById('logoutModal').classList.remove('hidden');
}
function closeLogoutModal() {
  document.getElementById('logoutModal').classList.add('hidden');
}
</script>
