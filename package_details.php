<?php
session_start();
include 'db_connect.php';

$package_id = $_GET['id'] ?? 0;

// Fetch  sa package
$stmt = $conn->prepare("SELECT * FROM packages WHERE id=?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch destinations with lat/lng ug activities para sa map
$destinations = [];
$stmt = $conn->prepare("
    SELECT ts.id, 
           ts.name_of_tourist_spot AS name, 
           ts.location, 
           ts.description, 
           ts.activity, 
           ts.latitude, 
           ts.longitude, 
           ts.image1, ts.image2, ts.image3
    FROM package_destinations pd
    JOIN tourist_spots ts ON pd.tourist_spot_id = ts.id
    WHERE pd.package_id=? 
    ORDER BY pd.stop_order
");

$stmt->bind_param("i", $package_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $destinations[] = $row;
}
$stmt->close();

// ------------------ BALANCED NGA ITINERARY TIME ------------------ //
$startTime = strtotime("08:00 AM");
$endTime   = strtotime("05:00 PM"); 
$time = $startTime;

// Pickup and Drop-off gikan sa database
$pickup = $package['pickup_location'] ?? "Pick-up location not set";
$dropoff = $package['dropoff_location'] ?? "Drop-off location not set";

// Durations
$travelFirst = 15 * 60;
$travelNext  = 30 * 60;
$visitTime   = 60 * 60; // 1 hour per visit
$lunchTime   = 60 * 60;

// Split destinations: 2 in morning, 2 in afternoon
$morningDest = array_slice($destinations, 0, 2);
$afternoonDest = array_slice($destinations, 2, 2);

$morningItinerary = [];
$afternoonItinerary = [];

// --- Sa Morning ---
$morningItinerary[] = ['time' => date("H:i", $time), 'activity' => $pickup];

foreach ($morningDest as $i => $dest) {
    $time += ($i == 0) ? $travelFirst : $travelNext;
    $morningItinerary[] = ['time' => date("H:i", $time), 'activity' => "Travel to " . $dest['name']];
    
    $time += $visitTime;
    $visitText = "Visit " . $dest['name'];
    if (!empty($dest['activity'])) {
        $visitText .= " (Activities: " . $dest['activity'] . ")";
    }
    $morningItinerary[] = ['time' => date("H:i", $time), 'activity' => $visitText];
}

// --- Lunch Time ---
$time = strtotime("12:00 PM");
$morningItinerary[] = ['time' => date("H:i", $time), 'activity' => "Lunch Break"];
$time += $lunchTime;

// --- Sa Afternoon ---
foreach ($afternoonDest as $i => $dest) {
    $time += $travelNext;
    $afternoonItinerary[] = ['time' => date("H:i", $time), 'activity' => "Travel to " . $dest['name']];
    
    $time += $visitTime;
    $visitText = "Visit " . $dest['name'];
    if (!empty($dest['activity'])) {
        $visitText .= " (Activities: " . $dest['activity'] . ")";
    }
    $afternoonItinerary[] = ['time' => date("H:i", $time), 'activity' => $visitText];
}

// --- Drop-off at exactly 5 PM ---
$afternoonItinerary[] = ['time' => date("H:i", $endTime), 'activity' => "Drop-off at " . $dropoff];


// Fetch inclusions & exclusions dynamically (max ug 4)
$inclusions = [];
$exclusions = [];
for ($i = 1; $i <= 4; $i++) {
    if (!empty($package["inclusion$i"])) $inclusions[] = $package["inclusion$i"];
    if (!empty($package["exclusion$i"])) $exclusions[] = $package["exclusion$i"];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($package['title']) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-[Poppins]">

<?php include 'header.php'; ?>

<main class="max-w-7xl mx-auto mt-8 p-4">

<h1 class="text-2xl md:text-2xl font-bold mb-4"><?= htmlspecialchars($package['title']) ?></h1>


<!-- Image Slider -->
<div class="swiper-container mb-6 rounded-lg overflow-hidden shadow">
  <div class="swiper-wrapper">
    <?php for($i=1;$i<=4;$i++):
        $img = $package["image$i"] ?? null;
        if($img && file_exists("uploads/".$img)): ?>
      <div class="swiper-slide">
        <img src="uploads/<?= htmlspecialchars($img) ?>" class="w-full h-60 object-cover">
      </div>
    <?php endif; endfor; ?>
  </div>

  <!-- Pagination -->
  <div class="swiper-pagination"></div>

  <!-- Navigation) -->
  <div class="swiper-button-next"></div>
  <div class="swiper-button-prev"></div>
</div>

<!-- Swiper JS nga link -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

<script>
  new Swiper('.swiper-container', {
    loop: true,
    autoplay: {
      delay: 3000, // 3 seconds taga slide
      disableOnInteraction: false, // mo continue gihapon ug autoplay even ang user mo interacts
    },
    pagination: {
      el: '.swiper-pagination',
      clickable: true,
    },
    navigation: {
      nextEl: '.swiper-button-next',
      prevEl: '.swiper-button-prev',
    },
    effect: 'slide', 
    speed: 800 
  });
</script>


<!-- Description -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-2">Description</h2>
    <p class="text-gray-700 leading-relaxed">
        <?= nl2br(htmlspecialchars($package['description'] ?? '')) ?>
    </p>
</div>

<!-- Destinations sa package -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-2">Destinations</h2>
    <ul class="space-y-6">
        <?php foreach($destinations as $index => $dest): ?>
        <li class="border-b pb-6">
            <!-- Multiple Images nga naa sa Center -->
            <div class="flex justify-center space-x-4 mb-4">
                <?php for($i=1;$i<=3;$i++): 
                    $imgField = "image$i";
                    if (!empty($dest[$imgField]) && file_exists("uploads/".$dest[$imgField])): ?>
                        <img src="uploads/<?= htmlspecialchars($dest[$imgField]) ?>" 
                            class="w-64 h-48 object-cover rounded-lg shadow">
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if (empty($dest['image1']) && empty($dest['image2']) && empty($dest['image3'])): ?>
                    <div class="w-64 h-48 bg-gray-200 flex items-center justify-center text-gray-500 text-sm rounded-lg">
                        No Images
                    </div>
                <?php endif; ?>
            </div>

            <!-- Destination details -->
            <div>
                <span class="font-medium text-xl"><?= htmlspecialchars($dest['name']) ?></span>
                <p class="text-gray-500 text-sm"><?= htmlspecialchars($dest['location']) ?></p>
                
                <!-- Description nga naay Read More -->
                <?php 
                $desc = htmlspecialchars($dest['description'] ?? '');
                $shortDesc = strlen($desc) > 500 ? substr($desc,0,200)."..." : $desc;
                ?>
                <div class="relative mt-2 overflow-hidden max-h-24 transition-all duration-500" id="desc-container-<?= $index ?>">
                    <p class="text-gray-600 text-sm" id="desc-text-<?= $index ?>"><?= $desc ?></p>
                </div>
                <?php if(strlen($desc) > 50): ?>
                <button onclick="toggleDesc(<?= $index ?>)" 
                        class="text-green-700 font-semibold mt-1" id="desc-btn-<?= $index ?>">
                    Read More
                </button>
                <?php endif; ?>

                <?php if(!empty($dest['activity'])): ?>
                <div class="mt-3">
                    <p class="font-semibold text-gray-700">Activities:</p>
                    <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($dest['activity'])) ?></p>
                </div>
                <?php endif; ?>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<script>
function toggleDesc(index){
    const container = document.getElementById('desc-container-' + index);
    const btn = document.getElementById('desc-btn-' + index);
    
    if(container.style.maxHeight && container.style.maxHeight !== "6rem"){ // mo collapsed
        container.style.maxHeight = "6rem"; // mo collapse
        btn.textContent = "Read More";
    } else { 
        container.style.maxHeight = container.scrollHeight + "px"; // expand ug smoothly
        btn.textContent = "Read Less";
    }
}
</script>


<!-- Map -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-2">Map</h2>
    <div id="map" class="w-full h-80 rounded"></div>
</div>

<!-- Inclusions & Exclusions sa package -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div class="bg-white p-6 rounded shadow">
           <span class="text-green-600 font-bold text-xl">Inclusion ✓</span>
        <ul class="list-disc list-inside space-y-1">
            <?php foreach($inclusions as $inc): ?>
            <li><?= htmlspecialchars($inc) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="bg-white p-6 rounded shadow">
        <span class="text-red-600 font-bold text-xl">Exclusion ✗</span>
        <ul class="list-disc list-inside space-y-1">
            <?php foreach($exclusions as $exc): ?>
            <li><?= htmlspecialchars($exc) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<!-- Itinerary -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-4">Itinerary</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <ul class="space-y-3">
                <!-- Pickup location as first item -->
                <li class="flex justify-between border-b pb-2">
                    <span class="font-medium"><?= date("h:i A", strtotime($package['pickup_time'] ?? '08:00')) ?></span>
                    <span class="text-gray-700">
                        Pickup at <?= htmlspecialchars($package['pickup_location']) ?>
                    </span>
                </li>

                <?php foreach($morningItinerary as $item): ?>
                <li class="flex justify-between border-b pb-2">
                    <span class="font-medium"><?= date("h:i A", strtotime($item['time'])) ?></span>
                    <span class="text-gray-700">
                        <?php 
                        $cleanActivity = preg_replace('/^(Travel to |Visit )/', '', $item['activity']);
                        $cleanActivity = preg_replace('/\s*\(Activities:.*\)/', '', $cleanActivity);
                        echo htmlspecialchars(trim($cleanActivity));
                        ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div>

            <ul class="space-y-3">
                <?php foreach($afternoonItinerary as $item): ?>
                <li class="flex justify-between border-b pb-2">
                    <span class="font-medium"><?= date("h:i A", strtotime($item['time'])) ?></span>
                    <span class="text-gray-700">
                        <?php 
                        $cleanActivity = preg_replace('/^(Travel to |Visit )/', '', $item['activity']);
                        $cleanActivity = preg_replace('/\s*\(Activities:.*\)/', '', $cleanActivity);
                        echo htmlspecialchars(trim($cleanActivity));
                        ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>


<div class="md:flex md:space-x-6">
    <!-- Ratings & Reviews nga card ssa ubos -->
    <div class="bg-white p-6 rounded shadow mb-6 md:w-1/2 max-h-[400px] md:max-h-[600px] overflow-y-auto">
        <h2 class="text-2xl font-semibold mb-4">Reviews</h2>

        <!-- Display average sa rating -->
        <?php
        $avgRating = 0; $countRating = 0;
        $stmt = $conn->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as total FROM ratings WHERE package_id=?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res) {
            $avgRating = round($res['avg_rating'], 1);
            $countRating = $res['total'];
        }
        ?>
        <p class="text-lg mb-2">
            ⭐ <?= $avgRating ?> / 5.0 (<?= $countRating ?> reviews)
        </p>

        <!-- Review sa list -->
        <?php
        $stmt = $conn->prepare("
            SELECT r.rating, r.created_at, t.fullname 
            FROM ratings r
            JOIN tourists t ON r.tourist_id = t.id
            WHERE r.package_id=?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $reviews = $stmt->get_result();
        ?>
        <ul class="space-y-4">
            <?php while($rev = $reviews->fetch_assoc()): ?>
            <li class="border-b pb-2">
                <p class="font-semibold"><?= htmlspecialchars($rev['fullname']) ?></p>
                <p><?= str_repeat("⭐", (int)$rev['rating']) ?></p>
                <p class="text-gray-500 text-sm"><?= $rev['created_at'] ?></p>
            </li>
            <?php endwhile; ?>
        </ul>

        <!-- Only ang tourists ray maka rate -->
        <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'tourist'): ?>
        <form action="submit_rating.php" method="POST" class="mt-4">
            <input type="hidden" name="package_id" value="<?= $package_id ?>">
            <label for="rating" class="font-semibold">Leave a Rating:</label>
            <select name="rating" id="rating" class="border rounded px-2 py-1" required>
                <option value="">Select</option>
                <?php for($i=1;$i<=5;$i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> ⭐</option>
                <?php endfor; ?>
            </select>
            <button type="submit" class="bg-yellow-600 text-white px-4 py-1 rounded ml-2">Submit</button>
        </form>
        <?php else: ?>
        <p class="mt-3 text-gray-600 text-sm">You can leave a review here.</p>
        <?php endif; ?>
    </div>

    <!-- Booking Form -->
    <form id="bookingForm" method="POST" action="preview_booking.php" class="flex flex-col justify-between items-start bg-white p-6 rounded shadow mb-6 md:w-1/2 sticky top-4 space-y-4">
        <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
        <input type="hidden" name="total" id="total_input">

        <div>
            <p class="text-gray-700 mb-1 font-semibold">Price per pax:</p>
            <p class="text-green-600 font-bold text-xl" id="price">₱<?= number_format($package['price'],2) ?></p>
        </div>

        <div class="flex items-center space-x-4">
            <label class="font-semibold">Select Pax:</label>
            <select name="pax" id="pax" class="border rounded px-2 py-1">
                <option value="1">Solo</option>
                <option value="3">3 Pax</option>
                <option value="5">5 Pax</option>
                <option value="8">8 Pax</option>
            </select>
        </div>

        <div class="flex items-center space-x-4">
            <label class="font-semibold">Select Date:</label>
            <input type="text" name="booking_date" id="booking_date" class="border rounded px-2 py-1" placeholder="Select date" required>
        </div>

        <div>
            <p class="font-semibold">Total Price:</p>
            <p class="text-green-700 font-bold text-xl" id="total">₱<?= number_format($package['price'],2) ?></p>
        </div>

        <div>
            <button type="submit" class="bg-yellow-600 text-white px-6 py-2 rounded hover:bg-green-700 font-semibold">
                Book Now
            </button>
        </div>
    </form>
</div>
</main>

<?php include 'footer.php'; ?>

<script>
// Swiper slider
var swiper = new Swiper('.swiper-container', {
    slidesPerView: 1,
    spaceBetween: 10,
    loop: true,
    pagination: { el: '.swiper-pagination', clickable: true },
});

// Leaflet map
var map = L.map('map').setView([<?= $destinations[0]['latitude'] ?? 8.45; ?>, <?= $destinations[0]['longitude'] ?? 123.84; ?>], 10);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: 'Map data &copy; OpenStreetMap contributors'
}).addTo(map);

// Function to create numbered red markers
function createNumberedMarker(lat, lng, number, popupText) {
    var icon = L.divIcon({
        html: '<div style="background-color:red;color:white;border-radius:50%;width:25px;height:25px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;">'+number+'</div>',
        className: '',
        iconSize: [25, 25],
        iconAnchor: [12, 12],
        popupAnchor: [0, -10]
    });
    L.marker([lat, lng], {icon: icon}).addTo(map)
        .bindPopup(popupText);
}

// Array to store polyline points
var routePoints = [];

// Destination markers only (1,2,3...)
<?php foreach($destinations as $index => $dest):
    if($dest['latitude'] && $dest['longitude']): ?>
createNumberedMarker(<?= $dest['latitude'] ?>, <?= $dest['longitude'] ?>, '<?= $index+1 ?>', "<b><?= htmlspecialchars($dest['name']) ?></b><br><?= htmlspecialchars($dest['location']) ?>");
routePoints.push([<?= $dest['latitude'] ?>, <?= $dest['longitude'] ?>]);
<?php endif; endforeach; ?>;

// Draw polyline between destinations
if (routePoints.length > 1) {
    var polyline = L.polyline(routePoints, {color: 'red', weight: 4, opacity: 0.7}).addTo(map);
    map.fitBounds(polyline.getBounds());
} else if (routePoints.length === 1) {
    map.setView(routePoints[0], 13);
}

// Pax selection & total price
const pricePerPax = <?= $package['price'] ?>;
const paxSelect = document.getElementById('pax');
const totalEl = document.getElementById('total');
const totalInput = document.getElementById('total_input');

function updateTotal() {
    const pax = parseInt(paxSelect.value);
    const total = pricePerPax * pax;
    totalEl.textContent = '₱' + total.toLocaleString();
    totalInput.value = total;
}

updateTotal();
paxSelect.addEventListener('change', updateTotal);

// Flatpickr
flatpickr("#booking_date", { minDate: "today", dateFormat: "Y-m-d" });
</script>

</body>
</html>
