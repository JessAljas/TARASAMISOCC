<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in or role nag mismatch
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: tourist_spot_login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch ug owner info
$stmt = $conn->prepare("SELECT * FROM spot_owners WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Profile image fallback
$profile_img = !empty($owner['profile_image']) ? 'uploads/' . $owner['profile_image'] : 'img/default_profile.png';

// Handle sa profile update
$profile_update_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $profile_image = $owner['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_image = 'profile_' . time() . '.' . $ext;
        if (!is_dir('uploads')) mkdir('uploads', 0777, true);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], 'uploads/' . $new_image);
        if (!empty($owner['profile_image']) && file_exists('uploads/' . $owner['profile_image'])) {
            unlink('uploads/' . $owner['profile_image']);
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

// Initialize sa message variable
$inquiry_msg = "";

// Handle sa sending inquiry
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

// Handle sa adding new tourist spot
$error = $success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_spot'])) {
    $spot_name      = trim($_POST['spot_name']);
    $owner_fullname = trim($_POST['owner_fullname']);
    $description    = trim($_POST['description']);
    $location       = trim($_POST['location']);
    $activity       = trim($_POST['activity']);
    $entrance_fee   = $_POST['entrance_fee'] !== "" ? floatval($_POST['entrance_fee']) : 0;
    $latitude       = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
    $longitude      = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : 0;

    $image_names = [null, null, null];
    $upload_dir = 'uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['spot_images']['name'][$i])) {
            $ext = pathinfo($_FILES['spot_images']['name'][$i], PATHINFO_EXTENSION);
            $image_names[$i] = 'spot_' . time() . "_$i." . $ext;
            move_uploaded_file($_FILES['spot_images']['tmp_name'][$i], $upload_dir . $image_names[$i]);
        }
    }

    // Assign correct variables
    $owner_id   = $user_id; // kani kay gikan sa $_SESSION['user']['id']
    $image1     = $image_names[0];
    $image2     = $image_names[1];
    $image3     = $image_names[2];

$posted_by_type = 'spot_owner';

$stmt = $conn->prepare("INSERT INTO tourist_spots 
    (owner_id, name_of_tourist_spot, description, location, activity, entrance_fee, latitude, longitude, image1, image2, image3, owner_name, posted_by_type, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");

$stmt->bind_param(
    "issssddssssss",
    $owner_id,
    $spot_name,
    $description,
    $location,
    $activity,
    $entrance_fee,
    $latitude,
    $longitude,
    $image1,
    $image2,
    $image3,
    $owner_fullname,
    $posted_by_type
);


    if ($stmt->execute()) {
        $success = "Tourist spot added successfully!";
    } else {
        $error = "Failed to add tourist spot: " . $stmt->error;
    }
    $stmt->close();
}


// Count total spots
$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tourist_spots WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_entries = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();

// Fetch all spots with search filter
$search = $_GET['search'] ?? '';
$spots = [];
if ($search) {
    $stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE owner_id=? AND name_of_tourist_spot LIKE ? ORDER BY created_at DESC");
    $like = "%" . $search . "%";
    $stmt->bind_param("is", $user_id, $like);
} else {
    $stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE owner_id=? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) $spots[] = $row;
$stmt->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourist Spot Owner Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-[Poppins]">

<header class="bg-green-500 text-white shadow p-8 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Tourist Spot-Owner Dashboard</h1>

        <!-- ang rofile Dropdown -->
        <div class="relative">
            <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2 bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700 transition">
                <i class="fas fa-user-circle text-2xl"></i>
                <span class="font-semibold">Tourist Spot Owner</span>
                <i class="fas fa-caret-down"></i>
            </button>
            <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg overflow-hidden hidden">
                <a href="javascript:void(0);" 
                   onclick="document.getElementById('editModal').classList.remove('hidden'); toggleProfileDropdown();" 
                   class="block px-5 py-3 font-semibold text-green-700 hover:bg-green-100 transition">
                   <i class="fas fa-user-edit mr-2 text-green-600"></i> Profile
                </a>
                <a href="javascript:void(0);" 
                   onclick="document.getElementById('messageModal').classList.remove('hidden'); toggleProfileDropdown();" 
                   class="block px-5 py-3 font-semibold text-green-700 hover:bg-green-100 transition">
                   <i class="fas fa-envelope mr-2 text-green-600"></i> Send Inquiry
                </a>
                <a href="javascript:void(0);" 
                   onclick="document.getElementById('logoutModal').classList.remove('hidden'); toggleProfileDropdown();" 
                   class="block px-5 py-3 font-semibold text-red-600 hover:bg-red-100 transition">
                   <i class="fas fa-sign-out-alt mr-2 text-red-500"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<br><br>
<!-- Stats Card with Add Button -->
<div class="flex justify-center mb-6">
    <div class="bg-gradient-to-r from-green-400 via-teal-400 to-green-500 shadow-lg rounded-lg p-3 text-center text-white w-60 flex flex-col items-center h-30">
        <h2 class="text-lg font-semibold">Your Tourist Spots</h2>
        <p class="text-3xl font-bold mt-2"><?= $total_entries ?></p>

        <!-- Add Tourist Spot Button inside sa card -->
        <button id="toggleFormBtn" class="mt-auto bg-white text-green-600 p-2 rounded-full hover:bg-gray-100">
            <i class="fas fa-plus"></i> Add Tourist Spot
        </button>
    </div>
</div>

<main class="container mx-auto p-6 flex-1">
 <?php if($inquiry_msg): ?>
    <p class="text-green-600 mb-2"><?= htmlspecialchars($inquiry_msg) ?></p>
<?php endif; ?>

<!-- Existing Tourist Spots -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach($spots as $s): ?>
            <div class="bg-white shadow rounded-lg p-4 flex flex-col relative">
                <h3 class="font-bold text-lg mb-1"><?= htmlspecialchars($s['name_of_tourist_spot']) ?></h3>
                <p>Status: <span class="font-semibold"><?= htmlspecialchars($s['status']) ?></span></p>
                <p>Location: <?= htmlspecialchars($s['location']) ?></p>
                <a href="tourist_spot_manage.php?id=<?= $s['id'] ?>" class="mt-auto bg-green-500 text-white px-3 py-1 rounded self-start">
                    Manage
                </a>
            </div>
        <?php endforeach; ?>
    </div>

   <!-- Edit Profile Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
        <h2 class="text-lg font-bold mb-4">Profile</h2>
        <?php if($profile_update_msg): ?>
            <p class="text-green-600 mb-2"><?= htmlspecialchars($profile_update_msg) ?></p>
        <?php endif; ?>

        <!-- Profile Preview (shown first) -->
       <div id="profilePreview" class="mb-4 border rounded-lg p-4 bg-gray-50 text-center">
    <img src="<?= htmlspecialchars($profile_img) ?>" class="w-20 h-20 rounded-full mx-auto mb-2 border">
    <p class="font-semibold"><?= htmlspecialchars($owner['fullname']) ?></p>
    <p class="text-gray-600"><?= htmlspecialchars($owner['email']) ?></p>
    <p class="text-gray-600"><?= htmlspecialchars($owner['phone_number']) ?></p>

    <?php if (!empty($owner['name_of_tourist_spot'])): ?>
        <div class="mt-3 text-left">
            <p class="font-semibold text-green-700">Tourist Spot:</p>
            <p class="text-gray-700"><?= htmlspecialchars($owner['name_of_tourist_spot']) ?></p>
        </div>
    <?php else: ?>
        <p class="mt-3 text-gray-500 italic">No tourist spot assigned yet.</p>
    <?php endif; ?>
</div>

        <!-- Edit Form (hidden by default) -->
        <form id="editProfileForm" method="POST" enctype="multipart/form-data" class="space-y-4 hidden">
            <div>
                <label>Profile Image</label>
                <input type="file" name="profile_image" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Full Name</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($owner['fullname']) ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($owner['email']) ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label>Phone</label>
                <input type="text" name="phone_number" value="<?= htmlspecialchars($owner['phone_number']) ?>" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('editModal').classList.add('hidden');" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button type="submit" name="update_profile" class="px-4 py-2 bg-green-500 text-white rounded">Save</button>
            </div>
        </form>

        <!-- Toggle Buttons -->
        <div id="profileActions" class="flex justify-center gap-3 mt-4">
            <button type="button" 
                    onclick="document.getElementById('profilePreview').classList.add('hidden'); document.getElementById('editProfileForm').classList.remove('hidden'); this.parentElement.classList.add('hidden');" 
                    class="px-4 py-2 bg-red-500 text-white rounded">
                <i class="fas fa-user-edit mr-1"></i> Edit Profile
            </button>
            <button type="button" 
                    onclick="document.getElementById('editModal').classList.add('hidden');" 
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Send Inquiry Modal -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
        <h2 class="text-lg font-bold mb-4">Send Inquiry</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="send_inquiry" value="1">

            <div>
                <label>Send To:</label>
                <input type="hidden" name="receiver_role" value="agency">
                <span class="font-semibold">Agency</span>
            </div>

            <div>
                <label>Email:</label>
                <input type="text" name="subject" required class="w-full p-2 border rounded">
            </div>

            <div>
                <label>Message:</label>
                <textarea name="message" rows="4" required class="w-full p-2 border rounded"></textarea>
            </div>

            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('messageModal').classList.add('hidden');" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded">Send</button>
            </div>
        </form>
    </div>
</div>

    <!-- Add Tourist Spot Form -->
    <div id="addSpotForm" class="bg-white shadow rounded-lg p-6 mb-6 hidden">
        <h2 class="text-lg font-semibold mb-4">Add a New Tourist Spot</h2>
        <?php if($error): ?><p class="text-red-600 mb-2"><?= htmlspecialchars($error) ?></p><?php endif; ?>
        <?php if($success): ?><p class="text-green-600 mb-2"><?= htmlspecialchars($success) ?></p><?php endif; ?>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <input type="hidden" name="submit_spot" value="1">
            <div>
                <label>Spot Name</label>
                <input type="text" name="spot_name" required class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Owner Full Name</label>
                <input type="text" name="owner_fullname" required class="w-full p-2 border rounded" value="<?= htmlspecialchars($owner['fullname']) ?>">
            </div>
            <div>
                <label>Location</label>
                <input type="text" name="location" required class="w-full p-2 border rounded">
            </div>
            <div class="md:col-span-2">
                <label>Pick Location on Map</label>
                <div id="map" class="w-full h-64 rounded border mb-2"></div>
                <small>Click on the map to set latitude & longitude.</small>
            </div>
            <div>
                <label>Latitude</label>
                <input type="text" id="latitude" name="latitude" readonly class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Longitude</label>
                <input type="text" id="longitude" name="longitude" readonly class="w-full p-2 border rounded">
            </div>
            <div class="md:col-span-2">
                <label>Description</label>
                <textarea name="description" rows="4" required class="w-full p-2 border rounded"></textarea>
            </div>
            <div>
                <label>Activities</label>
                <input type="text" name="activity" class="w-full p-2 border rounded" placeholder="Swimming, Hiking">
            </div>
            <div>
                <label>Entrance Fee (₱)</label>
                <input type="number" step="0.01" name="entrance_fee" class="w-full p-2 border rounded">
            </div>
            <div class="md:col-span-2">
                <label>Spot Images</label>
                <input type="file" name="spot_images[0]" accept="image/*" class="w-full mb-2">
                <input type="file" name="spot_images[1]" accept="image/*" class="w-full mb-2">
                <input type="file" name="spot_images[2]" accept="image/*" class="w-full">
            </div>
            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="bg-green-500 text-white px-6 py-2 rounded">Post Spot</button>
            </div>
        </form>
    </div>

<!-- Logout Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-sm text-center">
        <h2 class="text-lg font-bold mb-4">Confirm Logout</h2>
        <p class="mb-4">Are you sure you want to logout?</p>
        <div class="flex justify-center gap-4">
            <button onclick="document.getElementById('logoutModal').classList.add('hidden');" 
                    class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">
                Cancel
            </button>
            <a href="tourist_spot_logout.php" 
               class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                Logout
            </a>
        </div>
    </div>
</div>
</main>
 <!-- Footer -->
  <footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <!-- Logo -->
  <div class="flex flex-col items-center space-y-2">
    <div class="flex space-x-2">
      <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
      <img src="img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
      <img src="img/prov-logo.png" alt="Bee Logo" class="w-14 h-14">
    </div>
    <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
  </div>
    <!-- Footer Bottom -->
  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
  </footer>


<!-- Leaflet CSS/JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<!-- Leaflet Control Geocoder -->
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>

<script>
// Profile dropdown toggle
function toggleProfileDropdown() {
    document.getElementById('profileDropdown').classList.toggle('hidden');
}

// References
const toggleBtn = document.getElementById('toggleFormBtn');
const addForm = document.getElementById('addSpotForm');
let map, marker;

// Function to initialize map
function initMap() {
    if (map) return; // Already na initialized

    map = L.map('map').setView([8.15, 123.85], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    marker = null;

    map.on('click', function(e){
        document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
        document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
        if(marker) marker.setLatLng(e.latlng);
        else marker = L.marker(e.latlng).addTo(map);
    });

    const geocoder = L.Control.geocoder({
        defaultMarkGeocode: false,
        position: 'topright',
        placeholder: 'Search location...'
    })
    .on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        map.setView(latlng, 15);

        if(marker) marker.setLatLng(latlng);
        else marker = L.marker(latlng).addTo(map);

        document.getElementById('latitude').value = latlng.lat.toFixed(6);
        document.getElementById('longitude').value = latlng.lng.toFixed(6);
    })
    .addTo(map);
}

toggleBtn.addEventListener('click', () => {
    addForm.classList.toggle('hidden');

    setTimeout(() => {
        initMap();
        map.invalidateSize();
    }, 100);
});
</script>

</body>
</html>
