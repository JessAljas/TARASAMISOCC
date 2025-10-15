<?php
session_start();
include '../config/db_connect.php';

// Restrict access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

// Tourism officer nga info
$user = $_SESSION['user'];
$email = $user['email'];
$fullname = $user['fullname'];


// Ensure nga officer nag exists in spot_owners
$stmt = $conn->prepare("SELECT id FROM spot_owners WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    $owner_id = $res['id'];
} else {
    // Insert new officer as spot_owner
    $stmt = $conn->prepare("INSERT INTO spot_owners (fullname, email, password, phone_number, created_at) VALUES (?, ?, ?, ?, NOW())");
    $dummy_password = password_hash("defaultpassword", PASSWORD_DEFAULT); // temporary password or generate random lang 
    $dummy_phone = '';
    $stmt->bind_param("ssss", $fullname, $email, $dummy_password, $dummy_phone);
    $stmt->execute();
    $owner_id = $stmt->insert_id;
    $stmt->close();
}

// Handle form submission
$message = "";
$total_spots = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spot_name        = $_POST['name'] ?? '';
    $owner_name       = $_POST['owner_name'] ?? '';
    $spot_description = $_POST['description'] ?? '';
    $spot_location    = $_POST['location'] ?? '';
    $entrance_fee     = floatval($_POST['entrance_fee'] ?? 0);
    $activity         = $_POST['activity'] ?? '';
    $latitude         = floatval($_POST['latitude'] ?? 0);
    $longitude        = floatval($_POST['longitude'] ?? 0);

    // Handle image uploads
    $uploaded_images = [];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['image']['name'][$i])) {
            $ext = pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION);
            $filename = "spot_" . time() . "_$i." . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'][$i], "../uploads/" . $filename)) {
                $uploaded_images[$i] = $filename;
            } else {
                $uploaded_images[$i] = null;
            }
        } else {
            $uploaded_images[$i] = null;
        }
    }

    $image1 = $uploaded_images[0];
    $image2 = $uploaded_images[1];
    $image3 = $uploaded_images[2];

    if (!$image1 || !$image2 || !$image3) {
        $message = "❌ Please upload all 3 images.";
    } else {
        // Insert into tourist_spots nga table
        $stmt = $conn->prepare("
            INSERT INTO tourist_spots (
                owner_id,
                name_of_tourist_spot,
                description,
                location,
                image1,
                image2,
                image3,
                entrance_fee,
                activity,
                latitude,
                longitude,
                posted_by_type,
                status,
                owner_name,
                spot_created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'tourism_officers', 'pending', ?, NOW())
        ");
$stmt->bind_param(
    "issssssdsdds",
    $owner_id,
    $spot_name,
    $spot_description,
    $spot_location,
    $image1,
    $image2,
    $image3,
    $entrance_fee,
    $activity,
    $latitude,
    $longitude,
    $owner_name
);

        if ($stmt->execute()) {
            $message = "✅ Spot added successfully!";
        } else {
            $message = "❌ Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Count total spots posted by this officer of tourism_officers 
$count_query = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tourist_spots 
    WHERE posted_by_type='tourism_officers' AND owner_id=?
");
$count_query->bind_param("i", $owner_id);
$count_query->execute();
$count_result = $count_query->get_result()->fetch_assoc();
$total_spots = $count_result['total'] ?? 0;
$count_query->close();

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Tourist Spot - Tourism Officer</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
</head>
<body class="bg-gray-100 p-6 font-[Poppins]">

<!-- Total Spots Card -->
<div class="relative mx-auto mb-6 w-64 bg-gradient-to-r from-green-400 to-yellow-400 border shadow-lg rounded-xl p-5 text-center text-white">
    <i class="fas fa-map-marker-alt absolute text-white text-6xl opacity-20 top-2 right-2"></i>
    <h2 class="text-lg font-semibold">Total Spots Posted</h2>
    <p class="text-3xl font-bold mt-2"><?= $total_spots ?></p>
</div>
<?php if($message): ?>
    <div id="flashMessage" 
         class="text-green-600 text-center">
        <?= htmlspecialchars($message) ?>
    </div>
    <script>
        // Fade out and remove after 6 seconds
        setTimeout(() => {
            const msg = document.getElementById('flashMessage');
            if (msg) {
                msg.style.transition = "opacity 0.5s ease";
                msg.style.opacity = "0";
                setTimeout(() => msg.remove(), 500);
            }
        }, 6000);
    </script>
<?php endif; ?>

<div class="flex items-center justify-between mb-6 p-4 rounded-lg bg-gradient-to-r from-green-500 to-yellow-400 text-white shadow-md">
    <h2 class="text-2xl font-semibold text-center flex-1">Add a New Tourist Spot</h2>
    <a href="tourism_manage_spots.php" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-green-600 font-medium hover:bg-gray-100 transition">
        <i class="fas fa-home w-5 text-center"></i>
        <span>Manage Tourist Spots</span>
    </a>
</div>

<form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <!-- Spot Info -->
    <div>
        <h3 class="font-semibold mb-2">Spot Info</h3>
        <input type="text" name="name" required placeholder="Spot Name" class="w-full p-3 border rounded mb-3">
        <input type="text" name="owner_name" required placeholder="Owner Name" class="w-full p-3 border rounded mb-3">
        <input type="text" name="location" required placeholder="Location" class="w-full p-3 border rounded mb-3">
        <textarea name="description" rows="4" placeholder="Description" class="w-full p-3 border rounded"></textarea>
        <input type="text" name="activity" placeholder="Activities (e.g. Hiking, Swimming)" class="w-full p-3 border rounded mt-3">
        <input type="number" step="0.01" name="entrance_fee" placeholder="Entrance Fee (₱)" class="w-full p-3 border rounded mt-3">
    </div>

    <!-- Map -->
    <div>
        <h3 class="font-semibold mb-2">Pick Location on Map</h3>
        <div id="map" class="w-full h-64 rounded border mb-2"></div>
        <div class="grid grid-cols-2 gap-4 mt-2">
            <input type="text" id="latitude" name="latitude" placeholder="Latitude" class="w-full p-3 border rounded" readonly>
            <input type="text" id="longitude" name="longitude" placeholder="Longitude" class="w-full p-3 border rounded" readonly>
        </div>

    <div>
  <h3 class="font-semibold mb-2">Upload Images</h3>
  <div class="flex flex-wrap gap-4">
    <div class="flex-1 min-w-[150px]">
      <input type="file" name="image[0]" accept="image/*" required
             class="block w-full border rounded p-2 cursor-pointer text-sm file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-500 file:text-white hover:file:bg-green-600 transition" />
    </div>
    <div class="flex-1 min-w-[150px]">
      <input type="file" name="image[1]" accept="image/*" required
             class="block w-full border rounded p-2 cursor-pointer text-sm file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-500 file:text-white hover:file:bg-green-600 transition" />
    </div>
    <div class="flex-1 min-w-[150px]">
      <input type="file" name="image[2]" accept="image/*" required
             class="block w-full border rounded p-2 cursor-pointer text-sm file:mr-3 file:py-2 file:px-4 file:rounded file:border-0 file:bg-green-500 file:text-white hover:file:bg-green-600 transition" />
    </div>
  </div>
</div>
<br>

   <!-- Submit Button -->
<div class="md:col-span-2 flex justify-end mt-4">
  <button type="submit"
          class="flex items-center justify-center gap-2 w-auto px-8 py-3 rounded-lg font-semibold text-white bg-gradient-to-r from-green-500 to-yellow-400 hover:from-green-600 hover:to-yellow-500 transition shadow-md">
      <i class="fas fa-paper-plane"></i>
      Add Tourist Spot
  </button>
</div>
    </div>
</form>
<div class="w-full flex justify-center mt-4 items-center">
    <a href="manage_request.php" class="text-blue-600 underline hover:text-blue-800 flex items-center">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>
<br>
<!-- FOOTER -->
<footer class="bg-green-500 text-white py-6 shadow-inner mt-auto">
  <div class="container mx-auto flex flex-col items-center space-y-4 text-center px-4">
    <div class="flex flex-col items-center space-y-2">
      <div class="flex items-center space-x-2">
        <img src="../img/bee-logo.png" alt="Bee Tours Logo" class="w-12 h-12">
        <h5 class="font-semibold text-lg">Travel Bee Tours</h5>
      </div>
      <div class="flex items-center space-x-2 mt-2">
        <img src="../img/prov-logo.png" alt="Province Logo" class="w-10 h-10">
        <p class="text-sm md:text-base font-medium max-w-xs md:max-w-md">
          Misamisnon Magpuyong Malinawon Malamboon ug Malipayon
        </p>
      </div>
    </div>
    <p class="text-sm mt-2">© 2025 Tara sa Mis.Occ. All rights reserved.</p>
  </div>
</footer>
<script src=js/spot.js></script>

</body>
</html>
