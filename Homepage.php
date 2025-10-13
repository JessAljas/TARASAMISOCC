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

  <!-- Header with fixed position -->
  <div class="fixed top-0 left-0 w-full z-50 shadow-md bg-white">
    <?php include 'config/include/header.php'; ?>
  </div>

  <!-- Hero Section -->
  <section class="hero-section relative flex items-center justify-center text-center">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative max-w-3xl mx-auto p-6 rounded-2xl bg-black/30 shadow-lg">
      <h1 class="script-title text-white text-4xl sm:text-5xl md:text-6xl font-bold mb-4 drop-shadow-lg">
        Explore Misamis Occidental
      </h1>
      <p class="text-white text-base sm:text-lg md:text-xl mb-6 drop-shadow-lg">
        Discover beautiful destinations and exciting adventures across the province.
      </p>
      <a href="explore.php" class="inline-block bg-yellow-400 text-blue-900 font-semibold px-8 py-3 rounded-full shadow-lg hover:bg-yellow-500 transition duration-300">
        <i class="fas fa-map-marked-alt mr-2"></i>Discover Top Destinations
      </a>
    </div>
  </section>

  <!-- Featured Tours & Services Section -->
  <section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-5">
      <h2 class="text-4xl font-bold text-center text-yellow-600 mb-12">Featured Tours & Services</h2>
      <div class="services-slider space-x-6">
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
        <div class="service-card bg-white p-6 rounded-2xl shadow-md hover:shadow-xl transition duration-300 text-center">
          <div class="text-yellow-500 text-4xl mb-4"><i class="<?= $service['icon'] ?>"></i></div>
          <h3 class="text-xl font-bold text-gray-800 mb-2"><?= $service['title'] ?></h3>
          <p class="text-gray-600"><?= $service['desc'] ?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- Map Section -->
  <section class="w-full py-8 flex justify-center">
    <div class="w-4/5">
      <h2 class="text-3xl font-bold text-center text-yellow-600 mb-6">
        Explore Misamis Occidental on the Map
      </h2>
      <div id="map" class="rounded-lg shadow-lg z-20"></div>
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

  // Initialize province map
  document.addEventListener("DOMContentLoaded", () => {
    initProvinceMap(touristSpots);
  });
</script>

</body>
</html>
