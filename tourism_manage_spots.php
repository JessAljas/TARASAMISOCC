<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}
// identify Tourism officers
$user = $_SESSION['user'];
$email = $user['email'];   
$fullname = $user['fullname'];
$message = "";


// Get spot_owner ID for para sa officer
$stmt = $conn->prepare("SELECT id FROM spot_owners WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

if ($res) {
    $owner_id = $res['id'];
} else {
    // create new spot_owner if ]wala pa ga exists
    $stmt = $conn->prepare("INSERT INTO spot_owners (fullname, email, password, phone_number, created_at) VALUES (?, ?, ?, ?, NOW())");
    $dummy_password = password_hash("defaultpassword", PASSWORD_DEFAULT);
    $dummy_phone = '';
    $stmt->bind_param("ssss", $fullname, $email, $dummy_password, $dummy_phone);
    $stmt->execute();
    $owner_id = $stmt->insert_id;
    $stmt->close();
}

// Handle sa Delete
if (isset($_GET['delete'])) {
    $spot_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE posted_by_type='tourism_officers' AND owner_id=? AND id=?");
    $stmt->bind_param("ii", $owner_id, $spot_id);
    if ($stmt->execute()) $message = "✅ Spot deleted successfully.";
    else $message = "❌ Error deleting spot: " . $stmt->error;
    $stmt->close();
}

// Handle sa Edit nga Submission
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

    // Get sa current nga images
    $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tourist_spots WHERE posted_by_type='tourism_officers' AND owner_id=? AND id=?");
    $stmt->bind_param("ii", $owner_id, $spot_id);
    $stmt->execute();
    $current_images = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $updated_images = [];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['edit_image']['name'][$i])) {
            $ext = pathinfo($_FILES['edit_image']['name'][$i], PATHINFO_EXTENSION);
            $filename = "spot_" . time() . "_$i." . $ext;
            if (move_uploaded_file($_FILES['edit_image']['tmp_name'][$i], "uploads/" . $filename)) {
                $updated_images[$i] = $filename;
            } else {
                $updated_images[$i] = $current_images["image".($i+1)];
            }
        } else {
            $updated_images[$i] = $current_images["image".($i+1)];
        }
    }

    // Update sa database
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

    if ($stmt->execute()) $message = "✅ Spot updated successfully!";
    else $message = "❌ Error updating spot: " . $stmt->error;
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

<div class="max-w-6xl mx-auto bg-gradient-to-r from-green-500 to-yellow-400 rounded-lg shadow p-6 mb-6">
    <h1 class="text-2xl font-bold text-white text-center">Manage Your Tourist Spots</h1>
</div>

<?php if($message): ?>
<div class="mb-4 p-4 border rounded bg-gray-50 text-gray-700 shadow"><?= htmlspecialchars($message) ?></div>
<?php endif; ?>

<div class="mb-4 flex items-center gap-2">
    <label for="searchInput" class="font-medium text-gray-700">Search:</label>
    <div class="relative flex-1">
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">🔍</span>
        <input type="text" id="searchInput" placeholder="Search spots..." class="w-50 p-3 pl-10 border rounded shadow focus:outline-none focus:ring focus:border-green-400">
    </div>
</div>

<table class="min-w-full border-collapse" id="spotsTable">
<thead>
<tr class="bg-green-400 text-white">
    <th class="p-2 border">ID</th>
    <th class="p-2 border">Spot Name</th>
    <th class="p-2 border">Owner Name</th>
    <th class="p-2 border">Location</th>
    <th class="p-2 border">Entrance Fee</th>
    <th class="p-2 border">Status</th>
    <th class="p-2 border">Actions</th>
</tr>
</thead>
<tbody id="tableBody">
<?php foreach($spots as $spot): ?>
<tr class="border-b hover:bg-gray-50">
    <td class="p-2 border"><?= $spot['id'] ?></td>
    <td class="p-2 border"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
    <td class="p-2 border"><?= htmlspecialchars($spot['owner_name']) ?></td>
    <td class="p-2 border"><?= htmlspecialchars($spot['location']) ?></td>
    <td class="p-2 border">₱<?= number_format($spot['entrance_fee'], 2) ?></td>
    <td class="p-2 border">
        <?php 
        $status = strtolower($spot['status']);
        $color = $status === 'approved' ? 'green' : ($status === 'pending' ? 'yellow' : 'green');
        ?>
        <span class="px-2 py-1 rounded text-white bg-<?= $color ?>-500"><?= ucfirst($spot['status']) ?></span>
    </td>
    <td class="p-2 border flex gap-2">
        <button onclick='openEditModal(<?= json_encode($spot) ?>)' 
                class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 flex items-center gap-1">
            <i class="fas fa-edit"></i> Edit
        </button>
        <a href="?delete=<?= $spot['id'] ?>" 
           onclick="return confirm('Are you sure?')" 
           class="px-2 py-1 bg-red-500 text-white rounded hover:bg-red-600 flex items-center gap-1">
            <i class="fas fa-trash-alt"></i> Delete
        </a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<div class="mt-4 flex justify-center gap-2" id="pagination"></div>

<div class="w-full flex justify-center mt-4">
    <a href="tourism_dashboard.php" class="text-blue-600 underline hover:text-blue-800 flex items-center gap-2">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

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
                <img src="" class="w-full h-32 object-cover rounded" id="img0">
                <img src="" class="w-full h-32 object-cover rounded" id="img1">
                <img src="" class="w-full h-32 object-cover rounded" id="img2">
            </div>
            <input type="file" name="edit_image[0]" accept="image/*" class="w-full mb-2">
            <input type="file" name="edit_image[1]" accept="image/*" class="w-full mb-2">
            <input type="file" name="edit_image[2]" accept="image/*" class="w-full mb-2">
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeEditModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Save Changes</button>
        </div>
    </form>
</div>
</div>

<script>
let editModal = document.getElementById('editModal');
let editMapInstance = null;

function openEditModal(spot) {
    editModal.classList.remove('hidden');
    document.getElementById('edit_spot_id').value = spot.id;
    document.getElementById('edit_name').value = spot.name_of_tourist_spot;
    document.getElementById('edit_owner_name').value = spot.owner_name;
    document.getElementById('edit_location').value = spot.location;
    document.getElementById('edit_description').value = spot.description;
    document.getElementById('edit_activity').value = spot.activity;
    document.getElementById('edit_entrance_fee').value = spot.entrance_fee;
    document.getElementById('edit_latitude').value = spot.latitude;
    document.getElementById('edit_longitude').value = spot.longitude;

    for (let i = 0; i < 3; i++) {
        document.getElementById('img' + i).src = spot['image' + (i+1)] ? 'uploads/' + spot['image' + (i+1)] : '';
    }

    if (editMapInstance) editMapInstance.remove();

    const lat = spot.latitude ? parseFloat(spot.latitude) : 8.15;
    const lng = spot.longitude ? parseFloat(spot.longitude) : 123.85;

    setTimeout(() => {
        editMapInstance = L.map('edit_map', { center: [lat, lng], zoom: 12 });
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(editMapInstance);

        let marker = L.marker([lat, lng], { draggable: true }).addTo(editMapInstance);

        marker.on('dragend', function(e) {
            const pos = e.target.getLatLng();
            document.getElementById('edit_latitude').value = pos.lat.toFixed(6);
            document.getElementById('edit_longitude').value = pos.lng.toFixed(6);
        });

        editMapInstance.on('click', function(e) {
            const clickLat = e.latlng.lat.toFixed(6);
            const clickLng = e.latlng.lng.toFixed(6);
            document.getElementById('edit_latitude').value = clickLat;
            document.getElementById('edit_longitude').value = clickLng;
            marker.setLatLng([clickLat, clickLng]);
        });

        editMapInstance.invalidateSize();
        editMapInstance.setView([lat, lng], 12);
    }, 300);
}

function closeEditModal() {
    editModal.classList.add('hidden');
    document.getElementById('editForm').reset();
}

// Simple search
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function() {
    const filter = searchInput.value.toLowerCase();
    document.querySelectorAll('#spotsTable tbody tr').forEach(row => {
        const text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>

</body>
</html>
