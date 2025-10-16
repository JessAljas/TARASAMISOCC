<?php
include 'config/db_connect.php'; // The database connection file
?>

<!-- Footer -->
<footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <!-- Logo -->
  <div class="flex flex-col items-center space-y-2">
    <div class="flex space-x-2">
      <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
      <img src="img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
    </div>
    <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
  </div>


    <!-- Navigation Links -->
    <div class="flex flex-wrap justify-center gap-4 text-white text-base">
      <a href="Homepage.php" class="hover:text-orange-600 transition">Home</a>
      <a href="explore.php" class="hover:text-orange-600 transition">Explore</a>
      <a href="package.php" class="hover:text-orange-600 transition">Packages</a>
      <a href="contact.php" class="hover:text-orange-600 transition">Contact</a>
      <a href="services.php" class="hover:text-orange-600 transition">Services</a>
    </div>

   <!-- Social Media & Contact Icons -->
<div class="flex flex-wrap items-center gap-4 text-base text-white">
  <!-- Facebook -->
  <a href="https://www.facebook.com/TravelBeeTour" target="_blank"
     class="flex items-center gap-2 hover:text-blue-600 transition">
    <i class="fa-brands fa-facebook-f"></i>
    <span>TravelBeeTour</span>
  </a>

  <!-- WhatsApp -->
  <a href="https://wa.me/639817127702" target="_blank"
     class="flex items-center gap-2 hover:text-red-500 transition">
    <i class="fa-brands fa-whatsapp"></i>
    <span>0981 712 7702</span>
  </a>

  <!-- Mobile -->
  <a href="tel:+639518835462"
     class="flex items-center gap-2 hover:text-yellow-400 transition">
    <i class="fa-solid fa-mobile-screen"></i>
    <span>0951 883 5462</span>
  </a>

  <!-- Telephone -->
  <a href="tel:0885217270"
     class="flex items-center gap-2 hover:text-blue-400 transition">
    <i class="fa-solid fa-phone"></i>
    <span>(088) 521-7270</span>
  </a>
</div>


  <!-- Footer Bottom -->
  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
</footer>

<!-- Font Awesome CDN NGA LINK PARA MO GANA ANG MGA ICONS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>
