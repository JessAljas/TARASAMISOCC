<?php 
session_start();
include 'db_connect.php';

// Check if ka login ba jud as spot owner
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: tourist_spot_login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];


// Ga Handle sa delete
if(isset($_GET['delete'])){
    $spot_id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM tourist_spots WHERE id=? AND owner_id=?");
    $stmt->bind_param("ii",$spot_id,$user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle sa update
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['update_spot'])){
    $spot_id = intval($_POST['spot_id']);
    $name = $_POST['spot_name'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $activity = $_POST['activity'];
    $entrance_fee = floatval($_POST['entrance_fee']);
    $latitude = floatval($_POST['latitude']);
    $longitude = floatval($_POST['longitude']);

    $old = $conn->query("SELECT image1,image2,image3 FROM tourist_spots WHERE id=$spot_id")->fetch_assoc();
    $images = [$old['image1'],$old['image2'],$old['image3']];
    $upload_dir = 'uploads/';
    if(!is_dir($upload_dir)) mkdir($upload_dir,0777,true);
    for($i=0;$i<3;$i++){
        if(!empty($_FILES['spot_images']['name'][$i])){
            $ext = pathinfo($_FILES['spot_images']['name'][$i], PATHINFO_EXTENSION);
            $images[$i] = 'spot_'.$spot_id."_".$i.".".$ext;
            move_uploaded_file($_FILES['spot_images']['tmp_name'][$i], $upload_dir.$images[$i]);
        }
    }

    $stmt = $conn->prepare("UPDATE tourist_spots 
        SET name_of_tourist_spot=?,description=?,location=?,activity=?,entrance_fee=?,latitude=?,longitude=?,image1=?,image2=?,image3=?,status='modified' 
        WHERE id=? AND owner_id=?");
    $stmt->bind_param("ssssdddsssii",$name,$description,$location,$activity,$entrance_fee,$latitude,$longitude,$images[0],$images[1],$images[2],$spot_id,$user_id);
    $stmt->execute();
    $stmt->close();
    header("Location: ".$_SERVER['PHP_SELF']."?page=".($_GET['page'] ?? 1)."&updated=1");
    exit;
}

// Pagination nga Setup
$per_page = 8;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$total_spots = $conn->query("SELECT COUNT(*) AS cnt FROM tourist_spots WHERE owner_id=$user_id")->fetch_assoc()['cnt'];
$total_pages = max(1, ceil($total_spots/$per_page));
$start = ($page-1)*$per_page;
$spots = $conn->query("SELECT * FROM tourist_spots WHERE owner_id=$user_id LIMIT $start,$per_page")->fetch_all(MYSQLI_ASSOC);
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
    <?php if(isset($_GET['updated']) && $_GET['updated']==1): ?>
        <div id="successMsg" class="mb-4 p-3 rounded bg-green-100 text-green-800 border border-green-300 text-center">
            Tourist spot successfully updated!
        </div>
    <?php endif; ?>

    <!-- LIST  sa TABLE -->
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
    <?php foreach($spots as $spot): ?>
        <tr class="border-b align-top">
            <!-- Name -->
            <td class="px-4 py-3 align-middle"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></td>

            <!-- Entrance Fee -->
            <td class="px-4 py-3 align-middle">₱<?= htmlspecialchars($spot['entrance_fee']) ?></td>

            <!-- Activity -->
            <td class="px-4 py-3 align-middle"><?= htmlspecialchars($spot['activity']) ?></td>

            <!-- Status -->
            <td class="px-4 py-3 align-middle">
                <?php if($spot['status']=='verified'): ?>
                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded text-sm font-semibold">Verified</span>
                <?php elseif($spot['status']=='pending'): ?>
                    <span class="bg-orange-100 text-orange-700 px-2 py-1 rounded text-sm font-semibold">Pending</span>
                <?php elseif($spot['status']=='modified'): ?>
                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded text-sm font-semibold">Modified</span>
                <?php else: ?>
                    <span class="bg-gray-100 text-gray-700 px-2 py-1 rounded text-sm font-semibold"><?= htmlspecialchars($spot['status']) ?></span>
                <?php endif; ?>
            </td>

            <!-- Actions -->
            <td class="px-4 py-3 align-middle flex gap-2">
                <button onclick="openModal(<?= $spot['id'] ?>)" class="bg-blue-500 text-white px-2 py-1 rounded">
                    <i class="fas fa-edit"></i>
                </button>
                <a href="?delete=<?= $spot['id'] ?>&page=<?= $page ?>" 
                   onclick="return confirm('Delete this spot?');" 
                   class="bg-red-500 text-white px-2 py-1 rounded">
                   <i class="fas fa-trash"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

    <!-- Pagination -->
    <div class="flex justify-center mt-4 gap-2">
        <?php if($page>1): ?>
            <a href="?page=<?= $page-1 ?>" class="text-blue-600 underline">Prev</a>
        <?php endif; ?>
        <?php for($p=1;$p<=$total_pages;$p++): ?>
            <a href="?page=<?= $p ?>" class="<?= $p==$page ? 'font-bold text-black' : 'text-blue-600 underline' ?>"><?= $p ?></a>
        <?php endfor; ?>
        <?php if($page<$total_pages): ?>
            <a href="?page=<?= $page+1 ?>" class="text-blue-600 underline">Next</a>
        <?php endif; ?>
    </div>

    <div class="text-center my-4">
        <a href="tourist_spot_owner_dashboard.php" class="underline text-blue-600 hover:text-blue-800">Back to Dashboard</a>
    </div>
</main>

<!-- Edit Modal -->
<div id="modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white p-6 rounded w-[95%] max-w-2xl relative overflow-y-auto max-h-[90vh] mx-auto">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-gray-600">
            <i class="fas fa-times"></i>
        </button>
        <form id="editForm" method="POST" enctype="multipart/form-data" class="grid gap-3">
            <input type="hidden" name="update_spot" value="1">
            <input type="hidden" name="spot_id" id="spot_id">

            <label>Spot Name</label>
            <input type="text" name="spot_name" id="spot_name" class="w-full border p-2 rounded">

            <label>Activities</label>
            <input type="text" name="activity" id="activity" class="w-full border p-2 rounded">

            <label>Location</label>
            <input type="text" name="location" id="location" class="w-full border p-2 rounded">

            <label>Description</label>
            <textarea name="description" id="description" class="w-full border p-2 rounded" rows="3"></textarea>

            <label>Entrance Fee (₱)</label>
            <input type="number" step="0.01" name="entrance_fee" id="entrance_fee" class="w-full border p-2 rounded">

            <label>Current Images</label>
            <div class="flex gap-2 flex-wrap" id="currentImages"></div>

            <label>Change Images</label>
            <div class="flex gap-2 flex-wrap">
                <input type="file" name="spot_images[0]" class="border p-1 rounded">
                <input type="file" name="spot_images[1]" class="border p-1 rounded">
                <input type="file" name="spot_images[2]" class="border p-1 rounded">
            </div>
            
            <label>Pick Location on Map</label>
            <div id="map" class="w-full h-48 rounded border"></div>
            <input type="text" name="latitude" id="latitude" readonly class="w-full border p-2 rounded mt-2" placeholder="Latitude">
            <input type="text" name="longitude" id="longitude" readonly class="w-full border p-2 rounded mt-2" placeholder="Longitude">

            <div class="flex justify-start mt-2">
                <button type="submit" class="bg-green-500 text-white w-24 py-1 rounded text-sm hover:bg-green-600">
                    <i class="fas fa-save"></i> Update
                </button>
            </div>
        </form>
    </div>
</div>


<script>
let spots = <?php echo json_encode($spots); ?>;
let modal = document.getElementById('modal');
let map, marker;

function openModal(id){
    let spot = spots.find(s => s.id == id);
    document.getElementById('spot_id').value = spot.id;
    document.getElementById('spot_name').value = spot.name_of_tourist_spot;
    document.getElementById('activity').value = spot.activity;
    document.getElementById('location').value = spot.location;
    document.getElementById('description').value = spot.description;
    document.getElementById('entrance_fee').value = spot.entrance_fee;
    document.getElementById('latitude').value = spot.latitude;
    document.getElementById('longitude').value = spot.longitude;

    let currentDiv = document.getElementById('currentImages');
    currentDiv.innerHTML = '';
    for(let i=1;i<=3;i++){
        if(spot['image'+i] && spot['image'+i]!==""){
            let img = document.createElement('img');
            img.src = 'uploads/'+spot['image'+i];
            img.className = 'h-16 w-24 object-cover border';
            currentDiv.appendChild(img);
        }
    }

    modal.classList.remove('hidden');
    setTimeout(() => {
        if(map) map.remove();
        map = L.map('map').setView([spot.latitude || 8.15, spot.longitude || 123.85], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'&copy; OpenStreetMap contributors'}).addTo(map);
        marker = L.marker([spot.latitude || 8.15, spot.longitude || 123.85]).addTo(map);
        map.on('click', function(e){
            document.getElementById('latitude').value = e.latlng.lat.toFixed(6);
            document.getElementById('longitude').value = e.latlng.lng.toFixed(6);
            marker.setLatLng(e.latlng);
        });
    },100);
}

function closeModal(){ modal.classList.add('hidden'); }

// Fade out  ang success message
const msg = document.getElementById('successMsg');
if(msg){
    setTimeout(() => {
        msg.style.transition = "opacity 0.5s";
        msg.style.opacity = 0;
        setTimeout(() => msg.remove(), 500);
    }, 3000);
}
</script>
</body>
</html>
