        <?php
        session_start();
        include '../config/db_connect.php';

        // ✅ Restrict access
        if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agency'])) {
            header("Location: login.php");
            exit;
        }

        $error = '';
        $success = '';

        // ==================== FETCH TOURIST SPOTS ==================== //
        $spots = [];
        $res = $conn->query("SELECT id, name_of_tourist_spot FROM tourist_spots ORDER BY name_of_tourist_spot");
        while ($row = $res->fetch_assoc()) $spots[] = $row;

        // ==================== DELETE PACKAGE ==================== //
        if (isset($_GET['delete_id'])) {
            $delete_id = intval($_GET['delete_id']);
            $conn->query("DELETE FROM package_destinations WHERE package_id=$delete_id");
            $conn->query("DELETE FROM itinerary WHERE package_id=$delete_id");
            if ($conn->query("DELETE FROM packages WHERE id=$delete_id")) {
                $success = "Package deleted successfully.";
            } else {
                $error = "Failed to delete package.";
            }
        }

        // ==================== UPDATE PACKAGE ==================== //
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_package_id'])) {
            $package_id = intval($_POST['edit_package_id']);
            $title = trim($_POST['edit_title']);
            $price = floatval($_POST['edit_price']);
            $description = trim($_POST['edit_description']);
            $pickup = trim($_POST['edit_pickup']);
            $dropoff = trim($_POST['edit_dropoff']);
            $destinations = $_POST['edit_destinations'] ?? [];

            // Fetch current status
            $statusRes = $conn->query("SELECT status FROM packages WHERE id=$package_id");
            $status = $statusRes->fetch_assoc()['status'] ?? 'pending';

            // Inclusions & Exclusions
            $inclusions = [];
            $exclusions = [];
            for ($i = 1; $i <= 4; $i++) {
                $inclusions[] = trim($_POST["edit_inclusion$i"] ?? '');
                $exclusions[] = trim($_POST["edit_exclusion$i"] ?? '');
            }

            // ==================== HANDLE DELETED IMAGES ==================== //
            if (!empty($_POST['delete_image'])) {
                foreach ($_POST['delete_image'] as $delImg) {
                    // Delete file from server
                    $filePath = "../uploads/" . basename($delImg);
                    if (file_exists($filePath)) unlink($filePath);

                    // Clear image column in DB
                    for ($i = 1; $i <= 4; $i++) {
                        $col = "image$i";
                        $currentImg = $conn->query("SELECT $col FROM packages WHERE id=$package_id")->fetch_assoc()[$col];
                        if ($currentImg === $delImg) {
                            $conn->query("UPDATE packages SET $col=NULL WHERE id=$package_id");
                        }
                    }
                }
            }

            // ==================== UPDATE PACKAGE FIELDS ==================== //
            $stmt = $conn->prepare("UPDATE packages SET 
                title=?, description=?, price=?, pickup_location=?, dropoff_location=?,
                inclusion1=?, inclusion2=?, inclusion3=?, inclusion4=?,
                exclusion1=?, exclusion2=?, exclusion3=?, exclusion4=?, status=?
                WHERE id=?");
            $stmt->bind_param(
                "ssdsssssssssssi",
                $title, $description, $price, $pickup, $dropoff,
                $inclusions[0], $inclusions[1], $inclusions[2], $inclusions[3],
                $exclusions[0], $exclusions[1], $exclusions[2], $exclusions[3],
                $status, $package_id
            );
            $stmt->execute();
            $stmt->close();

            // ==================== UPDATE DESTINATIONS ==================== //
            $conn->query("DELETE FROM package_destinations WHERE package_id=$package_id");
            foreach ($destinations as $order => $spot_id) {
                $conn->query("INSERT INTO package_destinations (package_id, tourist_spot_id, stop_order) 
                            VALUES ($package_id, $spot_id, " . ($order + 1) . ")");
            }

            // ==================== HANDLE NEW IMAGE UPLOADS ==================== //
            if (!empty($_FILES['edit_images']['name'][0])) {
                for ($i = 0; $i < count($_FILES['edit_images']['name']); $i++) {
                    if (!empty($_FILES['edit_images']['name'][$i])) {
                        $filename = time() . "_" . basename($_FILES['edit_images']['name'][$i]);
                        $target = "../uploads/" . $filename;

                        if (move_uploaded_file($_FILES['edit_images']['tmp_name'][$i], $target)) {
                            // Find first empty image slot
                            for ($j = 1; $j <= 4; $j++) {
                                $col = "image$j";
                                $currentImg = $conn->query("SELECT $col FROM packages WHERE id=$package_id")->fetch_assoc()[$col];
                                if (empty($currentImg)) {
                                    $conn->query("UPDATE packages SET $col='$filename' WHERE id=$package_id");
                                    break;
                                }
                            }
                        }
                    }
                }
            }

            // ==================== UPDATE ITINERARY ==================== //
            if (!empty($_POST['itinerary_time'])) {
                $stmtUpdate = $conn->prepare("UPDATE itinerary SET time=?, destination_name=?, activity_type=? WHERE id=? AND package_id=?");
                $stmtInsert = $conn->prepare("INSERT INTO itinerary (package_id, destination_name, time, activity_type) VALUES (?, ?, ?, ?)");

                foreach ($_POST['itinerary_time'] as $it_id => $time) {
                    $activity = trim($_POST['itinerary_activity'][$it_id] ?? '');
                    $type = $_POST['itinerary_type'][$it_id] ?? 'arrival';
                    $time_db = date("H:i:s", strtotime($time));

                    if (is_numeric($it_id) && $it_id > 0) {
                        $stmtUpdate->bind_param("sssii", $time_db, $activity, $type, $it_id, $package_id);
                        $stmtUpdate->execute();
                    } else {
                        $stmtInsert->bind_param("isss", $package_id, $activity, $time_db, $type);
                        $stmtInsert->execute();
                    }
                }

                $stmtUpdate->close();
                $stmtInsert->close();
            }

            $success = "Package and itinerary updated successfully!";
        }

        // ==================== PAGINATION ==================== //
        $limit = 5;
        $page = max(1, intval($_GET['page'] ?? 1));
        $offset = ($page - 1) * $limit;

        // ==================== FETCH PACKAGES ==================== //
        $total = $conn->query("SELECT COUNT(*) AS total FROM packages")->fetch_assoc()['total'];
        $total_pages = ceil($total / $limit);

        $packages = [];
        $res = $conn->query("SELECT * FROM packages ORDER BY id DESC LIMIT $limit OFFSET $offset");
        while ($row = $res->fetch_assoc()) $packages[] = $row;

        // ==================== FETCH DESTINATIONS MAP ==================== //
        $dest_map = [];
        $res = $conn->query("SELECT package_id, tourist_spot_id FROM package_destinations");
        while ($row = $res->fetch_assoc()) $dest_map[$row['package_id']][] = $row['tourist_spot_id'];

        // ==================== FETCH ITINERARIES ==================== //
        $itineraries = [];
        $res = $conn->query("SELECT id, package_id, destination_name, time, activity_type FROM itinerary ORDER BY package_id, time ASC");
        while ($row = $res->fetch_assoc()) $itineraries[$row['package_id']][] = $row;
        ?>

        <!DOCTYPE html>
        <html lang="en">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Manage Packages</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
            <link rel="stylesheet" href="css/style.css">
        </head>

        <body class="bg-gray-100 font-[Poppins]">

        <div class="flex min-h-screen">
        <div class="fixed top-0 left-0 h-screen w-64 bg-white shadow-md overflow-y-auto z-20">
        <?php include 'sidebar.php'; ?>
        </div>

        <main class="flex-1 ml-64 mt-8 px-6 relative overflow-x-auto">

        <div class="bg-white shadow rounded-lg p-6 mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <h2 class="text-lg font-semibold flex items-center gap-4">Manage Packages</h2>
        <div class="flex items-center gap-2">
            <label for="searchInput" class="font-medium text-gray-700">Search:</label>
            <input type="text" id="searchInput" placeholder="Search packages..." class="p-2 border rounded w-64">
            <a href="agency_add_package.php" 
            class="bg-gradient-to-r from-yellow-400 to-green-500 text-white px-4 py-2 rounded shadow hover:from-yellow-500 hover:to-green-600 text-sm flex items-center gap-2 transition">
            <i class="fas fa-plus"></i> Add New Package
            </a>
        </div>
        </div>

        <?php if($error): ?>
        <p id="errorMsg" class="text-red-600 mb-2 text-center"><?= $error ?></p>
        <?php endif; ?>

        <?php if($success): ?>
        <p id="successMsg" class="text-green-600 mb-2 text-center"><?= $success ?></p>
        <?php endif; ?>


<div class="overflow-x-auto bg-white shadow rounded-lg p-4">
  <table class="w-full text-sm border border-gray-300 border-collapse">
    <thead>
      <tr class="bg-gray-200 text-left">
        <th class="px-4 py-2 border border-gray-300">Title</th>
        <th class="px-4 py-2 border border-gray-300">Price (₱)</th>
        <!-- Removed Description -->
        <th class="px-4 py-2 border border-gray-300">Meet Up Place</th>
        <th class="px-4 py-2 border border-gray-300 text-center">Status</th>
        <th class="px-4 py-2 border border-gray-300 text-center">Actions</th>
      </tr>
    </thead>
    <tbody id="packagesTable">
      <?php foreach($packages as $pkg): ?>
      <tr class="hover:bg-gray-50 align-top border-t border-gray-300">
        <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($pkg['title']) ?></td>
        <td class="px-4 py-2"><?= number_format($pkg['price'],2) ?></td>
        <!-- Removed Description -->
        <td class="px-4 py-2">
          <p class="text-sm"><strong>Pickup:</strong> <?= htmlspecialchars($pkg['pickup_location'] ?? '') ?></p>
          <p class="text-sm"><strong>Dropoff:</strong> <?= htmlspecialchars($pkg['dropoff_location'] ?? '') ?></p>
        </td>
        <td class="px-4 py-2 text-center">
          <?php
            $status = !empty($pkg['status']) ? strtolower($pkg['status']) : 'pending';
            $color = match($status){
                'approved' => 'bg-green-200 text-green-800',
                'rejected' => 'bg-red-200 text-red-800',
                default => 'bg-yellow-200 text-yellow-800',
            };
          ?>
          <span class="px-3 py-1 rounded-full text-sm font-semibold <?= $color ?>">
            <?= ucfirst($status ?: 'Pending') ?>
          </span>
        </td>
        <td class="px-4 py-2 text-center">
          <div class="flex justify-center gap-2">
            <!-- Edit Button -->
            <button onclick="openEditModal(<?= $pkg['id'] ?>)" 
                class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-xs flex items-center gap-1">
            <i class="fas fa-edit"></i> Edit
            </button>

            <!-- Delete Button -->
            <button 
                class="deleteBtn bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs flex items-center gap-1"
                data-id="<?= $pkg['id'] ?>"
                data-title="<?= htmlspecialchars($pkg['title']) ?>">
            <i class="fas fa-trash-alt"></i> Delete
            </button>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
        <div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-lg p-6 max-w-sm w-full">
            <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
            <p id="deleteModalText" class="mb-4">Are you sure you want to delete this package?</p>
            <div class="flex justify-end gap-2">
            <button onclick="closeDeleteModal()" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancel</button>
            <a href="#" id="confirmDeleteBtn" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Yes, Delete</a>
            </div>
        </div>
        </div>


        <!-- Edit Modal -->
        <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden overflow-auto z-50">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6 relative">
                <h3 class="text-lg font-semibold mb-4">Edit Package</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="edit_package_id" id="edit_package_id">

                    <!-- Title / Price / Pickup / Dropoff / Description / Inclusions / Exclusions -->
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Title</label>
                        <input type="text" name="edit_title" id="edit_title" required class="w-full p-2 border rounded">
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Price (₱)</label>
                        <input type="number" step="0.01" name="edit_price" id="edit_price" required class="w-full p-2 border rounded">
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Pickup Location</label>
                        <input type="text" name="edit_pickup" id="edit_pickup" class="w-full p-2 border rounded">
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Dropoff Location</label>
                        <input type="text" name="edit_dropoff" id="edit_dropoff" class="w-full p-2 border rounded">
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Description</label>
                        <textarea name="edit_description" id="edit_description" rows="4" class="w-full p-2 border rounded"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Inclusions (max 4)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php for($i=1;$i<=4;$i++): ?>
                                <input type="text" name="edit_inclusion<?= $i ?>" id="edit_inclusion<?= $i ?>" class="w-full p-2 border rounded">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Exclusions (max 4)</label>
                        <div class="grid grid-cols-2 gap-2">
                            <?php for($i=1;$i<=4;$i++): ?>
                                <input type="text" name="edit_exclusion<?= $i ?>" id="edit_exclusion<?= $i ?>" class="w-full p-2 border rounded">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <!-- Destinations -->
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Destinations</label>
                        <div id="edit_dest_container" class="grid grid-cols-2 gap-2 max-h-48 overflow-auto border p-2 rounded"></div>
                    </div>

                <!-- Images -->
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Images (Max 4)</label>
                        <div id="edit_images_container" class="flex space-x-4 overflow-x-auto p-2 border rounded"></div>
                        <input type="file" name="edit_images[]" multiple accept="image/*" class="mt-2">
                    </div>

                    <!-- 🆕 Itinerary Editor -->
                    <div class="mb-3">
                        <label class="block mb-1 font-medium">Itinerary Schedule</label>
                        <div id="edit_itinerary_container" class="space-y-2 max-h-96 overflow-y-auto border p-3 rounded bg-gray-50 text-sm"></div>
                        <button type="button" onclick="addNewItineraryRow()" class="mt-2 bg-green-500 text-white px-2 py-1 rounded hover:bg-blue-600 text-sm">+ Add Row</button>
                    </div>

                    <div class="flex justify-end space-x-2 mt-4">
                        <button type="button" onclick="closeEditModal()" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancel</button>
                        <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>
                    </div>
                </form>
            </div>
        </div>

    <script>
        const packages = <?= json_encode($packages) ?>;
        const spots = <?= json_encode($spots) ?>;
        const dest_map = <?= json_encode($dest_map) ?>;
        const itineraries = <?= json_encode($itineraries) ?>;
    </script>

    <script src="isset/manage_package.js"></script>


       