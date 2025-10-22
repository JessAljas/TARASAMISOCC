<?php
session_start();
include 'config/db_connect.php'; // Database connection

$package_id = $_GET['id'] ?? 0;

// ==================== FETCH PACKAGE ==================== //
$package = null;
if ($package_id) {
    $stmt = $conn->prepare("SELECT * FROM packages WHERE id=?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $package = $result->fetch_assoc();
    }
    $stmt->close();
}

// If no package found, redirect or show error
if (!$package) {
    echo "<p class='text-red-600 text-center mt-6'>Package not found.</p>";
    exit;
}

// ==================== FETCH DESTINATIONS ==================== //
$destinations = [];
$stmt = $conn->prepare("
    SELECT ts.id, 
           ts.name_of_tourist_spot AS name, 
           ts.location, 
           ts.description, 
           ts.activity, 
           ts.latitude, 
           ts.longitude, 
           ts.image1, ts.image2, ts.image3,
           ts.entrance_fee
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

// ==================== FETCH ITINERARY ==================== //
$itinerary = [];
$stmt = $conn->prepare("SELECT time, activity_type, destination_name FROM itinerary WHERE package_id=? ORDER BY time ASC");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $itinerary[] = $row;
}
$stmt->close();

// Get unavailable/closed dates from packages table
$unavailableDates = [];
if (!empty($package['closed_dates'])) {
    $unavailableDates = json_decode($package['closed_dates'], true); // convert JSON string to PHP array
    if (!is_array($unavailableDates)) $unavailableDates = [];
}




// ==================== FETCH INCLUSIONS & EXCLUSIONS ==================== //
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
<style>
 #map {
z-index: 1; 
}
</style>
<body class="bg-gray-100 flex flex-col min-h-screen font-[Poppins]">
<?php include 'config/include/header.php'; ?>
<main class="max-w-7xl mx-auto mt-8 p-4">
<h1 class="text-2xl md:text-3xl font-bold mb-4 text-center"><?= htmlspecialchars($package['title']) ?></h1>


<!-- Image Slider -->
<div class="flex justify-center mb-6">
  <div class="swiper-container rounded-lg overflow-hidden shadow w-full max-w-3xl bg-green-100">
    <div class="swiper-wrapper">
      <?php for($i=1;$i<=4;$i++):
          $img = $package["image$i"] ?? null;
          if($img && file_exists("uploads/".$img)): ?>
        <div class="swiper-slide flex justify-center">
          <img src="uploads/<?= htmlspecialchars($img) ?>" class="h-80 object-cover">
        </div>
      <?php endif; endfor; ?>
    </div>
  </div>
</div>

<!-- Swiper JS nga link -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

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
  <span class="font-bold text-2xl"><?= htmlspecialchars($dest['name']) ?></span>
    <p class="text-black-500 text-1xl"><?= htmlspecialchars($dest['location']) ?></p>

    <!--  Entrance Fee Section -->
    <?php if (!empty($dest['entrance_fee'])): ?>
        <p class="text-sm text-orange-600 mb-2">
            💰 <strong>Entrance Fee:</strong> ₱<?= number_format($dest['entrance_fee'], 2) ?>
        </p>
    <?php else: ?>
        <p class="text-sm text-red-500 mb-2">💰 Entrance Fee: Not specified</p>
    <?php endif; ?>

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
        <p class="font-bold text-1xl">Activities:</p>
        <p class="text-gray-600 text-sm"><?= nl2br(htmlspecialchars($dest['activity'])) ?></p>
    </div>
    <?php endif; ?>
</div>
        </li>
        <?php endforeach; ?>
    </ul>
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
<!-- Map -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-2">Map</h2>
    <div id="map" class="w-full h-80 rounded"></div>
</div>

<!-- ==================== ITINERARY SECTION ==================== -->
<div class="bg-white p-6 rounded shadow mb-6">
    <h2 class="text-2xl font-semibold mb-4">Itinerary</h2>

    <?php if (!empty($itinerary)): ?>
        <div class="grid md:grid-cols-2 gap-6">
            <!-- Morning Column -->
            <div>
                <h3 class="text-lg font-semibold mb-2 text-gray-700">Morning</h3>
                <ul class="space-y-3">
                    <?php 
                    $hasMorning = false;
                    foreach ($itinerary as $item):
                        $hour = (int)date("H", strtotime($item['time']));
                        if ($hour < 12): // Morning
                            $hasMorning = true;
                            $destination = $item['destination_name'] ?? $item['activity'] ?? 'No destination';
                    ?>
                        <li class="flex justify-between border-b pb-2">
                            <span class="font-medium text-gray-700">
                                <?= date("h:i A", strtotime($item['time'])) ?>
                            </span>
                            <span class="text-gray-800">
                                <?php 
                                    $type = ucfirst($item['activity_type']);
                                    // Replace "Visit" with "Arrival"
                                    if (strtolower($type) === 'arrival') {
                                        $type = 'Arrival';
                                    }
                                    echo "<strong>$type:</strong> " . htmlspecialchars($destination);
                                ?>
                            </span>
                        </li>
                    <?php 
                        endif;
                    endforeach;
                    if (!$hasMorning) echo "<p class='text-gray-500 italic'>No morning activities.</p>";
                    ?>
                </ul>
            </div>

            <!-- Afternoon Column -->
            <div>
                <h3 class="text-lg font-semibold mb-2 text-gray-700">Afternoon</h3>
                <ul class="space-y-3">
                    <?php 
                    $hasAfternoon = false;
                    foreach ($itinerary as $item):
                        $hour = (int)date("H", strtotime($item['time']));
                        if ($hour >= 12): // Afternoon
                            $hasAfternoon = true;
                            $destination = $item['destination_name'] ?? $item['activity'] ?? 'No destination';
                    ?>
                        <li class="flex justify-between border-b pb-2">
                            <span class="font-medium text-gray-700">
                                <?= date("h:i A", strtotime($item['time'])) ?>
                            </span>
                            <span class="text-gray-800">
                                <?php 
                                    $type = ucfirst($item['activity_type']);
                                    if (strtolower($type) === 'visit') {
                                        $type = 'Arrival';
                                    }
                                    echo "<strong>$type:</strong> " . htmlspecialchars($destination);
                                ?>
                            </span>
                        </li>
                    <?php 
                        endif;
                    endforeach;
                    if (!$hasAfternoon) echo "<p class='text-gray-500 italic'>No afternoon activities.</p>";
                    ?>
                </ul>
            </div>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No itinerary available for this package.</p>
    <?php endif; ?>
</div>


<!-- Booking Section -->
<div id="bookingForm" 
     class="flex flex-col md:flex-row md:space-x-8 space-y-8 md:space-y-0 
            border border-green-400 shadow-lg shadow-green-200 bg-white rounded-xl p-6 
            transition hover:shadow-green-300">
  <form action="preview_booking.php" method="POST" class="space-y-4">
  <div>
    <label for="pax" class="font-semibold text-gray-700 mb-1 flex items-center gap-2">
      <i class="fa-solid fa-users text-green-600"></i> Select Number of Guests
    </label>
    <select
      name="pax"
      id="pax"
      class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:border-green-500 focus:ring-1 focus:ring-green-400 transition"
      required
    >
      <option value="1">Solo (1 Pax)</option>
      <option value="3">3 Pax</option>
      <option value="5">5 Pax</option>
      <option value="8">8 Pax</option>
    </select>
  </div>

  <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 rounded">
    <p class="text-yellow-700 text-sm font-medium">
      🌟 Don’t miss out! Secure your booking early to guarantee your spot for tomorrow’s tour.
    </p>
  </div>

  <p class="text-red-600 text-sm text-center">
    <span class="inline-block w-3 h-3 bg-red-500 mr-1 rounded-full align-middle"></span>
    <em>Dates highlighted in red are</em> <strong>unavailable</strong> <em>for booking.</em>
  </p>

  <div>
    <label for="booking_date" class="font-semibold text-gray-700 mb-2 flex items-center gap-2">
      <i class="fas fa-calendar-alt text-green-600"></i> Select Tour Date
    </label>
    <input type="text" name="booking_date" id="booking_date"
      class="border-2 border-green-500 rounded-lg px-3 py-2 w-full focus:outline-none focus:ring-2 focus:ring-green-300"
      placeholder="Choose a date" required/>
  </div>

  <div class="text-center">
    <p class="font-semibold text-gray-700">Total Amount</p>
    <p id="originalPrice" class="text-sm text-gray-500 line-through hidden"></p>
    <p id="total" class="text-2xl font-bold text-green-700 mt-1 underline">
      ₱<?= number_format($package['price'], 2) ?>
    </p>
    <p id="discountNote" class="text-green-600 text-sm font-medium mt-1 hidden"></p>

    <!-- Hidden inputs for backend -->
    <input type="hidden" name="package_id" value="<?= $package['id'] ?>">
    <input type="hidden" id="final_price" name="final_price" value="<?= $package['price'] ?>">
  </div>

  <div class="flex justify-center gap-4 pt-2">
    <button
      type="submit"
      class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium text-sm flex items-center gap-2 shadow transition">
      <i class="fa-solid fa-check-circle"></i> Confirm Booking
    </button>

    <a href="package.php"
      class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-6 py-2 rounded-lg font-medium text-sm flex items-center gap-2 shadow transition">
      <i class="fa-solid fa-xmark"></i> Cancel
    </a>
  </div>

</form>

<script>
  // Base price from PHP
  const basePrice = <?= $package['price'] ?>;

  const paxSelect = document.getElementById("pax");
  const totalDisplay = document.getElementById("total");
  const discountNote = document.getElementById("discountNote");
  const originalPrice = document.getElementById("originalPrice");
  const finalPriceInput = document.getElementById("final_price");

  paxSelect.addEventListener("change", () => {
    const pax = parseInt(paxSelect.value);
    let discount = 0;

    // Apply discount based on number of guests
    if (pax === 3) discount = 0.4; // 
    else if (pax === 5) discount = 0.10; 
    else if (pax === 8) discount = 0.12; 
    else discount = 0;

    const discountedPrice = basePrice - basePrice * discount;

    // Update UI
    if (discount > 0) {
      originalPrice.textContent = "₱" + basePrice.toLocaleString(undefined, { minimumFractionDigits: 2 });
      originalPrice.classList.remove("hidden");

      totalDisplay.textContent = "₱" + discountedPrice.toLocaleString(undefined, { minimumFractionDigits: 2 });
      discountNote.textContent = `You saved ${discount * 100}% for booking ${pax} guests!`;
      discountNote.classList.remove("hidden");
    } else {
      originalPrice.classList.add("hidden");
      discountNote.classList.add("hidden");
      totalDisplay.textContent = "₱" + basePrice.toLocaleString(undefined, { minimumFractionDigits: 2 });
    }

    // Update hidden input (for sending to PHP)
    finalPriceInput.value = discountedPrice.toFixed(2);
  });
</script>


  <!-- Reviews Card -->
 <div class="flex-1 bg-white p-8 rounded-2xl shadow-lg border border-gray-100 mt-20 md:mt-28">

  <h3 class="text-2xl font-semibold mb-4 text-center text-gray-800">
    Share Your Experience: Leave a Review After Your Tour
    </h3>

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
    <p class="text-lg mb-2 text-center">
      ⭐ <?= $avgRating ?> / 5.0 (<?= $countRating ?> reviews)
    </p>

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
    <ul class="space-y-4 max-h-[400px] overflow-y-auto pr-2">
      <?php while($rev = $reviews->fetch_assoc()): ?>
      <li class="border-b pb-2">
        <p class="font-semibold text-gray-800"><?= htmlspecialchars($rev['fullname']) ?></p>
        <p><?= str_repeat("⭐", (int)$rev['rating']) ?></p>
        <p class="text-gray-500 text-sm"><?= $rev['created_at'] ?></p>
      </li>
      <?php endwhile; ?>
    </ul>

    <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'tourist'): ?>
    <form
      action="submit_rating.php"
      method="POST"
      class="mt-4 flex items-center gap-3 justify-center"
    >
      <input type="hidden" name="package_id" value="<?= $package_id ?>" />
      <label for="rating" class="font-semibold text-gray-700">Leave a Rating:</label>
      <select
        name="rating"
        id="rating"
        class="border rounded px-2 py-1 focus:border-yellow-500 focus:ring-yellow-400"
        required
      >
        <option value="">Select</option>
        <?php for($i=1;$i<=5;$i++): ?>
        <option value="<?= $i ?>"><?= $i ?> ⭐</option>
        <?php endfor; ?>
      </select>
      <button
        type="submit"
        class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-1 rounded transition"
      >
        Submit
      </button>
    </form>
    <?php else: ?>
    <p class="mt-3 text-gray-600 text-center text-sm">
      Log in as a tourist to leave a review.
    </p>
    <?php endif; ?>
  </div>
</div>


</main>

<?php include 'config/include/footer.php'; ?>

<script src="js/package.js"></script>
<script>
  // Initialize map, booking, and datepicker with PHP data
  const destinations = <?= json_encode($destinations) ?>;
  const pricePerPax = <?= $package['price'] ?>;
  initMap(destinations);
  initBooking(pricePerPax);
  initDatePicker();
</script>
<script>
    // Pass unavailable dates from PHP to JavaScript
    const unavailableDates = <?= json_encode($unavailableDates) ?>;
    console.log("Closed dates:", unavailableDates); // Optional: check in console

    // Initialize Flatpickr
    flatpickr("#booking_date", {
        dateFormat: "Y-m-d",
        minDate: "today",
        disable: unavailableDates, // these dates will be unselectable
        locale: {
            firstDayOfWeek: 1 // week starts on Monday
        },
        onDayCreate: function(dObj, dStr, fp, dayElem) {
            const date = dayElem.dateObj.toISOString().split("T")[0];
            if (unavailableDates.includes(date)) {
                // Add tooltip for closed dates
                dayElem.title = "Closed / Not Available";
                dayElem.style.backgroundColor = "#f87171"; // red-ish background
                dayElem.style.color = "#fff";
                dayElem.style.cursor = "not-allowed";
            }
        }
    });
</script>

</body>
</html>
