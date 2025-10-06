<?php
session_start();
include 'db_connect.php';

// Only allow admin/agency users
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

$error = '';
$success = '';
$user_id = $_SESSION['user']['id'];

// Fetch tourist spots
$spots = [];
$res = $conn->query("SELECT id, name_of_tourist_spot FROM tourist_spots ORDER BY name_of_tourist_spot");
while ($row = $res->fetch_assoc()) $spots[] = $row;

// Handle Delete
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM package_destinations WHERE package_id=?");
    if($stmt){ $stmt->bind_param("i",$delete_id); $stmt->execute(); $stmt->close(); }

    $stmt = $conn->prepare("DELETE FROM packages WHERE id=?");
    if($stmt){
        $stmt->bind_param("i",$delete_id);
        if($stmt->execute()) $success = "Package deleted successfully.";
        else $error = "Failed to delete package.";
        $stmt->close();
    }
}

// Handle Update/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_package_id'])) {
    $package_id = intval($_POST['edit_package_id']);
    $title = trim($_POST['edit_title']);
    $price = $_POST['edit_price'];
    $description = trim($_POST['edit_description']);
    $pickup = trim($_POST['edit_pickup']);
    $dropoff = trim($_POST['edit_dropoff']);
    $destinations = $_POST['edit_destinations'] ?? [];

    // Fetch current status from DB to prevent admin/agency from changing it
    $stmt = $conn->prepare("SELECT status FROM packages WHERE id=?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $status = $stmt->get_result()->fetch_assoc()['status'] ?? 'pending';
    $stmt->close();

    $inclusions = [];
    $exclusions = [];
    for($i=1;$i<=4;$i++){
        $inclusions[] = trim($_POST["edit_inclusion$i"] ?? '');
        $exclusions[] = trim($_POST["edit_exclusion$i"] ?? '');
    }

    // Handle image uploads
    $images = [];
    for($i=0;$i<4;$i++){
        if(!empty($_FILES['edit_images']['name'][$i])){
            $filename = time()."_".basename($_FILES['edit_images']['name'][$i]);
            $target = "uploads/".$filename;
            if(move_uploaded_file($_FILES['edit_images']['tmp_name'][$i], $target)) $images[$i]=$filename;
        }
    }

    // Update package
    $fields = "title=?, description=?, price=?, pickup_location=?, dropoff_location=?,
               inclusion1=?, inclusion2=?, inclusion3=?, inclusion4=?,
               exclusion1=?, exclusion2=?, exclusion3=?, exclusion4=?, status=?";
    $params = [$title, $description, $price, $pickup, $dropoff,
               $inclusions[0], $inclusions[1], $inclusions[2], $inclusions[3],
               $exclusions[0], $exclusions[1], $exclusions[2], $exclusions[3],
               $status];
    $types = "ssdsssssssssss";

    for($i=0;$i<4;$i++){
        if(!empty($images[$i])){
            $fields .= ", image".($i+1)."=?";
            $types .= "s";
            $params[] = $images[$i];
        }
    }

    $types .= "i";
    $params[] = $package_id;

    $sql = "UPDATE packages SET $fields WHERE id=?";
    $stmt = $conn->prepare($sql);
    if($stmt){ $stmt->bind_param($types,...$params); $stmt->execute(); $stmt->close(); }

    // Update destinations
    $stmt = $conn->prepare("DELETE FROM package_destinations WHERE package_id=?");
    if($stmt){ $stmt->bind_param("i",$package_id); $stmt->execute(); $stmt->close(); }

    foreach($destinations as $order=>$spot_id){
        $stop_order = $order+1;
        $stmt = $conn->prepare("INSERT INTO package_destinations (package_id, tourist_spot_id, stop_order) VALUES (?,?,?)");
        if($stmt){ $stmt->bind_param("iii",$package_id,$spot_id,$stop_order); $stmt->execute(); $stmt->close(); }
    }

    $success = "Package updated successfully!";
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1) * $limit;

// Fetch total packages
$total_res = $conn->query("SELECT COUNT(*) AS total FROM packages");
$total_row = $total_res->fetch_assoc();
$total_packages = $total_row['total'];
$total_pages = ceil($total_packages/$limit);

// Fetch packages for current page
$packages = [];
$res = $conn->query("SELECT * FROM packages ORDER BY id DESC LIMIT $limit OFFSET $offset");
while($row=$res->fetch_assoc()) $packages[]=$row;

// Map destinations
$dest_map = [];
$res = $conn->query("SELECT package_id, tourist_spot_id FROM package_destinations");
while($row=$res->fetch_assoc()) $dest_map[$row['package_id']][] = $row['tourist_spot_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Packages</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body class="bg-gray-100 font-[Poppins]">

<div class="flex min-h-screen">
    <aside class="w-64 fixed h-full bg-gray-800 text-white">
        <?php include 'sidebar.php'; ?>
    </aside>

    <main class="flex-1 ml-64 mt-8 px-6 relative overflow-x-auto">
       <!-- Header -->
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

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#packagesTable tr");
  rows.forEach(row => {
    let text = row.innerText.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>

<?php if($error): ?><p class="text-red-600 mb-2"><?= $error ?></p><?php endif; ?>
<?php if($success): ?><p class="text-green-600 mb-2"><?= $success ?></p><?php endif; ?>

<div class="overflow-x-auto bg-white shadow rounded-lg p-4">
  <table class="w-full text-sm border border-gray-300 border-collapse">
    <thead>
      <tr class="bg-gray-200 text-left">
        <th class="px-4 py-2 border border-gray-300">Title</th>
        <th class="px-4 py-2 border border-gray-300">Price (₱)</th>
        <th class="px-4 py-2 border border-gray-300">Description</th>
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
        <td class="px-4 py-2 text-gray-700"><?= nl2br(htmlspecialchars($pkg['description'])) ?></td>
        <td class="px-4 py-2">
          <p class="text-sm"><strong>Pickup:</strong> <?= htmlspecialchars($pkg['pickup_location'] ?? '') ?></p>
          <p class="text-sm"><strong>Dropoff:</strong> <?= htmlspecialchars($pkg['dropoff_location'] ?? '') ?></p>
        </td>
        <td class="px-4 py-2 text-center">
          <?php
            $status = $pkg['status'] ?? 'pending';
            $color = match($status){
                'approved' => 'bg-green-200 text-green-800',
                'rejected' => 'bg-red-200 text-red-800',
                default => 'bg-yellow-200 text-yellow-800',
            };
          ?>
          <span class="px-2 py-1 rounded-full text-xs font-semibold <?= $color ?>">
            <?= ucfirst($status) ?>
          </span>
        </td>
        <td class="px-4 py-2 text-center">
          <div class="flex justify-center gap-2">
            <button onclick="openModal(<?= $pkg['id'] ?>)" 
              class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-xs flex items-center gap-1">
              <i class="fas fa-edit"></i> Edit
            </button>
            <a href="?delete_id=<?= $pkg['id'] ?>" 
               onclick="return confirm('Are you sure?');" 
               class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-xs flex items-center gap-1">
              <i class="fas fa-trash-alt"></i> Delete
            </a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- Pagination -->
<div class="mt-4 flex justify-center space-x-2">
  <a href="?page=<?= max(1,$page-1) ?>" class="underline px-2 py-1 <?= $page==1?'text-gray-400 pointer-events-none':'text-blue-600 hover:text-blue-800' ?>">Prev</a>
  <?php for($p=1;$p<=$total_pages;$p++): ?>
    <?php if($p == $page): ?>
      <span class="underline font-bold px-2 py-1 text-blue-800"><?= $p ?></span>
    <?php elseif($p <= 5): ?>
      <a href="?page=<?= $p ?>" class="underline px-2 py-1 text-blue-600 hover:text-blue-800"><?= $p ?></a>
    <?php endif; ?>
  <?php endfor; ?>
  <a href="?page=<?= min($total_pages,$page+1) ?>" class="underline px-2 py-1 <?= $page==$total_pages?'text-gray-400 pointer-events-none':'text-blue-600 hover:text-blue-800' ?>">Next</a>
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden overflow-auto z-50">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl max-h-[90vh] overflow-y-auto p-6 relative">
        <h3 class="text-lg font-semibold mb-4">Edit Package</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="edit_package_id" id="edit_package_id">

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

            <div class="mb-3">
                <label class="block mb-1 font-medium">Destinations</label>
                <div id="edit_dest_container" class="grid grid-cols-2 gap-2 max-h-48 overflow-auto border p-2 rounded"></div>
            </div>

            <div class="mb-3">
                <label class="block mb-1 font-medium">Images (Max 4)</label>
                <div id="edit_images_container" class="flex space-x-4 overflow-x-auto p-2 border rounded"></div>
                <input type="file" name="edit_images[]" multiple accept="image/*" class="mt-2">
            </div>

            <div class="flex justify-end space-x-2 mt-4">
                <button type="button" onclick="closeModal()" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">Cancel</button>
                <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">Save</button>
            </div>
        </form>
    </div>
</div>

</main>
</div>

<script>
const packages = <?= json_encode($packages) ?>;
const spots = <?= json_encode($spots) ?>;
const dest_map = <?= json_encode($dest_map) ?>;

function openModal(id){
    const pkg = packages.find(p=>p.id==id);
    if(!pkg) return;
    document.getElementById('edit_package_id').value = pkg.id;
    document.getElementById('edit_title').value = pkg.title;
    document.getElementById('edit_price').value = pkg.price;
    document.getElementById('edit_pickup').value = pkg.pickup_location;
    document.getElementById('edit_dropoff').value = pkg.dropoff_location;
    document.getElementById('edit_description').value = pkg.description;

    // Inclusions
    for(let i=1;i<=4;i++){
        document.getElementById('edit_inclusion'+i).value = pkg['inclusion'+i] || '';
        document.getElementById('edit_exclusion'+i).value = pkg['exclusion'+i] || '';
    }

    // Destinations
    const destContainer = document.getElementById('edit_dest_container');
    destContainer.innerHTML = '';
    const pkgDests = dest_map[pkg.id] || [];
    spots.forEach(s=>{
        const checked = pkgDests.includes(s.id) ? 'checked' : '';
        const div = document.createElement('div');
        div.classList.add('flex','items-center','gap-2');
        div.innerHTML = `<input type="checkbox" name="edit_destinations[]" value="${s.id}" ${checked}>
                         <label>${s.name_of_tourist_spot}</label>`;
        destContainer.appendChild(div);
    });

    // Images
    const imagesContainer = document.getElementById('edit_images_container');
    imagesContainer.innerHTML = '';
    for(let i=1;i<=4;i++){
        if(pkg['image'+i]){
            const img = document.createElement('img');
            img.src = 'uploads/' + pkg['image'+i];
            img.classList.add('w-24','h-24','object-cover','rounded');
            imagesContainer.appendChild(img);
        }
    }

    document.getElementById('editModal').classList.remove('hidden');
}

function closeModal(){
    document.getElementById('editModal').classList.add('hidden');
}
</script>

</body>
</html>
