<?php
session_start();
include 'db_connect.php';

// Mo redirect ani nga code if wala naka login as admin/agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

// ================== CODE GA HANDLE SA ADD NEW OWNER ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_owner'])) {
    $fullname = trim($_POST['add_fullname']);
    $email = trim($_POST['add_email']);
    $password = password_hash(trim($_POST['add_password']), PASSWORD_DEFAULT);
    $phone = trim($_POST['add_phone_number']);
    $spot_name = trim($_POST['add_name_of_tourist_spot']); // keep it
    $profile_image = null;

    if (!empty($_FILES['add_profile_image']['name'])) {
        $ext = pathinfo($_FILES['add_profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image = 'profile_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['add_profile_image']['tmp_name'], 'uploads/' . $profile_image);
    }

    // Insert into sa spot_owners table including na ang default spot name
    $stmt = $conn->prepare("
        INSERT INTO spot_owners (fullname, email, password, phone_number, profile_image, name_of_tourist_spot, created_at) 
        VALUES (?,?,?,?,?,?, NOW())
    ");
    $stmt->bind_param("ssssss", $fullname, $email, $password, $phone, $profile_image, $spot_name);
    $stmt->execute();
    $stmt->close();

    // Do NOT create a row sa tourist_spots yet
    header("Location: agency_registered_tourist_spots.php");
    exit;
}

// ================== AJAX FETCH SPOTS PARA SA MODAL ==================
if(isset($_GET['action']) && $_GET['action'] == 'fetch_spots' && isset($_GET['owner_id'])){
    $owner_id = intval($_GET['owner_id']);
    $stmt = $conn->prepare("
        SELECT id, name_of_tourist_spot, created_at, posted_by_type
        FROM tourist_spots
        WHERE owner_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $owner_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $spots = [];
    while($row = $res->fetch_assoc()){
        $spots[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($spots);
    exit;
}

// ================== HANDLE SA PAG DELETE SA REGISTERED NGA TOURIST SPOTS ==================
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("SELECT profile_image FROM spot_owners WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($result && !empty($result['profile_image']) && file_exists("uploads/".$result['profile_image'])) {
        unlink("uploads/".$result['profile_image']);
    }
    $stmt = $conn->prepare("DELETE FROM spot_owners WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $stmt->close();
    header("Location: agency_registered_tourist_spots.php");
    exit;
}

// ================== HANDLE UPDATE SA REGISTERED NGA TOURIST SPOTS==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image = 'profile_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], 'uploads/' . $profile_image);

        $stmt = $conn->prepare("SELECT profile_image FROM spot_owners WHERE id=?");
        $stmt->bind_param("i",$id);
        $stmt->execute();
        $old = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($old && !empty($old['profile_image']) && file_exists("uploads/".$old['profile_image'])) {
            unlink("uploads/".$old['profile_image']);
        }
    }

    if ($profile_image) {
        $stmt = $conn->prepare("UPDATE spot_owners SET fullname=?, email=?, phone_number=?, profile_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $email, $phone, $profile_image, $id);
    } else {
        $stmt = $conn->prepare("UPDATE spot_owners SET fullname=?, email=?, phone_number=? WHERE id=?");
        $stmt->bind_param("sssi", $fullname, $email, $phone, $id);
    }
    $stmt->execute();
    $stmt->close();
    $_SESSION['edit_success'] = true;
    header("Location: agency_registered_tourist_spots.php");
    exit;
}

// ================== SEARCH & FETCH NGA CODE ==================
$search = '';
$whereClause = '';
$params = [];
$types = '';
if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $whereClause = "WHERE so.fullname LIKE ? OR so.email LIKE ? OR so.phone_number LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
    $types = "sss";
}
$total_owners = 0;
$res_total = $conn->query("SELECT COUNT(*) AS total FROM spot_owners");
if ($res_total) { $total_owners = $res_total->fetch_assoc()['total']; }
$sql = "SELECT so.* FROM spot_owners so $whereClause ORDER BY so.created_at DESC";
if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Registered Spot Owners | Agency Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css"/>
<style>
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:50; justify-content:center; align-items:center; padding:1rem; overflow-y:auto; }
.modal-content { background:#fff; padding:20px; border-radius:12px; max-width:900px; width:100%; max-height:90vh; overflow-y:auto; box-shadow:0 10px 25px rgba(0,0,0,0.3);}
#spotsContent { display:grid; grid-template-columns:1fr; gap:1.5rem; width:100%; }
</style>
</head>
<body class="bg-gray-100 flex font-[Poppins]">

<div class="w-64 bg-white-500 text-white min-h-screen sticky top-0">
    <?php include 'sidebar.php'; ?>
</div>

<div class="flex-1 flex flex-col min-h-screen">
<main class="flex-1 container mx-auto p-6">

<?php if (isset($_SESSION['edit_success'])): ?>
<div id="editSuccessMsg" 
     class="fixed top-4 right-4 bg-green-500 text-white px-4 py-2 rounded shadow z-50">
    ✅ Successfully edited!
</div>
<?php unset($_SESSION['edit_success']); ?>
<?php endif; ?>

<!-- Dashboard nga cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
<div class="bg-gradient-to-r from-yellow-400 to-green-500 shadow rounded-lg p-3 text-center text-white flex flex-col items-center">
<i class="fas fa-users text-4xl mb-3"></i>
<h2 class="text-lg font-semibold">Total Registered Spot Owners</h2>
<p class="text-3xl font-bold mt-2"><?= $total_owners ?></p>
</div>

<div class="bg-gradient-to-r from-yellow-400 to-green-500 shadow rounded-lg p-6 text-center flex flex-col justify-center text-white">
<h2 class="text-lg font-semibold mb-4">Add Tourist Spot</h2>
<a href="agency_add_tourist_spots.php" 
  class="bg-white text-green-600 font-semibold px-4 py-2 rounded shadow hover:bg-gray-100 flex items-center justify-center gap-2 transition">
  <i class="fas fa-plus"></i> Add Spot
</a>
</div>
</div>

<!-- Search & add buttons nga code -->
<div class="flex justify-between items-center mb-4">
  <form method="GET" class="flex items-center gap-2">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email, phone" class="p-2 border rounded shadow w-64">
    <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded shadow hover:bg-green-600 flex items-center gap-2">
      <i class="fas fa-search"></i> Search
    </button>
    <?php if($search): ?>
      <a href="agency_registered_tourist_spots.php" class="ml-2 text-gray-600 underline hover:text-gray-800">Clear</a>
    <?php endif; ?>
  </form>
 <button onclick="openAddModal()" 
  class="bg-gradient-to-r from-yellow-400 to-green-500 text-white px-4 py-2 rounded shadow hover:from-yellow-500 hover:to-green-600 flex items-center gap-2 transition">
    <i class="fas fa-user-plus"></i> Add Tourist Owner
</button>
</div>

<!-- Table sa registered nga tourist spot owner -->
<div class="overflow-x-auto bg-white shadow rounded-lg">
<table class="min-w-full">
<thead class="bg-green-500 text-white">
<tr>
<th class="px-6 py-3 text-left text-sm font-medium uppercase">Profile</th>
<th class="px-6 py-3 text-left text-sm font-medium uppercase">Full Name</th>
<th class="px-6 py-3 text-left text-sm font-medium uppercase">Email</th>
<th class="px-6 py-3 text-left text-sm font-medium uppercase">Phone</th>
<th class="px-6 py-3 text-center text-sm font-medium uppercase">Action</th>
</tr>
</thead>
<tbody class="divide-y divide-gray-200">
<?php if ($res && $res->num_rows > 0): ?>
  <?php while ($row = $res->fetch_assoc()): ?>
<tr class="hover:bg-gray-50">
<td class="px-6 py-4 whitespace-nowrap">
<?php if (!empty($row['profile_image']) && file_exists("uploads/".$row['profile_image'])): ?>
<img src="uploads/<?= htmlspecialchars($row['profile_image']) ?>" alt="Profile" class="w-12 h-12 rounded-full object-cover">
<?php else: ?>
<div class="w-12 h-12 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">N/A</div>
<?php endif; ?>
</td>
<td class="px-6 py-4"><?= htmlspecialchars($row['fullname']) ?></td>
<td class="px-6 py-4"><?= htmlspecialchars($row['email']) ?></td>
<td class="px-6 py-4"><?= htmlspecialchars($row['phone_number']) ?></td>
<td class="px-6 py-4 flex justify-center gap-3">
<button onclick="viewSpots(<?= $row['id'] ?>)" class="px-3 py-1 rounded bg-green-100 text-green-600 hover:bg-green-200 flex items-center gap-1" title="View Spots">
<i class="fas fa-eye"></i> 
</button>
<button class="px-3 py-1 rounded bg-yellow-400 text-white flex items-center gap-1 editBtn" 
        title="Edit"
        data-id="<?= $row['id'] ?>"
        data-fullname="<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>"
        data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>"
        data-phone="<?= htmlspecialchars($row['phone_number'], ENT_QUOTES) ?>"
        data-profile="<?= !empty($row['profile_image']) ? htmlspecialchars($row['profile_image'], ENT_QUOTES) : '' ?>">
<i class="fas fa-edit"></i> 
</button>
<a href="agency_registered_tourist_spots.php?delete_id=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this spot owner?');" class="px-3 py-1 rounded bg-red-100 text-red-600 hover:bg-red-200 flex items-center gap-1" title="Delete">
<i class="fas fa-trash"></i> 
</a>
</td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
<td colspan="5" class="px-6 py-4 text-center text-gray-500">No spot owners found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</main>
</div>

<!-- ================== MODALS NGA CODE================== -->
<!-- Add Owner nga Modal -->
<div id="addModal" class="modal">
  <div class="modal-content">
    <h2 class="text-xl font-semibold mb-4">Add Tourist Owner</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-3">
      <input type="text" name="add_fullname" placeholder="Full Name" required class="border p-2 rounded w-full">
      <input type="email" name="add_email" placeholder="Email" required class="border p-2 rounded w-full">
      <input type="password" name="add_password" placeholder="Password" required class="border p-2 rounded w-full">
      <input type="text" name="add_phone_number" placeholder="Phone Number" required class="border p-2 rounded w-full">
      <input type="text" name="add_name_of_tourist_spot" placeholder="Name of Tourist Spot" class="border p-2 rounded w-full">
      <input type="file" name="add_profile_image" class="border p-2 rounded w-full">
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeAddModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
        <button type="submit" name="add_owner" class="px-4 py-2 bg-green-500 text-white rounded">Add Owner</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Owner Modal -->
<div id="editModal" class="modal">
  <div class="modal-content">
    <h2 class="text-xl font-semibold mb-4">Edit Tourist Owner</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-3" id="editForm">
      <input type="hidden" name="update_id" id="edit_id">
      <input type="text" name="fullname" id="edit_fullname" placeholder="Full Name" required class="border p-2 rounded w-full">
      <input type="email" name="email" id="edit_email" placeholder="Email" required class="border p-2 rounded w-full">
      <input type="text" name="phone_number" id="edit_phone" placeholder="Phone Number" required class="border p-2 rounded w-full">
      <input type="file" name="profile_image" class="border p-2 rounded w-full">
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-green-400 text-white rounded">Update Owner</button>
      </div>
    </form>
  </div>
</div>

<!-- Spots Modal -->
<div id="spotsModal" class="modal">
  <div class="modal-content">
    <div class="flex justify-between items-center mb-3">
      <h2 class="text-xl font-semibold">Tourist Spots</h2>
      <button onclick="closeSpotsModal()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    </div>
    <div id="spotsContent"></div>
  </div>
</div>

<script>
// MODAL FUNCTIONS
function openAddModal(){ document.getElementById('addModal').style.display='flex'; }
function closeAddModal(){ document.getElementById('addModal').style.display='none'; }
function openEditModal(){ document.getElementById('editModal').style.display='flex'; }
function closeEditModal(){ document.getElementById('editModal').style.display='none'; }
function closeSpotsModal(){ document.getElementById('spotsModal').style.display='none'; }

// EDIT BUTTON LOGIC
document.querySelectorAll('.editBtn').forEach(btn=>{
    btn.addEventListener('click',()=>{
        document.getElementById('edit_id').value = btn.dataset.id;
        document.getElementById('edit_fullname').value = btn.dataset.fullname;
        document.getElementById('edit_email').value = btn.dataset.email;
        document.getElementById('edit_phone').value = btn.dataset.phone;
        openEditModal();
    });
});


// VIEW SPOTS
function viewSpots(ownerId){
    const modal = document.getElementById('spotsModal');
    const content = document.getElementById('spotsContent');
    modal.style.display='flex';
    content.innerHTML = '<p class="text-gray-500">Loading...</p>';
    fetch('agency_registered_tourist_spots.php?action=fetch_spots&owner_id='+ownerId)
        .then(res=>res.json())
        .then(data=>{
            if(data.length===0){ content.innerHTML='<p class="text-gray-500">No tourist spots found.</p>'; return; }
            let html = '';
            data.forEach(spot=>{
                html+=`<div class="p-3 border rounded shadow bg-gray-50">
                    <h3 class="font-semibold text-lg">${spot.name_of_tourist_spot}</h3>
                    <p class="text-sm text-gray-500">Posted by: ${spot.posted_by_type}</p>
                    <p class="text-sm text-gray-400">Created at: ${spot.created_at}</p>
                </div>`;
            });
            content.innerHTML = html;
        })
        .catch(err=>{ content.innerHTML='<p class="text-red-500">Error loading spots.</p>'; console.error(err); });
}

// AUTO HIDE SUCCESS SA MESSAGE
setTimeout(()=>{ const msg=document.getElementById('editSuccessMsg'); if(msg) msg.style.display='none'; },3000);

// VIEW SPOTS
function viewSpots(ownerId){
    const modal = document.getElementById('spotsModal');
    const content = document.getElementById('spotsContent');
    modal.style.display='flex';
    content.innerHTML = '<p class="text-gray-500">Loading...</p>';
    fetch('agency_registered_tourist_spots.php?action=fetch_spots&owner_id='+ownerId)
        .then(res=>res.json())
        .then(data=>{
            if(data.length===0){ 
                content.innerHTML='<p class="text-gray-500">No tourist spots found.</p>'; 
                return; 
            }
            let html = '';
            data.forEach(spot=>{
                html+=`
                <a href="agency_manage_tourist_spots.php?id=${spot.id}" 
                   class="block p-3 border rounded shadow bg-gray-50 mb-2 hover:bg-green-50 transition">
                    <h3 class="font-semibold text-lg text-green-700">${spot.name_of_tourist_spot}</h3>
                    <p class="text-sm text-gray-500">Posted by: ${spot.posted_by_type}</p>
                    <p class="text-sm text-gray-400">Created at: ${spot.created_at}</p>
                </a>`;
            });
            content.innerHTML = html;
        })
        .catch(err=>{
            content.innerHTML='<p class="text-red-500">Error loading spots.</p>'; 
            console.error(err); 
        });
}

</script>
</body>
</html>
