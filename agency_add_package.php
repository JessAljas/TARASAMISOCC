<?php
session_start();
include 'db_connect.php';

// Dri mo direct if not logged in as admin/agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';

// Fetch or gikuha ang tourist spots data
$spots = [];
$res = $conn->query("SELECT id, name_of_tourist_spot, location, latitude, longitude FROM tourist_spots ORDER BY name_of_tourist_spot");
while ($row = $res->fetch_assoc()) {
    $spots[] = $row;
}

// Handle form sa submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $price = $_POST['price'];
    $description = trim($_POST['description']);
    $pickup = trim($_POST['pickup']);
    $dropoff = trim($_POST['dropoff']);
    
    // Decode selected destinations from JS-ordered hidden field
    $destinations = [];
    if (!empty($_POST['selected_destinations'])) {
        $destinations = json_decode($_POST['selected_destinations'], true);
    }

    // Get inclusions & exclusions
    $inclusions = [];
    $exclusions = [];
    for ($i = 1; $i <= 4; $i++) {
        $inclusions[] = trim($_POST["inclusion$i"] ?? '');
        $exclusions[] = trim($_POST["exclusion$i"] ?? '');
    }

    if (count($destinations) > 4) {
        $error = "Maximum of 4 destinations only.";
    } else {
        $uploadedImages = [];
        $targetDir = "uploads/";

        for ($i = 0; $i < 4; $i++) {
            if (!empty($_FILES['images']['name'][$i])) {
                $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
                $targetFilePath = $targetDir . $fileName;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath)) {
                    $uploadedImages[] = $fileName;
                } else {
                    $uploadedImages[] = null;
                }
            } else {
                $uploadedImages[] = null;
            }
        }

        // Insert sa package with inclusions/exclusions
        $stmt = $conn->prepare("INSERT INTO packages 
        (title, description, price, pickup_location, dropoff_location, image1, image2, image3, image4,
         inclusion1, inclusion2, inclusion3, inclusion4,
         exclusion1, exclusion2, exclusion3, exclusion4) 
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");

        // ang bind_param code
        $stmt->bind_param("ssdssssssssssssss",
            $title, $description, $price, $pickup, $dropoff,
            $uploadedImages[0], $uploadedImages[1], $uploadedImages[2], $uploadedImages[3],
            $inclusions[0], $inclusions[1], $inclusions[2], $inclusions[3],
            $exclusions[0], $exclusions[1], $exclusions[2], $exclusions[3]
        );

        if ($stmt->execute()) {
            $package_id = $stmt->insert_id;
            $stmt->close();

            // Pag insert sa package destinations
            foreach ($destinations as $order => $spot_id) {
                $stmt = $conn->prepare("INSERT INTO package_destinations (package_id, tourist_spot_id, stop_order) VALUES (?, ?, ?)");
                $stop_order = $order + 1; // first clicked = 1
                $stmt->bind_param("iii", $package_id, $spot_id, $stop_order);
                $stmt->execute();
                $stmt->close();
            }

            $success = "Package and itinerary posted successfully!";
        } else {
            $error = "Error posting package: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Tour Package</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<style>
body { font-family: 'Inter', sans-serif; }
#mainContent { margin-left: 16rem; padding: 2rem; }
.card { background: white; border-radius: 0.75rem; padding: 2rem; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
input[type="file"] { border: 1px dashed #d1d5db; padding: 1rem; border-radius: 0.5rem; cursor: pointer; }
.checkbox-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
</style>
<script>
window.onload = function(){
    const max = 4;
    const checkboxes = document.querySelectorAll('input[name="destinations[]"]');
    const hiddenField = document.getElementById('selectedDestinations');
    let selected = [];

    checkboxes.forEach(cb => cb.addEventListener('change', function(){
        if(this.checked){
            if(selected.length >= max){
                alert("Maximum 4 destinations");
                this.checked=false;
                return;
            }
            selected.push(this.value);
        } else {
            selected = selected.filter(v => v !== this.value);
        }
        hiddenField.value = JSON.stringify(selected);
    }));
};
</script>
</head>
<body class="bg-gray-100 flex font-[Poppins]">

<?php include 'sidebar.php'; ?>

<div id="mainContent" class="flex-1">
<main class="max-w-5xl mx-auto mt-8">
<div class="flex items-center justify-between mb-6">
  <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-3">
    <span>Add New Tour Packages</span>
  </h1>
 <a href="agency_manage_packages.php" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-md shadow flex items-center gap-2">
    <i class="fas fa-folder"></i> Manage Packages
</a>

</div>

<div class="card">
<?php if($error): ?><p class="text-red-600 mb-4"><?= $error ?></p><?php endif; ?>
<?php if($success): ?><p class="text-green-600 mb-4"><?= $success ?></p><?php endif; ?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
<div>
<label class="block mb-2 font-semibold text-gray-700">Package Title</label>
<input type="text" name="title" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
</div>

<div>
<label class="block mb-2 font-semibold text-gray-700">Price (₱)</label>
<input type="number" step="0.01" name="price" required class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
</div>

<div class="grid grid-cols-2 gap-4">
<div>
<label class="block mb-2 font-semibold text-gray-700">Pickup Location</label>
<input type="text" name="pickup" required placeholder="Enter pickup location" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
</div>

<div>
<label class="block mb-2 font-semibold text-gray-700">Dropoff Location</label>
<input type="text" name="dropoff" required placeholder="Enter dropoff location" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
</div>
</div>

<div>
<label class="block mb-2 font-semibold text-gray-700">Package Description</label>
<textarea name="description" rows="4" required 
class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
placeholder="Write a short description about this package..."></textarea>
</div>

<!-- Inclusions sa package -->
<div>
<label class="block mb-2 font-semibold text-gray-700">Inclusions (max 4)</label>
<div class="grid grid-cols-2 gap-4">
<?php for($i=1; $i<=4; $i++): ?>
<input type="text" name="inclusion<?= $i ?>" placeholder="Inclusion <?= $i ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
<?php endfor; ?>
</div>
</div>

<!-- Exclusions sa package -->
<div>
<label class="block mb-2 font-semibold text-gray-700">Exclusions (max 4)</label>
<div class="grid grid-cols-2 gap-4">
<?php for($i=1; $i<=4; $i++): ?>
<input type="text" name="exclusion<?= $i ?>" placeholder="Exclusion <?= $i ?>" class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
<?php endfor; ?>
</div>
</div>

<div>
<label class="block mb-2 font-semibold text-gray-700">Select Destinations (max 4)</label>
<div class="checkbox-grid">
<?php foreach($spots as $spot): ?>
<label class="flex items-center space-x-2 p-2 border border-gray-200 rounded hover:bg-green-50 cursor-pointer">
<input type="checkbox" name="destinations[]" value="<?= $spot['id'] ?>" class="h-4 w-4 text-green-500">
<span class="text-gray-700"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></span>
</label>
<?php endforeach; ?>
</div>
<!-- Hidden field to preserve click order -->
<input type="hidden" name="selected_destinations" id="selectedDestinations">
</div>

<div>
<label class="block mb-2 font-semibold text-gray-700">Upload Images (Max 4)</label>
<div class="grid grid-cols-2 gap-4">
<?php for($i=0; $i<4; $i++): ?>
<input type="file" name="images[<?= $i ?>]" accept="image/*" class="w-full">
<?php endfor; ?>
</div>
<small class="text-gray-500">You can upload up to 4 images.</small>
</div>

<button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-md shadow font-semibold w-full">Post Package</button>
</form>
</div>
</main>
</div>
</body>
</html>
