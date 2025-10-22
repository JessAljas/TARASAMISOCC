<?php
session_start();
include '../config/db_connect.php';

// Redirect if not logged in or role mismatch
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_id = $user['id'];

// Fetch owner info
$stmt = $conn->prepare("SELECT * FROM spot_owners WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_img = !empty($owner['profile_image']) ? '../uploads/' . $owner['profile_image'] : '../img/default_profile.png';

// ========================== Handle Profile Update ==========================
$profile_update_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $profile_image = $owner['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_image = 'profile_' . time() . '.' . $ext;
        if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], '../uploads/' . $new_image);
        if (!empty($owner['profile_image']) && file_exists('../uploads/' . $owner['profile_image'])) {
            unlink('../uploads/' . $owner['profile_image']);
        }
        $profile_image = $new_image;
    }

    $stmt = $conn->prepare("UPDATE spot_owners SET fullname=?, email=?, phone_number=?, profile_image=? WHERE id=?");
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $profile_image, $user_id);
    if ($stmt->execute()) {
        $profile_update_msg = "Profile updated successfully!";
        $owner['fullname'] = $fullname;
        $owner['email'] = $email;
        $owner['phone_number'] = $phone;
        $owner['profile_image'] = $profile_image;
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['profile_image'] = $profile_image;
    } else {
        $profile_update_msg = "Failed to update profile.";
    }
    $stmt->close();
}

// ========================== Handle Sending Inquiry ==========================
$inquiry_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $receiver_role = $_POST['receiver_role'] ?? '';
    $allowed_roles = ['tourism_officer', 'agency'];

    if ($subject && $message_text && in_array($receiver_role, $allowed_roles, true)) {
        $stmt = $conn->prepare("INSERT INTO inquiries (sender_id, sender_role, receiver_role, subject, message, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $role = 'spot_owner';
        $stmt->bind_param("issss", $user_id, $role, $receiver_role, $subject, $message_text);

        if ($stmt->execute()) {
            $inquiry_msg = "Message sent successfully to " . htmlspecialchars($receiver_role) . "!";
        } else {
            $inquiry_msg = "Failed to send message: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $inquiry_msg = "Please fill all fields and select a valid recipient.";
    }
}

// ========================== Tourist Spots Data ==========================
$stmt = $conn->prepare("SELECT id FROM spot_owners WHERE email=?");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();
$owner_id = $res ? $res['id'] : 0;
$stmt->close();

$total_spots = 0;
if ($owner_id) {
    $count_query = $conn->prepare("SELECT COUNT(*) AS total FROM tourist_spots WHERE posted_by_type='spot_owner' AND owner_id=?");
    $count_query->bind_param("i", $owner_id);
    $count_query->execute();
    $count_result = $count_query->get_result()->fetch_assoc();
    $total_spots = $count_result['total'] ?? 0;
    $count_query->close();
}

// ========================== Handle Adding Tourist Spot ==========================
$add_spot_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tourist_spot'])) {
    $name = trim($_POST['name']); // Spot name
    $owner_name = trim($_POST['owner_name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $activity = trim($_POST['activity']);
    $entrance_fee = floatval($_POST['entrance_fee']);
    $latitude = trim($_POST['latitude']);
    $longitude = trim($_POST['longitude']);

    // Handle image uploads for 3 separate columns
    $images = [null, null, null]; // Initialize empty
    if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);

    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['image']['name'][$i])) {
            $ext = pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION);
            $new_image = 'spot_' . time() . '_' . $i . '.' . $ext;
            if (move_uploaded_file($_FILES['image']['tmp_name'][$i], '../uploads/' . $new_image)) {
                $images[$i] = $new_image;
            }
        }
    }

    $stmt = $conn->prepare("INSERT INTO tourist_spots 
        (name_of_tourist_spot, owner_name, location, description, activity, entrance_fee, latitude, longitude, image1, image2, image3, owner_id, posted_by_type, spot_created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'spot_owner', NOW())");

    // Corrected types string: 12 variables
    $stmt->bind_param(
        "ssssssdssssi",
        $name,
        $owner_name,
        $location,
        $description,
        $activity,
        $entrance_fee,
        $latitude,
        $longitude,
        $images[0],
        $images[1],
        $images[2],
        $user_id
    );

    if ($stmt->execute()) {
        $add_spot_msg = "Tourist Spot added successfully!";
        $total_spots++; // update total count
    } else {
        $add_spot_msg = "Failed to add tourist spot: " . $stmt->error;
    }

    $stmt->close();
}

// Close connection only at the very end
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourist Spot Owner Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
 <style>
    body {
      background-image: url('../img/back.jpg'); /* imong image */
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 100vh;
      font-family: 'Poppins', sans-serif;
    }
  </style>
<body class="bg-white-300 font-[Poppins] min-h-screen flex flex-col">
   <?php include 'owner_header.php'; ?>

<!-- ========================== MAIN DASHBOARD ========================== -->
<div class="flex flex-col items-center justify-center flex-1 py-6">

    <!-- Total Spots Card -->
    <div class="w-96 bg-gradient-to-r from-green-500 to-yellow-400 text-white p-8 rounded-2xl shadow-xl text-center flex flex-col items-center mb-6">
        <i class="fas fa-map-location-dot text-5xl mb-4"></i>
        <h2 class="text-lg font-semibold">Total Spots Posted</h2>
        <p class="text-3xl font-bold mt-2"><?= $total_spots ?></p>
    </div>

   <div class="flex space-x-6">


    <!-- Add Tourist Spot -->
    <button onclick="openModal('addSpotModal')" class="flex flex-col items-center">
        <div class="w-16 h-16 rounded-full bg-green-500 hover:bg-green-600 text-white text-3xl flex items-center justify-center shadow-lg transition">
            <i class="fas fa-plus"></i>
        </div>
        <span class="mt-2 text-white font-semibold">Add</span>
    </button>
</div>

<!-- ========================== ADD TOURIST SPOT MODAL ========================== -->
<div id="addSpotModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-start overflow-auto p-4 pt-12">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-3xl relative max-h-[90vh] overflow-y-auto">
    <h2 class="text-lg font-semibold mb-4 text-green-600">Add Tourist Spot</h2>

    <!-- Show success/error message -->
    <?php if($add_spot_msg): ?>
      <p class="bg-green-100 text-green-700 px-4 py-2 rounded shadow mb-4"><?= htmlspecialchars($add_spot_msg) ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- left side inputs -->
      <div>
        <input type="text" name="name" placeholder="Spot Name" required class="w-full p-3 border rounded mb-3">
        <input type="text" name="owner_name" placeholder="Owner Name" required class="w-full p-3 border rounded mb-3">
        <input type="text" name="location" placeholder="Location" required class="w-full p-3 border rounded mb-3">
        <textarea name="description" rows="4" placeholder="Description" class="w-full p-3 border rounded mb-3"></textarea>
        <input type="text" name="activity" placeholder="Activities" class="w-full p-3 border rounded mb-3">
        <input type="number" step="0.01" name="entrance_fee" placeholder="Entrance Fee" class="w-full p-3 border rounded">
      </div>

      <!-- right side map and images -->
      <div>
        <div id="modalMap" class="w-full h-64 rounded border mb-2"></div>
        <div class="grid grid-cols-2 gap-4 mt-2">
          <input type="text" id="modalLatitude" name="latitude" placeholder="Latitude" class="w-full p-3 border rounded" readonly>
          <input type="text" id="modalLongitude" name="longitude" placeholder="Longitude" class="w-full p-3 border rounded" readonly>
        </div>

        <h3 class="font-semibold mt-4 mb-2">Upload Images</h3>
        <div class="flex gap-4">
          <input type="file" name="image[]" accept="image/*" required class="w-full border p-2 rounded">
          <input type="file" name="image[]" accept="image/*" required class="w-full border p-2 rounded">
          <input type="file" name="image[]" accept="image/*" required class="w-full border p-2 rounded">
        </div>
      </div>

      <!-- submit buttons -->
      <div class="md:col-span-2 flex justify-end mt-4">
        <button type="button" onclick="closeModal('addSpotModal')" class="px-4 py-2 bg-gray-300 rounded mr-2">Cancel</button>
        <button type="submit" name="add_tourist_spot" class="bg-green-500 text-white px-8 py-3 rounded-lg shadow-md hover:bg-green-600 flex items-center gap-2">
          <i class="fas fa-paper-plane"></i> Add Tourist Spot
        </button>
      </div>
    </form>
  </div>
</div>


<script>
let modalMap, modalMarker;

function openModal(id) {
  const modal = document.getElementById(id);
  modal.classList.remove('hidden');
  document.body.classList.add('overflow-hidden');

  if(!modalMap){
    const defaultLat = 8.15, defaultLng = 123.85;
    const latInput = document.getElementById("modalLatitude");
    const lngInput = document.getElementById("modalLongitude");

    modalMap = L.map("modalMap").setView([defaultLat, defaultLng], 10);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"&copy; OpenStreetMap contributors"}).addTo(modalMap);

    modalMarker = L.marker([defaultLat, defaultLng]).addTo(modalMap);
    latInput.value = defaultLat.toFixed(6);
    lngInput.value = defaultLng.toFixed(6);

    modalMap.on('click', e=>{
      latInput.value = e.latlng.lat.toFixed(6);
      lngInput.value = e.latlng.lng.toFixed(6);
      modalMarker.setLatLng(e.latlng);
    });

    if(L.Control.geocoder){
      L.Control.geocoder({defaultMarkGeocode:false}).on('markgeocode', e=>{
        const center = e.geocode.center;
        modalMap.setView(center,14);
        modalMarker.setLatLng(center);
        latInput.value = center.lat.toFixed(6);
        lngInput.value = center.lng.toFixed(6);
      }).addTo(modalMap);
    }
  } else {
    setTimeout(()=>{ modalMap.invalidateSize(); modalMap.setView(modalMarker.getLatLng(), modalMap.getZoom()); }, 200);
  }
}

function closeModal(id){
  const modal = document.getElementById(id);
  modal.classList.add('hidden');
  document.body.classList.remove('overflow-hidden');
}


function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

</script>
</body>
</html>
