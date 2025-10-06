<?php
include('db_connect.php');
include('header.php');

$id = $_GET['id'] ?? null;
if (!$id) {
    die("Invalid spot ID.");
}

// Fetch sa spot details
$stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$spot = $result->fetch_assoc();
$stmt->close();

if (!$spot) {
    die("Tourist spot not found.");
}

// Set defaults if latitude/longitude kay  missing sa Ozamiz nga place
$latitude = !empty($spot['latitude']) ? $spot['latitude'] : 8.15;   
$longitude = !empty($spot['longitude']) ? $spot['longitude'] : 123.85;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?php echo htmlspecialchars($spot['name_of_tourist_spot']); ?> - Tara sa Mis.Occ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 font-[Poppins]">

<!-- Container -->
<div class="max-w-6xl mx-auto px-4 py-6">

  <!-- Title -->
  <h1 class="text-3xl font-bold text-gray-900 mb-6">
    <?php echo htmlspecialchars($spot['name_of_tourist_spot']); ?>
  </h1>

  <!-- Image Grid -->
  <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <?php if (!empty($spot['image1'])): ?>
      <img src="uploads/<?php echo htmlspecialchars($spot['image1']); ?>" 
           alt="Image 1 - <?php echo htmlspecialchars($spot['name_of_tourist_spot']); ?>" 
           class="rounded-2xl shadow-md w-full h-64 object-cover">
    <?php endif; ?>

    <?php if (!empty($spot['image2'])): ?>
      <img src="uploads/<?php echo htmlspecialchars($spot['image2']); ?>" 
           alt="Image 2 - <?php echo htmlspecialchars($spot['name_of_tourist_spot']); ?>" 
           class="rounded-2xl shadow-md w-full h-64 object-cover">
    <?php endif; ?>

    <?php if (!empty($spot['image3'])): ?>
      <img src="uploads/<?php echo htmlspecialchars($spot['image3']); ?>" 
           alt="Image 3 - <?php echo htmlspecialchars($spot['name_of_tourist_spot']); ?>" 
           class="rounded-2xl shadow-md w-full h-64 object-cover">
    <?php endif; ?>
  </div>

  <!-- Details sa tourist spots-->
  <div class="bg-white p-6 rounded-2xl shadow-md mb-6">
    <h2 class="text-xl font-semibold mb-3 text-gray-800">About this spot</h2>
    <p class="text-gray-600 mb-4"><?php echo nl2br(htmlspecialchars($spot['description'])); ?></p>

    <?php if (!empty($spot['location'])): ?>
      <p class="text-gray-700"><span class="font-semibold">📍 Location:</span> <?php echo htmlspecialchars($spot['location']); ?></p>
    <?php endif; ?>

    <?php if (!empty($spot['entrance_fee'])): ?>
      <p class="text-gray-700"><span class="font-semibold">💰 Entrance Fee:</span> ₱<?php echo number_format($spot['entrance_fee'], 2); ?></p>
    <?php endif; ?>

    <?php if (!empty($spot['activity'])): ?>
      <div class="mt-4">
        <p class="text-gray-700 font-semibold mb-2">🎯 Activities:</p>
        <ul class="list-disc pl-6 text-gray-600">
          <?php 
          $activities = explode(',', $spot['activity']);
          foreach ($activities as $act): ?>
            <li><?php echo htmlspecialchars(trim($act)); ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
  </div>

  <!-- Map -->
  <div class="bg-white p-6 rounded-2xl shadow-md">
    <h2 class="text-xl font-semibold mb-3 text-gray-800">Map Location</h2>
    <div id="map" class="w-full h-[400px] rounded-2xl"></div>
  </div>

</div>

<script>
  // Get PHP lat/lng
  var lat = <?php echo $latitude; ?>;
  var lng = <?php echo $longitude; ?>;
  var spotName = "<?php echo addslashes($spot['name_of_tourist_spot']); ?>";

  // Initialize ang Map
  var map = L.map('map').setView([lat, lng], 14);

  // Tile Layer
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  // Marker with label name sa tourist spot
  L.marker([lat, lng], {
      icon: L.icon({
          iconUrl: 'https://maps.google.com/mapfiles/ms/icons/red-dot.png',
          iconSize: [32, 32],
          iconAnchor: [16, 32],
          popupAnchor: [0, -32]
      })
  }).addTo(map)
    .bindTooltip(spotName, {permanent: true, direction: "top", offset: [0, -10]})
    .openTooltip();
</script>

<?php include 'footer.php'; ?>
</body>
</html>
