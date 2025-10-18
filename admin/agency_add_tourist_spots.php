<?php
session_start();
include '../config/db_connect.php';

// Restrict access only to admin or agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agency'])) {
    header("Location: login.php");
    exit;
}

// Get user info
$user = $_SESSION['user'];
$email = $user['email'];
$fullname = $user['fullname'];
$role = $user['role'];

$message = "";
$total_spots = 0;

// ✅ Step 1: Ensure this user exists in spot_owners
$stmt = $conn->prepare("SELECT id FROM spot_owners WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    $owner_id = $res['id'];
} else {
    // Insert new admin/agency as spot_owner
    $stmt = $conn->prepare("
        INSERT INTO spot_owners (fullname, email, password, phone_number, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $dummy_password = password_hash("defaultpassword", PASSWORD_DEFAULT);
    $dummy_phone = '';
    $stmt->bind_param("ssss", $fullname, $email, $dummy_password, $dummy_phone);
    $stmt->execute();
    $owner_id = $stmt->insert_id;
    $stmt->close();
}

// ✅ Step 2: Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $spot_name        = $_POST['name'] ?? '';
    $owner_name       = $fullname; // ✅ use logged-in user's name
    $spot_description = $_POST['description'] ?? '';
    $spot_location    = $_POST['location'] ?? '';
    $entrance_fee     = floatval($_POST['entrance_fee'] ?? 0);
    $activity         = $_POST['activity'] ?? '';
    $latitude         = floatval($_POST['latitude'] ?? 0);
    $longitude        = floatval($_POST['longitude'] ?? 0);

    // ✅ Step 3: Handle image uploads (3 required)
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
        // ✅ Step 4: Insert into tourist_spots
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
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
        ");

        $stmt->bind_param(
            "issssssdsddss",
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
            $role,
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


// ✅ Step 5: Count total spots posted by this admin/agency
$count_query = $conn->prepare("
    SELECT COUNT(*) AS total 
    FROM tourist_spots 
    WHERE posted_by_type=? AND owner_id=?
");
$count_query->bind_param("si", $role, $owner_id);
$count_query->execute();
$count_result = $count_query->get_result()->fetch_assoc();
$total_spots = $count_result['total'] ?? 0;
$count_query->close();

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
    <script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
    <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="flex font-[Poppins]">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Ang main Content -->
    <div id="mainContent">
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

    <!-- Message Box -->
    <?php if($message): ?>
    <div id="messageBox" 
        class="text-green-500 text-center fixed top-1/4 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
        <?= htmlspecialchars($message) ?>
    </div>

    <script>
        // Wait 6 seconds, then fade out and remove the message
        setTimeout(() => {
            const msg = document.getElementById('messageBox');
            if (msg) {
                msg.style.opacity = '0'; // fade out
                setTimeout(() => msg.remove(), 500); // remove after transition
            }
        }, 6000);
    </script>
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

    <script src="isset/script.js"></script>

    </body>
    </html>
