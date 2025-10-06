<?php
session_start();

// Only mo allow ug logged-in tourists
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

<style>
  .script-title { font-family: 'Great Vibes', cursive; }
  .hero-section {
    background-image: url('img/Screenshot 2025-09-24 150519.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    height: 90vh;
  }
  #map { height: 650px; width: 100%; display: block; }
  .services-slider {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 1rem;
  }
  .services-slider::-webkit-scrollbar { display: none; }
  .service-card { flex: 0 0 260px; scroll-snap-align: start; }

  header { z-index: 50; }
  body { padding-top: 80px; }
  section { scroll-margin-top: 80px; }
</style>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
 <div class="fixed top-0 left-0 w-full z-50 shadow-md bg-white font-[Poppins]">
        <?php include 'header.php'; ?>
    </div>
<!-- Hero nga Section -->
<section class="hero-section relative flex items-center justify-center text-center">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="relative max-w-3xl mx-auto p-6 rounded-2xl bg-black/30 shadow-lg">
    <h1 class="script-title text-white text-4xl sm:text-5xl md:text-6xl font-bold mb-4 drop-shadow-lg">
      Explore Misamis Occidental
    </h1>
    <p class="text-white text-base sm:text-lg md:text-xl mb-6 drop-shadow-lg font-[Poppins]">
      Discover beautiful destinations and exciting adventures across the province.
    </p>
    <a href="explore.php" class="inline-block bg-yellow-400 text-blue-900 font-semibold px-8 py-3 rounded-full shadow-lg hover:bg-yellow-500 transition duration-300 font-[Poppins]">
      <i class="fas fa-map-marked-alt mr-2"></i>Discover Top Destinations
    </a>
  </div>
</section>

<!-- Featured Tours & Services section -->
<section class="py-12 bg-gray-50 font-[Poppins]">
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

<!-- Map nga Section -->
<section class="w-full py-8 flex justify-center">
  <div class="w-4/5">
    <h2 class="text-3xl font-bold text-center text-yellow-600 mb-6">
      Explore Misamis Occidental on the Map
    </h2>
    <div id="map" class="rounded-lg shadow-lg"></div>
  </div>
</section>

<?php include 'footer.php'; ?>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
  // Initialize map centered on Misamis Occidental
  const map = L.map('map').setView([8.36, 123.75], 9);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Polygon for Misamis Occidental (approximate boundary)
  const misOccPolygon = [
    [8.56, 123.45], [8.70, 123.55], [8.80, 123.70],
    [8.78, 123.95], [8.65, 124.05], [8.40, 124.05],
    [8.20, 123.95], [8.05, 123.70], [8.15, 123.50],
    [8.36, 123.45]
  ];
  L.polygon(misOccPolygon, {
    color: "blue",
    weight: 2,
    fillColor: "#60a5fa",
    fillOpacity: 0.3
  }).addTo(map).bindPopup("Province of Misamis Occidental");

  touristSpots.forEach(spot => 
    L.marker(spot.location).addTo(map)
      .bindPopup(`<strong>${spot.name}</strong><br>${spot.desc}`)
  );
</script>
</body>
</html>
