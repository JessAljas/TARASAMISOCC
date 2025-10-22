<!-- tourism_header.php -->
<header class="bg-green-500 text-white shadow w-full">
  <div class="flex justify-between items-center h-20 px-6 py-4">
    <!-- Logo + Title -->
    <div class="flex items-center gap-3 text-3xl font-bold">
      <img src="../img/prov-logo.png" alt="Logo" class="w-16 h-16 rounded-full object-cover">
      <h1 style="font-family: 'cursive', 'Brush Script MT', 'Lucida Handwriting'; color: yellow">Tourism Dashboard</h1>
    </div>

    <!-- Navbar Links -->
    <nav class="flex gap-4 text-sm">
      <a href="staff_dashboard.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-home"></i> HOME
      </a>
      <a href="tourism_add_spots.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-plus"></i> ADD
      </a>
      <a href="tourism_manage_spots.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-edit"></i> MANAGE TOURIST SPOTS
      </a>
      <a href="tourism_manage_packages.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-edit"></i> MANAGE PACKAGES
      </a>
      <a href="manage_request.php" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-edit"></i> MANAGE REQUESTS
      </a>
      <button onclick="openLogoutModal()" class="flex items-center gap-1 px-3 py-2 rounded hover:bg-green-600">
        <i class="fas fa-sign-out-alt"></i> LOGOUT
      </button>
    </nav>
  </div>
</header>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
  <div class="bg-white rounded-lg p-6 max-w-sm w-full text-center relative">
    <button class="absolute top-2 right-2 text-gray-500 hover:text-gray-700" onclick="closeLogoutModal()">
      <i class="fas fa-times"></i>
    </button>
    <h2 class="text-xl font-bold mb-4">Confirm Logout</h2>
    <p class="mb-6 text-gray-600">Are you sure you want to logout?</p>
    <div class="flex justify-center gap-4">
      <a href="../config/logout.php" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Yes, Logout</a>
      <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400">Cancel</button>
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
