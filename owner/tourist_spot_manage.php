<?php 
session_start();
include '../config/db_connect.php';

// Check if logged in as spot owner
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Handle delete request securely with prepared statement (delete entire spot)
if (isset($_GET['delete'])) {
    $spot_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $spot_id, $user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_spot'])) {
    $spot_id = intval($_POST['spot_id']);
    $name = trim($_POST['spot_name']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $activity = trim($_POST['activity']);
    $entrance_fee = floatval($_POST['entrance_fee']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    if (empty($name)) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=NameRequired");
        exit;
    }

    // Fetch old images
    $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tourist_spots WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $spot_id, $user_id);
    $stmt->execute();
    $old = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$old) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=SpotNotFound");
        exit;
    }

    $images = [$old['image1'], $old['image2'], $old['image3']];
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    // Handle new uploaded images
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['spot_images']['name'][$i]) && $_FILES['spot_images']['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['spot_images']['name'][$i], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed_exts)) {
                $new_filename = 'spot_' . $spot_id . "_" . $i . "." . $ext;
                $target_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['spot_images']['tmp_name'][$i], $target_path)) {
                    if ($images[$i] && file_exists($upload_dir . $images[$i])) unlink($upload_dir . $images[$i]);
                    $images[$i] = $new_filename;
                }
            }
        }
    }

    // Handle deletion of individual images
    if (!empty($_POST['delete_images']) && is_array($_POST['delete_images'])) {
        foreach ($_POST['delete_images'] as $del_img) {
            $key = array_search($del_img, $images);
            if ($key !== false) {
                if (file_exists($upload_dir . $images[$key])) unlink($upload_dir . $images[$key]);
                $images[$key] = null;
            }
        }
    }

    // Update spot in DB
    $stmt = $conn->prepare("UPDATE tourist_spots 
        SET name_of_tourist_spot = ?, description = ?, location = ?, activity = ?, entrance_fee = ?, latitude = ?, longitude = ?, image1 = ?, image2 = ?, image3 = ?, status = 'modified' 
        WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ssssdddsssii", 
        $name, $description, $location, $activity, $entrance_fee, $latitude, $longitude, 
        $images[0], $images[1], $images[2], $spot_id, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . ($_GET['page'] ?? 1) . "&updated=1");
    exit;
}

// Pagination setup
$per_page = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Count total spots
$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM tourist_spots WHERE owner_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$total_spots = $stmt->get_result()->fetch_assoc()['cnt'];
$stmt->close();

$total_pages = max(1, ceil($total_spots / $per_page));
$start = ($page - 1) * $per_page;

// Fetch spots with pagination
$stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE owner_id = ? LIMIT ?, ?");
$stmt->bind_param("iii", $user_id, $start, $per_page);
$stmt->execute();
$result = $stmt->get_result();
$spots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Tourist Spots</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 min-h-screen font-[Poppins]">

<header class="bg-green-500 text-white p-8 shadow flex justify-center">
    <h1 class="text-2xl font-bold flex items-center gap-2">
        <i class="fas fa-map-marker-alt"></i> My Tourist Spots
    </h1>
</header>

<main class="p-6">

    <!-- Success Message -->
    <?php if(isset($_GET['updated']) && $_GET['updated'] == 1): ?>
        <div id="successMsg" class="text-green-800 text-center font-bold">
            ✅ Successfully edited!
        </div>
    <?php endif; ?>

    <!-- Error Message -->
    <?php if(isset($_GET['error'])): ?>
        <div class="mb-4 p-3 rounded bg-red-100 text-red-800 border border-red-300 text-center">
            <?= htmlspecialchars($_GET['error']) ?>
        </div>
    <?php endif; ?>

    <!-- Table List -->
    <table class="w-full bg-white rounded shadow overflow-hidden border-collapse">
        <thead class="bg-gray-300 text-black">
            <tr>
                <th class="px-4 py-2 text-left">Name</th>
                <th class="px-4 py-2 text-left">Entrance Fee</th>
                <th class="px-4 py-2 text-left">Activity</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($spots as $spot): ?>
            <tr class="border-b align-top">
                <td class="px-4 py-3 align-middle"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
                <td class="px-4 py-3 align-middle">₱<?= number_format((float)$spot['entrance_fee'], 2) ?></td>
                <td class="px-4 py-3 align-middle"><?= htmlspecialchars($spot['activity']) ?></td>
                <td class="px-4 py-3 align-middle">
                    <?php
                        $status = htmlspecialchars($spot['status']);
                        $status_classes = [
                            'verified' => 'bg-green-100 text-green-700',
                            'pending' => 'bg-orange-100 text-orange-700',
                            'modified' => 'bg-red-100 text-red-700'
                        ];
                        $class = $status_classes[$spot['status']] ?? 'bg-gray-100 text-gray-700';
                    ?>
                    <span class="<?= $class ?> px-2 py-1 rounded text-sm font-semibold"><?= ucfirst($status) ?></span>
                </td>
                <td class="px-4 py-3 align-middle flex gap-2">
                    <button onclick="openModal(<?= $spot['id'] ?>)" class="bg-blue-500 text-white px-2 py-1 rounded" aria-label="Edit spot <?= htmlspecialchars($spot['name_of_tourist_spot']) ?>">
                        <i class="fas fa-edit"></i>
                    </button>

                    <!-- Delete Button -->
                    <button 
                        onclick="openDeleteModal(<?= $spot['id'] ?>, '<?= htmlspecialchars(addslashes($spot['name_of_tourist_spot'])) ?>')" 
                        class="bg-red-500 text-white px-2 py-1 rounded flex items-center gap-1">
                        <i class="fas fa-trash"></i> Delete
                    </button>

                    <!-- Delete Modal -->
                    <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
                        <div class="bg-white rounded-lg shadow-lg p-6 w-96">
                            <h2 class="text-lg font-bold mb-4">Confirm Delete</h2>
                            <p id="deleteModalText" class="mb-6">Are you sure you want to delete this spot?</p>
                            <div class="flex justify-end gap-3">
                                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                                <a href="" id="confirmDeleteBtn" class="px-4 py-2 bg-red-500 text-white rounded">Delete</a>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="flex justify-center mt-4 gap-2" role="navigation" aria-label="Pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="text-blue-600 underline" aria-label="Previous page">Prev</a>
        <?php endif; ?>
        <?php for ($p = 1; $p <= $total_pages; $p++): ?>
            <a href="?page=<?= $p ?>" class="<?= $p == $page ? 'font-bold text-black' : 'text-blue-600 underline' ?>" aria-current="<?= $p == $page ? 'page' : 'false' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="text-blue-600 underline" aria-label="Next page">Next</a>
        <?php endif; ?>
    </div>

    <div class="text-center my-4">
        <a href="tourist_spot_owner_dashboard.php" class="underline text-blue-600 hover:text-blue-800">Back to Dashboard</a>
    </div>

</main>

<!-- Edit Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="bg-white p-6 rounded w-[95%] max-w-2xl relative overflow-y-auto max-h-[90vh] mx-auto">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-600" aria-label="Close modal">
            <i class="fas fa-times"></i>
        </button>
        <form id="editForm" method="POST" enctype="multipart/form-data" class="grid gap-3" novalidate>
            <input type="hidden" name="update_spot" value="1">
            <input type="hidden" name="spot_id" id="spot_id">

            <label for="spot_name">Spot Name</label>
            <input type="text" name="spot_name" id="spot_name" class="w-full border p-2 rounded" required>

            <label for="activity">Activities</label>
            <input type="text" name="activity" id="activity" class="w-full border p-2 rounded">

            <label for="location">Location</label>
            <input type="text" name="location" id="location" class="w-full border p-2 rounded">

            <label for="description">Description</label>
            <textarea name="description" id="description" class="w-full border p-2 rounded" rows="3"></textarea>

            <label for="entrance_fee">Entrance Fee (₱)</label>
            <input type="number" step="0.01" name="entrance_fee" id="entrance_fee" class="w-full border p-2 rounded" min="0">

            <label>Current Images</label>
            <div class="flex gap-2 flex-wrap" id="currentImages" aria-live="polite"></div>

            <label>Change Images</label>
            <div class="flex gap-2 flex-wrap">
                <input type="file" name="spot_images[0]" class="border p-1 rounded" accept="image/*">
                <input type="file" name="spot_images[1]" class="border p-1 rounded" accept="image/*">
                <input type="file" name="spot_images[2]" class="border p-1 rounded" accept="image/*">
            </div>

            <label>Pick Location on Map</label>
            <div id="map" class="w-full h-48 rounded border"></div>
            <input type="text" name="latitude" id="latitude" readonly class="w-full border p-2 rounded mt-2" placeholder="Latitude" aria-label="Latitude">
            <input type="text" name="longitude" id="longitude" readonly class="w-full border p-2 rounded mt-2" placeholder="Longitude" aria-label="Longitude">

            <div class="flex justify-start mt-2 gap-2">
                <button type="submit" class="bg-green-500 text-white w-24 py-1 rounded text-sm hover:bg-green-600 flex items-center justify-center gap-1" aria-label="Save changes">
                    <i class="fas fa-save"></i> Update
                </button>

                <button type="button" onclick="window.history.back()" class="bg-gray-300 text-black w-24 py-1 rounded text-sm hover:bg-red-400 flex items-center justify-center gap-1" aria-label="Cancel">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Floating Add Button as link -->
<div class="fixed bottom-6 right-6">
  <a href="add.php" class="w-16 h-16 rounded-full bg-green-500 hover:bg-green-600 text-white text-3xl font-bold flex items-center justify-center shadow-lg transition">
    +
  </a>
</div>

<input type="hidden" id="spotsData" value='<?= json_encode($spots, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>
<script>
    window.currentPage = <?= $page ?>;
</script>
<script src="js/login&manage.js"></script>
</body>
</html>
