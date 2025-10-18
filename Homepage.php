<?php
session_start();

// Only allow logged-in tourists
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist') {
  header("Location: login.php");
  exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Homepage | Tara sa Mis.Occ</title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer"/>
<link href="https://fonts.googleapis.com/css2?family=Great+Vibes&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link rel="stylesheet" href="config/css/style.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="font-[Poppins] bg-gray-100">

  <!-- Header -->
  <div class="fixed top-0 left-0 w-full z-50 shadow-md bg-white">
    <?php include 'config/include/header.php'; ?>
  </div>

  <!-- Hero Section -->
  <section class="hero-section relative flex items-center justify-center text-center min-h-[70vh]">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative max-w-3xl mx-4 sm:mx-auto p-6 sm:p-8 rounded-2xl bg-black/30 shadow-lg">
      <h1 class="script-title text-white text-3xl sm:text-4xl md:text-5xl font-bold mb-4 drop-shadow-lg">
        Explore Misamis Occidental
      </h1>
      <p class="text-white text-sm sm:text-base md:text-lg mb-6 drop-shadow-lg">
        Discover beautiful destinations and exciting adventures across the province.
      </p>
      <a href="explore.php" class="inline-block bg-yellow-400 text-blue-900 font-semibold px-6 sm:px-8 py-2 sm:py-3 rounded-full shadow-lg hover:bg-yellow-500 transition duration-300 text-sm sm:text-base">
        <i class="fas fa-map-marked-alt mr-2"></i>Discover Top Destinations
      </a>
    </div>
  </section>

  <!-- Featured Tours & Services Section -->
  <section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6">
      <h2 class="text-3xl sm:text-4xl font-bold text-center text-yellow-600 mb-8 sm:mb-12">Featured Tours & Services</h2>
      <div class="flex flex-col sm:flex-row sm:flex-wrap gap-6 overflow-x-auto sm:overflow-x-visible">
        <?php
        $services = [
          ['icon'=>'fas fa-mountain','title'=>'Adventure Tours','desc'=>'Explore breathtaking mountains, waterfalls, and hidden gems.'],
          ['icon'=>'fas fa-utensils','title'=>'Local Cuisine','desc'=>'Taste authentic dishes and delicacies.'],
          ['icon'=>'fas fa-bus','title'=>'Transport Services','desc'=>'Reliable transport options for your tours.'],
          ['icon'=>'fas fa-camera','title'=>'Photography Tours','desc'=>'Capture scenic moments guided by professionals.'],
          ['icon'=>'fas fa-handshake','title'=>'Guided Tours','desc'=>'Expert guides for a memorable experience.']
        ];
        foreach($services as $service):
        ?>
        <div class="min-w-[250px] flex-1 bg-white p-5 sm:p-6 rounded-2xl shadow-md hover:shadow-xl transition duration-300 text-center">
          <div class="text-yellow-500 text-3xl sm:text-4xl mb-3"><i class="<?= $service['icon'] ?>"></i></div>
          <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-2"><?= $service['title'] ?></h3>
          <p class="text-gray-600 text-sm sm:text-base"><?= $service['desc'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Map Section -->
  <section class="w-full py-8 flex justify-center">
    <div class="w-full sm:w-4/5 px-4">
      <h2 class="text-2xl sm:text-3xl font-bold text-center text-yellow-600 mb-4 sm:mb-6">
        Explore Misamis Occidental on the Map
      </h2>
      <div id="map" class="w-full h-64 sm:h-96 rounded-lg shadow-lg"></div>
    </div>
  </section>

  <!-- Footer -->
  <?php include 'config/include/footer.php'; ?>

 <!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/explo-details.js"></script>

<script>
  const touristSpots = [
    { name: "Hoyohoy View Deck", desc: "Scenic mountain spot", location: [8.35, 123.75] },
    { name: "Pipe Drive", desc: "City attraction", location: [8.48, 123.75] }
  ];

  document.addEventListener("DOMContentLoaded", () => {
    initProvinceMap(touristSpots);
  });
</script>

</body>
</html>
