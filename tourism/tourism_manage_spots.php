<?php
session_start();
include '../config/db_connect.php';
include 'tourism_header.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

$user = $_SESSION['user'];
$email = $user['email'];   
$fullname = $user['fullname'];
$message = "";

// Get spot_owner ID
$stmt = $conn->prepare("SELECT id FROM spot_owners WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    $owner_id = $res['id'];
} else {
    // Create new spot_owner if not exists
    $stmt = $conn->prepare("INSERT INTO spot_owners (fullname, email, password, phone_number, created_at) VALUES (?, ?, ?, ?, NOW())");
    $dummy_password = password_hash("defaultpassword", PASSWORD_DEFAULT);
    $dummy_phone = '';
    $stmt->bind_param("ssss", $fullname, $email, $dummy_password, $dummy_phone);
    $stmt->execute();
    $owner_id = $stmt->insert_id;
    $stmt->close();
}

// Handle Delete
if (isset($_GET['delete'])) {
    $spot_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE posted_by_type='tourism_officers' AND owner_id=? AND id=?");
    $stmt->bind_param("ii", $owner_id, $spot_id);
    $message = $stmt->execute() ? "✅ Spot deleted successfully." : "❌ Error deleting spot: " . $stmt->error;
    $stmt->close();
}

// Handle Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_spot_id'])) {
    $spot_id          = intval($_POST['edit_spot_id']);
    $spot_name        = $_POST['edit_name'] ?? '';
    $owner_name       = $_POST['edit_owner_name'] ?? '';
    $spot_description = $_POST['edit_description'] ?? '';
    $spot_location    = $_POST['edit_location'] ?? '';
    $entrance_fee     = floatval($_POST['edit_entrance_fee'] ?? 0);
    $activity         = $_POST['edit_activity'] ?? '';
    $latitude         = floatval($_POST['edit_latitude'] ?? 0);
    $longitude        = floatval($_POST['edit_longitude'] ?? 0);

    // Absolute path for uploads folder (outside tourism folder)
    $uploadDir = realpath(__DIR__ . "/../uploads") . "/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    // Get current images
    $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tourist_spots WHERE posted_by_type='tourism_officers' AND owner_id=? AND id=?");
    $stmt->bind_param("ii", $owner_id, $spot_id);
    $stmt->execute();
    $current_images = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Handle deleted images from X button
    $delete_images = $_POST['delete_image'] ?? [];

    // Prepare updated images array
    $updated_images = [];
    for ($i = 0; $i < 3; $i++) {
        $imgIndex = $i + 1;
        $current_img = $current_images["image$imgIndex"];

        // If marked for deletion, remove file and reset to null
        if (in_array($current_img, $delete_images)) {
            if ($current_img && file_exists($uploadDir . $current_img)) unlink($uploadDir . $current_img);
            $current_img = null;
        }

        // Replace with newly uploaded image
        if (!empty($_FILES['edit_image']['name'][$i])) {
            $ext = pathinfo($_FILES['edit_image']['name'][$i], PATHINFO_EXTENSION);
            $filename = "spot_" . time() . "_$i." . $ext;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'][$i], $uploadDir . $filename)) {
                // Delete old image if exists
                if ($current_img && file_exists($uploadDir . $current_img)) unlink($uploadDir . $current_img);
                $updated_images[$i] = $filename;
            } else {
                $updated_images[$i] = $current_img;
            }
        } else {
            $updated_images[$i] = $current_img;
        }
    }

    // Update database
    $stmt = $conn->prepare("
        UPDATE tourist_spots SET 
            name_of_tourist_spot=?, 
            owner_name=?, 
            description=?, 
            location=?, 
            activity=?, 
            entrance_fee=?, 
            latitude=?, 
            longitude=?, 
            image1=?, 
            image2=?, 
            image3=?
        WHERE posted_by_type='tourism_officers' AND owner_id=? AND id=?
    ");
    $stmt->bind_param(
        "sssssdddsssii",
        $spot_name,
        $owner_name,
        $spot_description,
        $spot_location,
        $activity,
        $entrance_fee,
        $latitude,
        $longitude,
        $updated_images[0],
        $updated_images[1],
        $updated_images[2],
        $owner_id,
        $spot_id
    );

    $message = $stmt->execute() ? "✅ Spot updated successfully!" : "❌ Error updating spot: " . $stmt->error;
    $stmt->close();
}


// Fetch tourist spots
$stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE posted_by_type='tourism_officers' AND owner_id=? ORDER BY spot_created_at DESC");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$result = $stmt->get_result();
$spots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Tourist Spots</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 p- font-[Poppins]">
<?php
$total_spots = count($spots); 
?>

<div class="max-w-md w-full mx-auto bg-gradient-to-r from-green-500 to-yellow-400 rounded-lg shadow p-6 mb-6 mt-5 flex items-center gap-4">
    <!-- Icon on the left -->
    <div class="text-white text-5xl p-4 bg-green-600 rounded-full shadow-lg flex items-center justify-center">
        <i class="fas fa-compass"></i>
    </div>
    <div class="flex flex-col">
        <h1 class="text-xl sm:text-2xl font-bold text-white mb-1">Manage Your Tourist Spots</h1>
        <p class="text-white text-base sm:text-lg">
            Total Spots: <span class="font-bold text-lg sm:text-xl"><?= $total_spots ?></span>
        </p>
    </div>
</div>


<?php if($message): ?>
<div id="messageBox" class="text-green-700 text-center">
    <?= htmlspecialchars($message) ?>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.querySelector('table tbody');

    searchInput.addEventListener('input', () => {
        const filter = searchInput.value.toLowerCase();
        const rows = tableBody.querySelectorAll('tr');

        rows.forEach(row => {
            const cells = Array.from(row.querySelectorAll('td'));
            const match = cells.some(td => td.textContent.toLowerCase().includes(filter));
            row.style.display = match ? '' : 'none';
        });
    });
});
</script>
<div class="overflow-x-auto mx-4 md:mx-8 lg:mx-16">
    <div class="mb-4 flex items-center gap-2 mx-2">
    <label for="searchInput" class="font-medium text-gray-700">Search:</label>
    <div class="relative">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
        <input type="text" id="searchInput" placeholder="Search spots..." 
               class="w-64 p-3 pl-10 border rounded shadow focus:outline-none focus:ring focus:border-green-400">
    </div>
</div>
    <table class="min-w-full border-collapse mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
        <thead>
            <tr class="bg-green-400 text-white text-left">
                <th class="p-3 border-b">Spot Name</th>
                <th class="p-3 border-b">Owner Name</th>
                <th class="p-3 border-b">Location</th>
                <th class="p-3 border-b">Entrance Fee</th>
                <th class="p-3 border-b">Status</th>
                <th class="p-3 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($spots as $spot): ?>
            <tr class="border-b hover:bg-gray-50 transition-colors">
                <td class="p-3"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
                <td class="p-3"><?= htmlspecialchars($spot['owner_name']) ?></td>
                <td class="p-3"><?= htmlspecialchars($spot['location']) ?></td>
                <td class="p-3">₱<?= number_format($spot['entrance_fee'], 2) ?></td>
                <td class="p-3">
                    <?php 
                    $status = strtolower($spot['status']);
                    $color = $status === 'approved' ? 'green' : ($status === 'pending' ? 'yellow' : 'green');
                    ?>
                    <span class="px-2 py-1 rounded text-white bg-<?= $color ?>-500"><?= ucfirst($spot['status']) ?></span>
                </td>
                <td class="p-3 flex gap-2">
                    <button class="editBtn px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center gap-1"
                        data-spot='<?= json_encode($spot, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="deleteBtn px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 flex items-center gap-1"
                        data-id="<?= $spot['id'] ?>">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>



<div class="md:col-span-2 flex justify-end mt-4">
<a href="tourism_add_spots.php" 
   class="fixed bottom-6 right-6 w-16 h-16 rounded-full bg-green-500 hover:bg-green-600 text-white text-3xl font-bold flex items-center justify-center shadow-lg transition-shadow hover:shadow-2xl z-50">
    +
</a>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-xl w-full max-w-md p-6 relative">
        <h2 class="text-xl font-semibold mb-4">Confirm Deletion</h2>
        <p class="mb-4">Are you sure you want to delete this tourist spot? This action cannot be undone.</p>
        <div class="flex justify-end gap-2">
            <button id="cancelDeleteBtn" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
            <button id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Yes, Delete</button>
        </div>
    </div>
</div>

<div class="mt-4 flex justify-center gap-2" id="pagination"></div>


<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
<div class="bg-white rounded-xl w-full max-w-3xl p-6 relative max-h-[90vh] overflow-y-auto">
    <h2 class="text-xl font-semibold mb-4">Edit Tourist Spot</h2>
    <form method="POST" enctype="multipart/form-data" id="editForm" class="grid grid-cols-1 gap-4">
        <input type="hidden" name="edit_spot_id" id="edit_spot_id">
        <input type="text" name="edit_name" id="edit_name" placeholder="Spot Name" class="w-full p-3 border rounded">
        <input type="text" name="edit_owner_name" id="edit_owner_name" placeholder="Owner Name" class="w-full p-3 border rounded">
        <input type="text" name="edit_location" id="edit_location" placeholder="Location" class="w-full p-3 border rounded">
        <textarea name="edit_description" id="edit_description" rows="3" placeholder="Description" class="w-full p-3 border rounded"></textarea>
        <input type="text" name="edit_activity" id="edit_activity" placeholder="Activity" class="w-full p-3 border rounded">
        <input type="number" step="0.01" name="edit_entrance_fee" id="edit_entrance_fee" placeholder="Entrance Fee (₱)" class="w-full p-3 border rounded">

        <div>
            <h3 class="font-semibold mb-2">Pick Location on Map</h3>
            <div id="edit_map" class="w-full h-64 rounded border mb-2"></div>
            <div class="grid grid-cols-2 gap-4 mt-2">
                <input type="text" name="edit_latitude" id="edit_latitude" placeholder="Latitude" class="w-full p-3 border rounded" readonly>
                <input type="text" name="edit_longitude" id="edit_longitude" placeholder="Longitude" class="w-full p-3 border rounded" readonly>
            </div>
        </div>

            <div>
            <h3 class="font-semibold mb-2">Images</h3>
            <div class="grid grid-cols-3 gap-2 mb-2" id="edit_images_preview">
                <!-- Images will be dynamically inserted here -->
            </div>
            <input type="file" name="edit_image[0]" accept="image/*" class="w-full mb-2" onchange="previewNewImage(this,0)">
            <input type="file" name="edit_image[1]" accept="image/*" class="w-full mb-2" onchange="previewNewImage(this,1)">
            <input type="file" name="edit_image[2]" accept="image/*" class="w-full mb-2" onchange="previewNewImage(this,2)">
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Save Changes</button>
        </div>
    </form>
</div>
</div>

<script src=js/spot_manage.js></script>

</body>
</html>
