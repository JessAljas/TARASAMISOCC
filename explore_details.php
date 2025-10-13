<?php
include 'config/db_connect.php'; // The database connection file
include('config/include/header.php');

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

// Set defaults if latitude/longitude kay missing sa Ozamiz nga place
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
  <link rel="stylesheet" href="config/css/style.css">
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
  <div class="bg-white p-6 rounded-2xl shadow-md z-10">
    <h2 class="text-xl font-semibold mb-3 text-gray-800">Map Location</h2>
    <div id="map" class="w-full h-[400px] rounded-2xl"></div>
  </div>

</div>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md relative">
    <h2 class="text-lg font-bold text-gray-800 mb-4">Confirm Logout</h2>
    <p class="text-gray-600 mb-6">Are you sure you want to log out?</p>
    <div class="flex justify-end space-x-3">
      <button onclick="closeLogoutModal()" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300 text-gray-700">Cancel</button>
      <a href="<?= $base ?>logout.php" class="px-4 py-2 rounded bg-red-600 hover:bg-red-700 text-white">Logout</a>
    </div>
  </div>
</div>


<?php include 'config/include/footer.php'; ?>

<!-- Pass PHP values to JS -->
<script>
  const lat = <?php echo $latitude; ?>;
  const lng = <?php echo $longitude; ?>;
  const spotName = "<?php echo addslashes($spot['name_of_tourist_spot']); ?>";

  document.addEventListener("DOMContentLoaded", function () {
    if (typeof initMap === "function") {
      initMap(lat, lng, spotName);
    } else {
      console.error("initMap() not found. Check if explo-details.js is loaded.");
    }
  });
</script>

<script src="js/explo-details.js"></script>
</body>
</html>
