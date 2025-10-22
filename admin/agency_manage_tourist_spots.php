    <?php
    session_start();
    include '../config/db_connect.php';

    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
        header("Location: login.php");
        exit;
    }

    // ================== HANDLE ADD SPOT ==================
    if (isset($_POST['add_spot'])) {
        $tourist_owner_name  = trim($_POST['tourist_owner_name'] ?? '');
        $tourist_owner_email = trim($_POST['tourist_owner_email'] ?? '');
        $tourist_owner_phone = trim($_POST['tourist_owner_phone'] ?? '');
        $name = trim($_POST['name']);
        $location = trim($_POST['location']);
        $description = trim($_POST['description']);
        $activity = trim($_POST['activity']);
        $entrance_fee = $_POST['entrance_fee'] !== "" ? floatval($_POST['entrance_fee']) : 0;

        // Handle Tourist Spot Owner
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
            if ($stmt->execute()) {
                $owner_id = $stmt->insert_id;
            } else {
                die("Failed to create tourist owner: " . $stmt->error);
            }
            $stmt->close();
        }

        // Handle images
        $images = [null, null, null];
        for ($i = 0; $i < 3; $i++) {
            if (!empty($_FILES['image']['name'][$i])) {
                $ext = pathinfo($_FILES['image']['name'][$i], PATHINFO_EXTENSION);
                $images[$i] = 'spot_' . time() . "_$i." . $ext;
                move_uploaded_file($_FILES['image']['tmp_name'][$i], 'uploads/' . $images[$i]);
            }
        }

        $stmt = $conn->prepare("INSERT INTO tourist_spots 
            (owner_id, name_of_tourist_spot, description, location, activity, entrance_fee, image1, image2, image3, status, created_at, posted_by_type)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), 'spot_owner')");
        $stmt->bind_param("issssdsss", $owner_id, $name, $description, $location, $activity, $entrance_fee, $images[0], $images[1], $images[2]);
        $stmt->execute();
        $stmt->close();
    }

    // ================== HANDLE EDIT ==================
    if (isset($_POST['edit_spot_id'])) {
        $spot_id = intval($_POST['edit_spot_id']);
        $edit_name = trim($_POST['edit_name']);
        $edit_description = trim($_POST['edit_description']);
        $edit_location = trim($_POST['edit_location']);
        $edit_activity = trim($_POST['edit_activity']);
        $edit_fee = $_POST['edit_fee'] !== "" ? floatval($_POST['edit_fee']) : 0;

        $stmt = $conn->prepare("SELECT image1, image2, image3, status FROM tourist_spots WHERE id=?");
        $stmt->bind_param("i", $spot_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $images = [];
        for ($i = 1; $i <= 3; $i++) {
            $deleteFlag = $_POST['delete_image'.$i] ?? '0';
            $images[$i-1] = $existing['image'.$i];

            if (!empty($_FILES['edit_images']['name'][$i-1])) {
                $ext = pathinfo($_FILES['edit_images']['name'][$i-1], PATHINFO_EXTENSION);
                $images[$i-1] = 'spot_' . time() . "_$i." . $ext;
                move_uploaded_file($_FILES['edit_images']['tmp_name'][$i-1], '../uploads/' . $images[$i-1]);
                if (!empty($existing['image'.$i]) && file_exists('../uploads/'.$existing['image'.$i])) {
                    unlink('../uploads/'.$existing['image'.$i]);
                }
            }

            if ($deleteFlag === '1' && !empty($existing['image'.$i])) {
                if (file_exists('../uploads/'.$existing['image'.$i])) {
                    unlink('../uploads/'.$existing['image'.$i]);
                }
                $images[$i-1] = null;
            }
        }

        $current_status = $existing['status'];

        $stmt = $conn->prepare("UPDATE tourist_spots 
            SET name_of_tourist_spot=?, description=?, location=?, activity=?, entrance_fee=?, status=?, image1=?, image2=?, image3=? 
            WHERE id=?");
        $stmt->bind_param("ssssdssssi", $edit_name, $edit_description, $edit_location, $edit_activity, $edit_fee, $current_status, $images[0], $images[1], $images[2], $spot_id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['success_msg'] = "Tourist spot successfully edited!";
        header("Location: agency_manage_tourist_spots.php");
        exit;
    }

    // ================== HANDLE DELETE ==================
    if (isset($_GET['delete_id'])) {
        $delete_id = intval($_GET['delete_id']);
        $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        // Set delete message in session
        $_SESSION['delete_msg'] = "Tourist spot successfully deleted!";
        header("Location: agency_manage_tourist_spots.php");
        exit;
    }

    // ================== SEARCH & PAGINATION ==================
    $search = trim($_GET['search'] ?? "");
    $limit = 8;
    $page  = max(1, intval($_GET['page'] ?? 1));
    $offset = ($page - 1) * $limit;

    $where = "";
    $params = [];
    $types = "";

    if ($search !== "") {
        $where = "WHERE ts.name_of_tourist_spot LIKE ? 
                OR ts.location LIKE ? 
                OR (ts.posted_by_type='spot_owner' AND so.fullname LIKE ?) 
                OR (ts.posted_by_type='tourism_officers' AND 'Tourism Staff' LIKE ?)
                OR (ts.posted_by_type='agency' AND 'Agency' LIKE ?)";
        $term = "%$search%";
        $params = [$term, $term, $term, $term, $term];
        $types = "sssss";
    }

    // Count total rows
    $count_sql = "SELECT COUNT(*) AS total 
                FROM tourist_spots ts 
                LEFT JOIN spot_owners so ON ts.owner_id = so.id
                $where";
    $stmt = $conn->prepare($count_sql);
    if ($where) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $total_rows = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    $total_pages = ceil($total_rows / $limit);

    // Fetch spots
    $sql = "SELECT ts.*,
            CASE 
                WHEN ts.posted_by_type='spot_owner' THEN so.fullname
                WHEN ts.posted_by_type='tourism_officers' THEN 'Tourism Staff'
                WHEN ts.posted_by_type='agency' THEN 'Agency'
                ELSE 'Unknown'
            END AS owner_name
            FROM tourist_spots ts
            LEFT JOIN spot_owners so ON ts.owner_id = so.id
            $where
            ORDER BY ts.created_at DESC
            LIMIT ? OFFSET ?";
    $stmt = $conn->prepare($sql);
    if ($where) {
        $stmt->bind_param("sssssii", $params[0], $params[1], $params[2], $params[3], $params[4], $limit, $offset);
    } else {
        $stmt->bind_param("ii", $limit, $offset);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $spots = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // ================== HANDLE SESSION MESSAGES ==================
    $success_msg = $_SESSION['success_msg'] ?? "";
    unset($_SESSION['success_msg']);

    $delete_msg = $_SESSION['delete_msg'] ?? "";
    unset($_SESSION['delete_msg']);
    ?>


    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Tourist Spots | Agency Panel</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="flex font-[Poppins]">

    <?php include 'sidebar.php'; ?>

  <div id="mainContent" class="flex-1">
    <main class="max-w-5xl mx-auto mt-0 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-3">
                <span>Manage Tourist Spots</span>
            </h1>
        </div>

    <!-- Success Message sa pag edit -->
    <?php if ($success_msg): ?>
    <div id="successMsg" class="text-green-700 text-center">
        <?= htmlspecialchars($success_msg) ?>
    </div>
    <?php endif; ?>
    <!-- Delete Success Message -->
    <?php if ($delete_msg): ?>
    <div id="deleteMsg" 
        class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2
                bg-red-100 text-red-700 px-6 py-3 rounded shadow-lg text-center
                transition-opacity duration-500">
        <?= htmlspecialchars($delete_msg) ?>
    </div>
    <?php endif; ?>

    <!-- Search  nga code-->
 <div class="flex justify-end mb-4">
  <div class="flex items-center gap-2"><label for="liveSearch" class="font-medium text-gray-700 text-sm">Search:</label>
    <div class="relative">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
      <input type="text" id="liveSearch" placeholder="Search tourist spots..." 
        class="w-56 border rounded p-2 pl-9 text-sm focus:outline-none focus:ring-2 focus:ring-green-400">
    </div>
  </div>
</div>

  <!-- Tourist Spots Table -->
<div class="bg-white shadow rounded-xl overflow-x-auto">
    <table id="spotsTable" class="min-w-full border-collapse">
    <thead class="bg-gray-100">
      <tr>
        <th class="px-4 py-2 border text-left">Tourist Spots</th>
        <th class="px-4 py-2 border text-left">Location</th>
        <th class="px-4 py-2 border text-left">Owner</th>
        <th class="px-4 py-2 border text-left">Activity</th>
        <th class="px-4 py-2 border text-left">Entrance Fee</th>
        <th class="px-4 py-2 border text-left">Status</th>
        <th class="px-4 py-2 border text-left">Created At</th>
        <th class="px-4 py-2 border text-center">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($spots): foreach ($spots as $spot): ?>
        <tr class="hover:bg-gray-50">
          <td class="px-4 py-2 border font-semibold"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
          <td class="px-4 py-2 border"><?= htmlspecialchars($spot['location']) ?></td>
          <td class="px-4 py-2 border"><?= htmlspecialchars($spot['owner_name'] ?? '-') ?></td>
          <td class="px-4 py-2 border max-w-xs">
            <div class="overflow-y-auto max-h-32"><?= htmlspecialchars($spot['activity']) ?></div>
          </td>
          <td class="px-4 py-2 border"><?= $spot['entrance_fee'] !== null ? '₱'.number_format($spot['entrance_fee'], 2) : 'Free' ?></td>
          <td class="px-4 py-2 border">
            <?php
              $status_class = 'bg-yellow-500 text-white px-2 py-1 rounded text-xs';
              $status_text = 'Pending';
              if ($spot['status'] === 'verified') {
                $status_class = 'bg-green-600 text-white px-2 py-1 rounded text-xs';
                $status_text = 'Verified';
              } elseif ($spot['status'] === 'rejected') {
                $status_class = 'bg-red-600 text-white px-2 py-1 rounded text-xs';
                $status_text = 'Rejected';
              }
            ?>
            <span class="<?= $status_class ?>"><?= $status_text ?></span>
          </td>
          <td class="px-4 py-2 border text-sm text-gray-500"><?= date("M d, Y h:i A", strtotime($spot['created_at'])) ?></td>
          <td class="px-4 py-2 border text-center">
            <div class="flex justify-center gap-2">
                <button 
                class="editBtn bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-sm"
                data-spot='<?= json_encode($spot, JSON_HEX_APOS | JSON_HEX_QUOT) ?>'>
                <i class="fas fa-edit"></i>
                </button>

              <button 
                class="deleteBtn bg-red-500 text-white p-2 rounded hover:bg-red-600 text-sm"
                data-id="<?= $spot['id'] ?>"
                data-name="<?= htmlspecialchars($spot['name_of_tourist_spot'], ENT_QUOTES) ?>">
                <i class="fas fa-trash"></i>
              </button>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr>
          <td colspan="8" class="px-4 py-4 text-center text-gray-500">No tourist spots found.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- PAGINATION -->
<div class="flex justify-center items-center mt-6 space-x-2">
  <?php if ($page > 1): ?>
    <a href="?search=<?= urlencode($search) ?>&page=<?= $page-1 ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Previous</a>
  <?php else: ?>
    <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">Previous</span>
  <?php endif; ?>

  <span class="px-4 py-2 bg-green-500 text-white rounded">Page <?= $page ?> of <?= $total_pages ?></span>

  <?php if ($page < $total_pages): ?>
    <a href="?search=<?= urlencode($search) ?>&page=<?= $page+1 ?>" class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Next</a>
  <?php else: ?>
    <span class="px-4 py-2 bg-gray-100 text-gray-400 rounded cursor-not-allowed">Next</span>
  <?php endif; ?>
</div>

<!-- ADD NEW BUTTON -->
<button onclick="window.location.href='agency_add_tourist_spots.php'" 
  class="fixed bottom-6 right-6 bg-green-500 text-white text-3xl w-16 h-16 rounded-full shadow-lg flex items-center justify-center hover:bg-green-600">
  <i class="fas fa-plus"></i>
</button>

<!-- ===================== DELETE MODAL ===================== -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-[9999]">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 relative text-center">
    <button type="button" onclick="closeDeleteModal()" 
      class="absolute top-3 right-3 text-gray-400 hover:text-gray-700 transition">
      <i class="fas fa-times text-xl"></i>
    </button>
    <div class="mb-4">
      <h2 class="text-2xl font-semibold text-gray-800">Confirm Deletion</h2>
    </div>
    <p id="deleteModalText" class="text-gray-600 mb-6 leading-relaxed">Are you sure you want to delete this spot?</p>
    <div class="flex justify-center gap-4">
      <button type="button" onclick="closeDeleteModal()" class="px-5 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400 transition">Cancel</button>
      <a href="#" id="confirmDeleteBtn" class="px-5 py-2 bg-red-500 text-white rounded-md hover:bg-red-600 transition">Yes, Delete</a>
    </div>
  </div>
</div>
<!-- ================= EDIT MODAL ================= -->
<div id="editModal" class="hidden fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-[99999]">
  <div class="bg-white rounded-lg shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 relative">
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" id="edit_spot_id" name="edit_spot_id">

      <!-- Header -->
      <div class="flex justify-between items-center border-b pb-3 mb-3">
        <h2 class="text-2xl font-semibold text-gray-800">Edit Tourist Spot</h2>
        <button type="button" onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700 text-xl">&times;</button>
      </div>

      <!-- Spot Name -->
      <div>
        <label for="edit_name" class="font-semibold text-gray-700 text-sm">Spot Name</label>
        <input type="text" id="edit_name" name="edit_name" 
               class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="Enter spot name">
      </div>

      <!-- Description -->
      <div>
        <label for="edit_description" class="font-semibold text-gray-700 text-sm">Description</label>
        <textarea id="edit_description" name="edit_description" 
                  class="w-full border rounded-lg p-3 mt-1 h-32 resize-none focus:ring-2 focus:ring-blue-400 focus:outline-none"
                  placeholder="Enter description here..."></textarea>
      </div>

      <!-- Location -->
      <div>
        <label for="edit_location" class="font-semibold text-gray-700 text-sm">Location</label>
        <input type="text" id="edit_location" name="edit_location" 
               class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="Enter location">
      </div>

      <!-- Activity -->
      <div>
        <label for="edit_activity" class="font-semibold text-gray-700 text-sm">Activity</label>
        <input type="text" id="edit_activity" name="edit_activity" 
               class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="Enter activity">
      </div>

      <!-- Entrance Fee -->
      <div>
        <label for="edit_fee" class="font-semibold text-gray-700 text-sm">Entrance Fee (₱)</label>
        <input type="number" id="edit_fee" name="edit_fee" 
               class="w-full border rounded-lg p-2 mt-1 focus:ring-2 focus:ring-blue-400 focus:outline-none"
               placeholder="Enter fee amount">
      </div>

      <!-- Status -->
      <div>
        <label class="font-semibold text-gray-700 text-sm">Status</label>
        <div id="edit_status" class="w-full border rounded p-2 mt-1 text-center font-medium bg-yellow-100 text-yellow-700">Pending</div>
      </div>
      
    <!-- Map for Location edit -->
      <label>Pick Location on Map</label>
      <div id="map" class="w-full h-48 rounded border mb-2"></div>
      <div class="flex space-x-2">
          <input type="text" name="latitude" id="latitude" readonly 
                class="flex-1 border p-2 rounded" 
                placeholder="Latitude" aria-label="Latitude">
          <input type="text" name="longitude" id="longitude" readonly 
                class="flex-1 border p-2 rounded" 
                placeholder="Longitude" aria-label="Longitude">
      </div>

      <!-- Images -->
      <div>
        <label class="font-semibold text-gray-700 text-sm mb-2 block">Images</label>
       <div id="edit_images_container" class="flex gap-3 mt-2 flex-wrap"></div>
      </div>

      <!-- Buttons -->
      <div class="flex justify-end gap-3 mt-5">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded hover:bg-gray-400 transition">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-700 flex items-center gap-2 transition">
          <i class="fas fa-save"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script src="isset/spm.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("liveSearch");
  const table = document.getElementById("spotsTable"); //
  const rows = table.querySelectorAll("tbody tr");

  searchInput.addEventListener("input", function () {
    const query = this.value.toLowerCase().trim();

    rows.forEach(row => {
      const text = row.textContent.toLowerCase();
      row.style.display = text.includes(query) ? "" : "none";
    });
  });
});
</script>

</body>
</html>
