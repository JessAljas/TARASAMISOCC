<?php
session_start();
include 'db_connect.php';


if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$user_name = $user['fullname'];

// Count total sa tourist spots
$result = $conn->query("SELECT COUNT(*) AS total FROM tourist_spots");
$total_spots = $result->fetch_assoc()['total'] ?? 0;

// Handle form submission sa tourist spot
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tourist_owner_name  = trim($_POST['tourist_owner_name'] ?? '');
    $tourist_owner_email = trim($_POST['tourist_owner_email'] ?? '');
    $tourist_owner_phone = trim($_POST['tourist_owner_phone'] ?? '');
    $name                = trim($_POST['name']);
    $location            = trim($_POST['location']);
    $description         = trim($_POST['description']);
    $activity            = trim($_POST['activity']);
    $entrance_fee        = $_POST['entrance_fee'] !== "" ? floatval($_POST['entrance_fee']) : 0;
    $latitude            = $_POST['latitude'] !== "" ? floatval($_POST['latitude']) : 0;
    $longitude           = $_POST['longitude'] !== "" ? floatval($_POST['longitude']) : 0;

    // Handle sa Tourist Owner nga code
    $stmt = $conn->prepare("SELECT id FROM spot_owners WHERE fullname = ?");
    $stmt->bind_param("s", $tourist_owner_name);
    $stmt->execute();
    $owner_result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($owner_result) {
        $owner_id = $owner_result['id'];
    } else {
        $stmt = $conn->prepare("INSERT INTO spot_owners (fullname, email, phone_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $tourist_owner_name, $tourist_owner_email, $tourist_owner_phone);
        if($stmt->execute()){
            $owner_id = $stmt->insert_id;
        } else {
            die("Failed to create tourist owner: " . $stmt->error);
        }
        $stmt->close();
    }

    // Handle sa images sa tourist spots
    $images = [null, null, null];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['image']['name'][$i])) {
            $ext = pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION);
            $images[$i] = 'spot_' . time() . "_$i." . $ext;
            move_uploaded_file($_FILES['image']['tmp_name'][$i], 'uploads/' . $images[$i]);
        }
    }

    // Default fields posted adin/agency
    $status = 'pending';
    $posted_by_type = $user['role']; 
    $owner_name = $tourist_owner_name;

    // Pag insert sa tourist spot record
 $stmt = $conn->prepare("INSERT INTO tourist_spots 
    (owner_id, name_of_tourist_spot, description, location, activity, entrance_fee, latitude, longitude, 
     image1, image2, image3, posted_by_type, owner_name, status, created_at, spot_created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

$stmt->bind_param(
    "issssdddssssss",  
    $owner_id,
    $name,
    $description,
    $location,
    $activity,
    $entrance_fee,
    $latitude,
    $longitude,
    $images[0],
    $images[1],
    $images[2],
    $posted_by_type,
    $owner_name,
    $status
);



    if ($stmt->execute()) {
        $message = "Tourist spot added successfully!";
        $total_spots++;
    } else {
        $message = "Failed to add tourist spot: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Tourist Spot - Tara sa MisOcc</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<style>
body { min-height: 100vh; background-color: #f3f4f6; font-family: 'Inter', sans-serif; }
#sidebar { min-width: 16rem; }
#mainContent { flex: 1; margin-left: 16rem; padding: 2rem; }
@media (max-width: 768px) {
    #sidebar { transform: translateX(-100%); transition: transform 0.3s ease; position: fixed; z-index:50; }
    #sidebar.show { transform: translateX(0); }
    #mainContent { margin-left: 0; transition: margin-left 0.3s ease; }
}
</style>
</head>
<body class="flex font-[Poppins]">

<!-- Sidebar -->
<?php include 'sidebar.php'; ?>

<!-- Ang main Content -->
<div id="mainContent">

    <!-- Ang mobile toggle code -->
    <button class="md:hidden mb-4 px-3 py-2 border rounded" onclick="document.getElementById('sidebar').classList.toggle('show')">
        <i class="fas fa-bars"></i> Menu
    </button>

    <!-- Ang header -->
    <header class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-4">Add Tourist Spot</h1>
    </header>

   <!-- Ang Total sa Tourist Spots nga Card -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-gradient-to-r from-green-400 to-yellow-400 border shadow rounded-xl p-3 text-center text-white flex flex-col items-center justify-center">
        <i class="fas fa-map-marker-alt text-4xl mb-3"></i>
        <h2 class="text-lg font-semibold">Total Spots Posted</h2>
        <p class="text-3xl font-bold mt-2"><?= $total_spots ?></p>
    </div>
</div>


    <?php if($message): ?>
        <div class="mb-4 p-4 border rounded bg-gray-50 text-gray-700 shadow"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Form sa pag add ug Tourist Spots-->
    <div class="bg-white border shadow rounded-xl p-6">
        <h2 class="text-2xl font-semibold mb-6 flex justify-between items-center">
            Add a New Tourist Spot
            <a href="agency_manage_tourist_spots.php" 
               class="px-4 py-2 rounded shadow text-sm bg-green-500 text-white hover:bg-green-600 transition">
               Manage Tourist Spots
            </a>
        </h2>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Info sa mga tourist spots -->
            <div>
                <h3 class="font-semibold mb-2">Spot Info</h3>
                <div class="space-y-3">
                    <input type="text" name="name" required placeholder="Spot Name" class="w-full p-3 border rounded">
                    <input type="text" name="location" required placeholder="Location" class="w-full p-3 border rounded">
                </div>
            </div>

            <!-- Info sa mga Tourist Owner  code-->
            <div>
                <h3 class="font-semibold mb-2">Tourist Owner Info</h3>
                <div class="space-y-3">
                    <input type="text" name="tourist_owner_name" required placeholder="Owner Name" class="w-full p-3 border rounded" value="<?= htmlspecialchars($_POST['tourist_owner_name'] ?? '') ?>">
                    <input type="email" name="tourist_owner_email" placeholder="Owner Email" class="w-full p-3 border rounded" value="<?= htmlspecialchars($_POST['tourist_owner_email'] ?? '') ?>">
                    <input type="text" name="tourist_owner_phone" placeholder="Owner Phone" class="w-full p-3 border rounded" value="<?= htmlspecialchars($_POST['tourist_owner_phone'] ?? '') ?>">
                </div>
            </div>

            <!-- Map Section nga code-->
            <div class="md:col-span-2">
                <h3 class="font-semibold mb-2">Pick Location on Map</h3>
                <div id="map" class="w-full h-64 rounded border mb-2"></div>
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <input type="text" id="latitude" name="latitude" placeholder="Latitude" class="w-full p-3 border rounded" readonly value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                    <input type="text" id="longitude" name="longitude" placeholder="Longitude" class="w-full p-3 border rounded" readonly value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">
                </div>
            </div>

            <!-- Description and Activities nga Form -->
            <div>
                <h3 class="font-semibold mb-2">Description & Activities</h3>
                <textarea name="description" rows="4" placeholder="Description" class="w-full p-3 border rounded"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                <input type="text" name="activity" placeholder="Activities e.g. Hiking, Swimming" class="w-full p-3 border rounded mt-3" value="<?= htmlspecialchars($_POST['activity'] ?? '') ?>">
                <input type="number" step="0.01" name="entrance_fee" placeholder="Entrance Fee (₱)" class="w-full p-3 border rounded mt-3" value="<?= htmlspecialchars($_POST['entrance_fee'] ?? '') ?>">
            </div>

            <!-- Form sa pag Upload ug Images -->
            <div>
                <h3 class="font-semibold mb-2">Upload Images</h3>
                <div class="space-y-2">
                    <input type="file" name="image[0]" accept="image/*" class="w-full">
                    <input type="file" name="image[1]" accept="image/*" class="w-full">
                    <input type="file" name="image[2]" accept="image/*" class="w-full">
                </div>
            </div>

            <!-- Submit Button sa pag add ug Tourist Spots -->
            <div class="md:col-span-2">
                <button type="submit" 
                        class="w-full rounded px-6 py-3 font-semibold text-white bg-gradient-to-r from-green-500 to-yellow-400 hover:from-green-600 hover:to-yellow-500 transition">
                    Add Tourist Spot
                </button>
            </div>
        </form>
    </div>
</div>

<script src="script.js"></script>

</body>
</html>
