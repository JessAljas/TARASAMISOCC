<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tara sa MisOcc.com</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<style>
  /* Fade-in animation */
  @keyframes fadeInUp {
    0% { opacity: 0; transform: translateY(20px); }
    100% { opacity: 1; transform: translateY(0); }
  }
  .fade-in-up {
    animation: fadeInUp 1s ease-out forwards;
  }

  /* Slow bounce sa logo */
  @keyframes bounce-slow {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
  }
  .bounce-slow {
    animation: bounce-slow 2s infinite;
  }

  /* Gradient text highlight */
  .text-gradient {
    background: linear-gradient(to right, #16a34a, #22c55e);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }
</style>
</head>
<body class="bg-gradient-to-b from-green-50 via-green-100 to-white min-h-screen flex flex-col font-[Poppins]">

  <!-- Main content -->
  <main class="flex-1 flex items-center justify-center px-5 py-16">
    <div class="flex flex-col-reverse lg:flex-row items-center justify-between max-w-6xl w-full gap-12">

      <!-- Text Section sa Left -->
      <div class="flex flex-col items-start gap-6 fade-in-up max-w-xl lg:max-w-2xl">
        <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gradient">
          Tara sa Mis Occ!
        </h1>
        <p class="text-gray-700 text-lg sm:text-xl leading-relaxed">
          <strong>Tara sa Mis.Occ</strong> makes exploring Misamis Occidental seamless, fast, and enjoyable. Whether you're a tourist seeking exciting destinations, a local business promoting services, or an agency managing tour packages, our platform brings everything together in one place.
          <br><br>
          Plan trips with real-time booking, interactive maps, curated itineraries, and secure payments. Discover hidden gems, book packages instantly, and create unforgettable memories with ease.
        </p>

        <!-- Learn ng Button -->
        <a href="login.php" 
           class="bg-green-600 text-white px-8 py-3 rounded-full font-semibold hover:bg-green-700 shadow-lg transition transform hover:scale-105 inline-block text-center mt-4">
          Learn More
        </a>
      </div>

      <!-- Logo nga Illustration sa (Right) -->
      <div class="fade-in-up flex justify-center lg:justify-end w-full lg:w-1/2">
        <img src="img/logo.png" alt="Software Developer Illustration" 
             class="w-64 h-64 sm:w-72 sm:h-72 md:w-80 md:h-80 lg:w-96 lg:h-96 rounded-full shadow-2xl border-4 border-green-200 object-cover bounce-slow">
      </div>

    </div>
  </main>

<!-- Footer -->
<footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <!-- Logo -->
    <div class="flex flex-col items-center space-y-2">
      <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
      <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
    </div>

    <!-- Navigation Links -->
    <div class="flex flex-wrap justify-center gap-4 text-white text-base">
      <a href="index.php" class="hover:text-blue-700 transition">Home</a>
      <a href="login.php" class="hover:text-blue-700 transition">Explore</a>
      <a href="login.php" class="hover:text-blue-700 transition">Packages</a>
      <a href="login.php" class="hover:text-blue-700 transition">Contact</a>
    </div>

    <!-- Social Media Icons -->
    <div class="flex space-x-4 text-xl text-white">
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-twitter"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-youtube"></i></a>
    </div>

  </div>

  <!-- Footer Bottom -->
  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
</footer>

<!-- Font Awesome CDN nga link -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

</body>
</html>
