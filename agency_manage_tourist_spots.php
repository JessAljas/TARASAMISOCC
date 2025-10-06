<?php
session_start();
include 'db_connect.php';


if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

$success_msg = "";

// Code nga Handle sa pag add sa tourist spots 
if (isset($_POST['add_spot'])) {
    $tourist_owner_name  = trim($_POST['tourist_owner_name'] ?? '');
    $tourist_owner_email = trim($_POST['tourist_owner_email'] ?? '');
    $tourist_owner_phone = trim($_POST['tourist_owner_phone'] ?? '');
    $name = trim($_POST['name']);
    $location = trim($_POST['location']);
    $description = trim($_POST['description']);
    $activity = trim($_POST['activity']);
    $entrance_fee = $_POST['entrance_fee'] !== "" ? floatval($_POST['entrance_fee']) : 0;

    // Code nga ga handle Tourist Spot Owner
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

    // Code nga ga handle sa images
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

// ================== HANDLE EDIT NGA CODE ==================
if (isset($_POST['edit_spot_id'])) {
    $spot_id = intval($_POST['edit_spot_id']);
    $edit_name = trim($_POST['edit_name']);
    $edit_description = trim($_POST['edit_description']);
    $edit_location = trim($_POST['edit_location']);
    $edit_activity = trim($_POST['edit_activity']);
    $edit_fee = $_POST['edit_fee'] !== "" ? floatval($_POST['edit_fee']) : 0;
    $edit_status = $_POST['edit_status'] ?? 'pending';

    $stmt = $conn->prepare("SELECT image1, image2, image3 FROM tourist_spots WHERE id=?");
    $stmt->bind_param("i", $spot_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $images = [];
    for ($i = 0; $i < 3; $i++) {
        if (!empty($_FILES['edit_images']['name'][$i])) {
            $ext = pathinfo($_FILES['edit_images']['name'][$i], PATHINFO_EXTENSION);
            $images[$i] = 'spot_' . time() . "_$i." . $ext;
            move_uploaded_file($_FILES['edit_images']['tmp_name'][$i], 'uploads/' . $images[$i]);
        } else {
            $images[$i] = $existing['image'.($i+1)];
        }
    }

    $stmt = $conn->prepare("UPDATE tourist_spots 
        SET name_of_tourist_spot=?, description=?, location=?, activity=?, entrance_fee=?, status=?, image1=?, image2=?, image3=? 
        WHERE id=?");
    $stmt->bind_param("ssssdssssi", $edit_name, $edit_description, $edit_location, $edit_activity, $edit_fee, $edit_status, $images[0], $images[1], $images[2], $spot_id);
    $stmt->execute();
    $stmt->close();

    $_SESSION['success_msg'] = "Tourist spot successfully edited!";
    header("Location: agency_manage_tourist_spots.php");
    exit;
}

// ================== HANDLE DELETE SA TOURIST SPOTS ==================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: agency_manage_tourist_spots.php");
    exit;
}

// ================== THE SEARCH + PAGINATION CODE ==================
$search = trim($_GET['search'] ?? "");
$limit = 8;
$page  = max(1, intval($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;


$where = "";
$params = [];
$types = "";

if ($search !== "") {
    // Pwede esearch in name, location, or owner name (handling different posted_by_type)
    $where = "WHERE ts.name_of_tourist_spot LIKE ? 
              OR ts.location LIKE ? 
              OR (ts.posted_by_type='spot_owner' AND so.fullname LIKE ?) 
              OR (ts.posted_by_type='tourism_officers' AND 'Tourism Staff' LIKE ?)
              OR (ts.posted_by_type='agency' AND 'Agency' LIKE ?)";
    $term = "%$search%";
    $params = [$term, $term, $term, $term, $term];
    $types = "sssss";
}

// ================== COUNT TOTAL ROWS NGA CODE ==================
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

// ================== FETCH OR PAGKUHA SA SPOTS PARA SA POSTED BY ==================
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
    // code sa bind search params + limit ug offset
    $stmt->bind_param("sssssii", $params[0], $params[1], $params[2], $params[3], $params[4], $limit, $offset);
} else {
    $stmt->bind_param("ii", $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$spots = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();


$success_msg = $_SESSION['success_msg'] ?? "";
unset($_SESSION['success_msg']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Tourist Spots | Agency Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<style>body { font-family: 'Inter', sans-serif; background-color: #f3f4f6; }</style>
</head>
<body class="flex font-[Poppins]">

<?php include 'sidebar.php'; ?>

<div id="mainContent" class="flex-1 md:ml-64 p-6">

<!-- Success Message sa pag edit -->
<?php if ($success_msg): ?>
<div class="mb-4 p-3 bg-green-100 border border-green-300 text-green-700 rounded">
    <?= htmlspecialchars($success_msg) ?>
</div>
<?php endif; ?>

<!-- Search  nga code-->
<div class="flex justify-end mb-4">
    <form method="GET" class="flex items-center gap-2">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
               placeholder="Search..." 
               class="w-48 border rounded p-2 text-sm">
        <button class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 text-sm">
            <i class="fas fa-search"></i>
        </button>
    </form>
</div>

<!-- Tourist Spots Table code-->
<div class="bg-white shadow rounded-xl overflow-hidden">
    <table class="min-w-full border-collapse">
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
            <?php if($spots): foreach($spots as $spot): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-2 border font-semibold"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($spot['location']) ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($spot['owner_name'] ?? '-') ?></td>
                <td class="px-4 py-2 border"><?= htmlspecialchars($spot['activity']) ?></td>
                <td class="px-4 py-2 border"><?= $spot['entrance_fee'] !== null ? '₱'.number_format($spot['entrance_fee'],2) : 'Free' ?></td>
                <td class="px-4 py-2 border">
                    <?php
                    $status_class = 'bg-yellow-500 text-white px-2 py-1 rounded text-xs';
                    $status_text = 'Pending';
                    if($spot['status'] === 'verified'){ $status_class='bg-green-600 text-white px-2 py-1 rounded text-xs'; $status_text='Verified'; }
                    elseif($spot['status'] === 'rejected'){ $status_class='bg-red-600 text-white px-2 py-1 rounded text-xs'; $status_text='Rejected'; }
                    ?>
                    <span class="<?= $status_class ?>"><?= $status_text ?></span>
                </td>
                <td class="px-4 py-2 border text-sm text-gray-500">
                    <?= date("M d, Y h:i A", strtotime($spot['created_at'])) ?>
                </td>
                <td class="px-4 py-2 border text-center">
                    <div class="flex justify-center gap-2">
                        <!-- The Edit Button -->
                        <button onclick='openEditModal(<?= json_encode($spot, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)' 
                                class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600 text-sm">
                            <i class="fas fa-edit"></i>
                        </button>

                        <!-- The Delete Button -->
                        <a href="?delete_id=<?= $spot['id'] ?>" 
                           onclick="return confirm('Delete this spot?')" 
                           class="bg-red-500 text-white p-2 rounded hover:bg-red-600 text-sm">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="8" class="px-4 py-4 text-center text-gray-500">No tourist spots found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!--The Pagination Code-->
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

<!-- Add Button sa pag add ug tourist spots -->
<button onclick="window.location.href='agency_add_tourist_spots.php'" 
        class="fixed bottom-6 right-6 bg-green-500 text-white text-3xl w-16 h-16 rounded-full shadow-lg flex items-center justify-center hover:bg-green-600">
    <i class="fas fa-plus"></i>
</button>
</div>

<!-- Edit Modal sa Tourist Spots -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden overflow-auto z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6 relative">
    <!-- Close X button -->
    <button type="button" onclick="closeEditModal()" class="absolute top-3 right-3 text-gray-500 hover:text-black">
        <i class="fas fa-times text-xl"></i>
    </button>

    <h2 class="text-xl font-bold mb-4">Edit Tourist Spot</h2>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_spot_id" id="edit_spot_id">

        <div class="mb-3">
            <label class="block mb-1 font-medium">Name</label>
            <input type="text" name="edit_name" id="edit_name" class="w-full border rounded p-2">
        </div>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Description</label>
            <textarea name="edit_description" id="edit_description" rows="4" class="w-full border rounded p-2"></textarea>
        </div>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Location</label>
            <input type="text" name="edit_location" id="edit_location" class="w-full border rounded p-2">
        </div>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Activity</label>
            <input type="text" name="edit_activity" id="edit_activity" class="w-full border rounded p-2">
        </div>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Entrance Fee</label>
            <input type="number" step="0.01" name="edit_fee" id="edit_fee" class="w-full border rounded p-2">
        </div>

        <div class="mb-3">
            <label class="block mb-1 font-medium">Status</label>
            <select name="edit_status" id="edit_status" class="w-full border rounded p-2">
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="rejected">Rejected</option>
            </select>
        </div>

        <!-- Images container with horizontal scroll -->
        <div class="mb-3">
            <label class="block mb-1 font-medium">Images</label>
            <div id="edit_images_container" class="flex space-x-4 overflow-x-auto p-2 border rounded">
                <!-- Dynamically loaded images go here -->
            </div>
        </div>

        <div class="flex justify-end gap-2 mt-4">
            <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
            <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">Save</button>
        </div>
    </form>
  </div>
</div>

<script src="script.js"></script>
</body>
</html>
