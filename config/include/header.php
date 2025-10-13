<?php
$base = $base ?? ''; 

if (session_status() === PHP_SESSION_NONE) session_start();
include 'config/db_connect.php'; 

// Check if user is logged in
$tourist_id = $_SESSION['user']['id'] ?? null;
?>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<header class="bg-green-500 text-white shadow-md sticky top-0 z-50 font-[Poppins]">
  <div class="flex items-center justify-between h-20 px-4 sm:px-6 lg:px-8">

    <!-- Logo in Header -->
    <div class="flex items-center space-x-4">
      <img src="<?= $base ?>img/logo.png" alt="Logo" class="h-12 w-12 rounded-full border-2 border-white shadow-md" />
      <span class="text-xl font-bold">Tara sa Mis.Occ</span>
    </div>

    <!-- Navigation -->
    <nav class="hidden md:flex items-center space-x-8 text-white text-sm font-medium">
      <a href="<?= $base ?>Homepage.php" class="flex items-center hover:text-yellow-300"><i class="fas fa-home mr-2"></i>HOME</a>
      <a href="<?= $base ?>package.php" class="flex items-center hover:text-yellow-300"><i class="fas fa-box-open mr-2"></i>PACKAGES</a>
      <a href="<?= $base ?>services.php" class="flex items-center hover:text-yellow-300"><i class="fas fa-concierge-bell mr-2"></i>SERVICES</a>
      <a href="<?= $base ?>contact.php" class="flex items-center hover:text-yellow-300"><i class="fas fa-envelope mr-2"></i>CONTACT</a>
    </nav>

    <!-- Profile, Search, Logout -->
    <div class="flex items-center space-x-4">
      <!-- Profile -->
      <?php if ($tourist_id): ?>
        <a href="<?= $base ?>tourist_profile.php" class="flex items-center hover:text-yellow-300">
          <i class="fas fa-user-circle text-3xl"></i>
        </a>
      <?php endif; ?>

      <!-- Search -->
      <form action="<?= $base ?>search.php" method="get" class="relative flex items-center bg-white rounded-full px-3 py-1 w-40 sm:w-60 lg:w-72">
        <input name="q" type="text" placeholder="Search..." class="text-sm text-gray-700 outline-none px-2 py-1 bg-transparent w-full" autocomplete="off" required />
        <button type="submit" class="text-blue-900 hover:text-yellow-500"><i class="fas fa-search"></i></button>
      </form>

      <!-- Logout -->
      <?php if ($tourist_id): ?>
        <button onclick="openLogoutModal()" class="flex items-center text-red-200 hover:text-red-400">
          <i class="fas fa-sign-out-alt mr-2"></i>LOGOUT
        </button>
      <?php endif; ?>
    </div>
  </div>
</header>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center transition-opacity duration-300 ease-in-out opacity-0">
  <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Confirm Logout</h2>
    <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
    <div class="flex justify-end space-x-3">
      <button onclick="closeLogoutModal()" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Cancel</button>
      <a href="<?= $base ?>logout.php" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Logout</a>
    </div>
  </div>
</div>

<script src="js/explo-details.js"></script>
