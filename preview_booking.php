<?php
session_start();
include 'config/db_connect.php';

// ✅ Allow only tourists
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist') {
    header("Location: login.php");
    exit;
}

$tourist_id   = $_SESSION['user']['id'];
$package_id   = $_POST['package_id'] ?? 0;
$pax          = $_POST['pax'] ?? 1;
$total        = $_POST['total'] ?? 0;
$booking_date = $_POST['booking_date'] ?? date('Y-m-d');

// ✅ Default package info
$package = [
    'title' => 'Selected Package',
    'pickup_location' => 'TBA',
    'dropoff_location' => 'TBA',
    'pickup_time' => 'TBA',
    'dropoff_time' => 'TBA'
];

// ✅ Get basic package details
$stmt = $conn->prepare("SELECT title, pickup_location, dropoff_location, price FROM packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
$original_price = 0;

if ($row = $result->fetch_assoc()) {
    $package['title'] = $row['title'];
    $package['pickup_location'] = $row['pickup_location'];
    $package['dropoff_location'] = $row['dropoff_location'];
    $original_price = $row['price'];
}
$stmt->close();

// ✅ Determine discount rate based on pax
$discount_rate = 0;
if ($pax == 3) $discount_rate = 0.4;
elseif ($pax == 5) $discount_rate = 0.10;
elseif ($pax == 8) $discount_rate = 0.12;

$discounted_price = $original_price - ($original_price * $discount_rate);
$you_save = $original_price * $discount_rate;

// ✅ Compute GCash total (2.5% service fee)
$gcash_total = $discounted_price + ($discounted_price * 0.025);

// ✅ Get pickup time (earliest itinerary time)
$stmt_pickup = $conn->prepare("SELECT time FROM itinerary WHERE package_id = ? ORDER BY time ASC LIMIT 1");
$stmt_pickup->bind_param("i", $package_id);
$stmt_pickup->execute();
$result_pickup = $stmt_pickup->get_result();
if ($row = $result_pickup->fetch_assoc()) {
    $package['pickup_time'] = date("g:i A", strtotime($row['time']));
}
$stmt_pickup->close();

// ✅ Get drop-off time (latest itinerary time)
$stmt_dropoff = $conn->prepare("SELECT time FROM itinerary WHERE package_id = ? ORDER BY time DESC LIMIT 1");
$stmt_dropoff->bind_param("i", $package_id);
$stmt_dropoff->execute();
$result_dropoff = $stmt_dropoff->get_result();
if ($row = $result_dropoff->fetch_assoc()) {
    $package['dropoff_time'] = date("g:i A", strtotime($row['time']));
}
$stmt_dropoff->close();

// ✅ Fetch itinerary
$itinerary = [];
$stmt_itinerary = $conn->prepare("
    SELECT destination_name, time, activity_type 
    FROM itinerary 
    WHERE package_id = ? 
    ORDER BY time ASC
");
$stmt_itinerary->bind_param("i", $package_id);
$stmt_itinerary->execute();
$result_itinerary = $stmt_itinerary->get_result();

while ($row = $result_itinerary->fetch_assoc()) {
    $itinerary[] = [
        'destination' => $row['destination_name'],
        'time' => date("g:i A", strtotime($row['time'])),
        'activity' => $row['activity_type']
    ];
}
$stmt_itinerary->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Preview</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-100 min-h-screen flex flex-col font-[Poppins]">
<?php include 'config/include/header.php'; ?>

<div class="flex-grow flex justify-center p-4">
  <div class="bg-white shadow-lg rounded-lg w-full max-w-6xl p-6 md:p-10 grid grid-cols-1 md:grid-cols-3 gap-8">

    <!-- LEFT SIDE -->
    <div class="md:col-span-2 space-y-6">
      <div class="border rounded-lg p-5 bg-gray-50">
        <h2 class="text-xl font-semibold mb-5 text-center">Package Details</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm leading-relaxed">
          <div class="space-y-1.5">
            <p><span class="font-semibold">Package:</span> <?= htmlspecialchars($package['title']) ?></p>
            <p><span class="font-semibold">Number of Pax:</span> <?= htmlspecialchars($pax) ?></p>

            <!-- ✅ Discount display -->
            <div class="mt-2 space-y-1">
              <p class="text-sm text-gray-500 line-through">Original Price: ₱<?= number_format($original_price, 2) ?></p>
              <?php if ($discount_rate > 0): ?>
                <p class="text-sm text-green-600 font-semibold">
                  Discount (<?= $discount_rate * 100 ?>%): -₱<?= number_format($you_save, 2) ?>
                </p>
                <p class="text-xs text-green-700 italic">You saved ₱<?= number_format($you_save, 2) ?>!</p>
              <?php endif; ?>
              <p class="text-lg font-bold text-green-700">Discounted Price: ₱<?= number_format($discounted_price, 2) ?></p>
            </div>

            <p><span class="font-semibold"><i class="fa-solid fa-calendar-days"></i> Booking Date:</span>
              <?= htmlspecialchars(date("F j, Y", strtotime($booking_date))) ?>
            </p>
          </div>

          <div class="space-y-1.5">
            <p><span class="font-semibold"><i class="fa-solid fa-location-dot"></i> Pickup Location:</span> <?= htmlspecialchars($package['pickup_location']) ?></p>
            <p><span class="font-semibold"><i class="fa-solid fa-clock"></i> Pickup Time:</span> <?= htmlspecialchars($package['pickup_time']) ?></p>
            <p><span class="font-semibold"><i class="fa-solid fa-flag-checkered"></i> Drop-off Location:</span> <?= htmlspecialchars($package['dropoff_location']) ?></p>
            <p><span class="font-semibold"><i class="fa-solid fa-clock"></i> Drop-off Time:</span> <?= htmlspecialchars($package['dropoff_time']) ?></p>
          </div>
        </div>

        <div class="text-center mt-6">
          <button onclick="document.getElementById('itineraryModal').classList.remove('hidden')"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-sm transition">
            <i class="fa-solid fa-list"></i> View Your Itinerary Schedule
          </button>
        </div>
      </div>

      <!-- CUSTOMER DETAILS -->
      <div class="border rounded-lg p-5 bg-gray-50">
        <h2 class="text-xl font-semibold mb-3">Customer Details</h2>
        <form id="bookingInfo" class="space-y-4">
          <div>
            <label class="block text-sm font-semibold">Full Name</label>
            <input type="text" name="fullname" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-semibold">Address</label>
            <input type="text" name="address" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-semibold">Email</label>
            <input type="email" name="email" required class="w-full border rounded px-3 py-2">
          </div>
          <div>
            <label class="block text-sm font-semibold">Phone Number</label>
            <input type="text" name="phone" required maxlength="11" pattern="\d{11}" title="Please enter an 11-digit phone number" class="w-full border rounded px-3 py-2" oninput="this.value=this.value.replace(/[^0-9]/g,'')">
          </div>
        </form>
      </div>
    </div>

    <!-- RIGHT SIDE -->
    <div class="flex flex-col lg:flex-row gap-6">
      <div class="flex-1 border border rounded-xl p-6 bg-gray-50 shadow-md h-[650px] flex flex-col justify-between">

           <!-- TERMS SECTION -->
    <div class="overflow-y-auto pr-2">
      <h2 class="text-xl font-semibold mb-4 text-center text-gray-800">TERMS AND CONDITIONS:</h2>
      <h6 class="text-sm font-semibold mb-4 text-center text-gray-800">Read First:</h6>
      <ul class="text-xs space-y-3 list-disc list-inside leading-relaxed">
        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          Booking may only be guaranteed once payment has been made.
        </li>

        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          Itinerary might CHANGE without prior notice according to local conditions.
        </li>

        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          We have the right to CANCEL or RESCHEDULE any trip under the following circumstances:
          <ul class="list-disc list-inside ml-5 text-orange-800 space-y-1">
            <li>Lack of joiners in a scheduled trip</li>
            <li>In case of Natural Calamity or reports of typhoon that may compromise passenger safety</li>
          </ul>
        </li>

        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          Travel organizer / Management is not responsible for any accidents during trips and not liable for any lost/damaged items or leaving behind valuables at hotels or other areas.
        </li>

        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          Partial payments or credit arrangements are not accepted. Full payment is required to secure your booking. No full payment, no tour.
        </li>

        <li class="text-red-600 font-semibold">
          <i class="fa-solid fa-circle-info text-red-600 mr-1"></i>
          NO payment, NO reservation.
        </li>

        <p class="text-xs text-red-600 mt-4 text-center bg-yellow-50 rounded-md p-2 italic">
          <i class="fa-solid fa-circle-info"></i> Note: GCash Service Fee (2.5%) applies.
        </p>

        <li class="text-orange-900">
          <i class="fa-solid fa-circle-info text-orange-700 mr-1"></i>
          Paying means you agree with our Terms & Conditions.
        </li>
      </ul>
    </div>

        <!-- PAYMENT -->
        <div class="flex-1 border mt-6 border-t pt-4">
          <h2 class="text-xl font-semibold mb-4 text-center text-gray-800">Payment Options</h2>

          <!-- PayMongo -->
          <form method="POST" action="paymongo_checkout.php" onsubmit="return copyBookingInfo(this)" class="mb-4">
            <input type="hidden" name="tourist_id" value="<?= $tourist_id ?>">
            <input type="hidden" name="package_id" value="<?= $package_id ?>">
            <input type="hidden" name="pax" value="<?= $pax ?>">
            <input type="hidden" name="total" value="<?= $discounted_price ?>">
            <input type="hidden" name="mode_of_payment" value="PayMongo">
            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">
            <input type="hidden" name="fullname">
            <input type="hidden" name="address">
            <input type="hidden" name="email">
            <input type="hidden" name="phone">
            <button type="submit"
              class="w-full bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded font-medium text-sm transition">
              <i class="fa-solid fa-credit-card"></i> Checkout Now
            </button>
          </form>

          <!-- GCash -->
          <form method="POST" action="gcash_payment.php" onsubmit="return copyBookingInfo(this)">
            <input type="hidden" name="tourist_id" value="<?= $tourist_id ?>">
            <input type="hidden" name="package_id" value="<?= $package_id ?>">
            <input type="hidden" name="pax" value="<?= $pax ?>">
            <input type="hidden" name="total" value="<?= $gcash_total ?>">
            <input type="hidden" name="mode_of_payment" value="GCash QR">
            <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">
            <input type="hidden" name="fullname">
            <input type="hidden" name="address">
            <input type="hidden" name="email">
            <input type="hidden" name="phone">

            <p class="text-sm font-bold text-green-800 text-center mb-2">
              ₱<?= number_format($gcash_total, 2) ?> (with service fee)
            </p>

            <div class="flex justify-between gap-2 mt-3">
              <button type="submit"
                class="w-1/2 bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded font-medium text-sm transition">
                <i class="fa-solid fa-qrcode"></i> Pay via QR
              </button>

              <a href="package.php"
                class="w-1/2 bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded font-medium text-sm text-center transition">
                <i class="fa-solid fa-xmark"></i> Cancel
              </a>
            </div>
          </form>

          <div class="mt-4">
            <p class="text-sm font-semibold mb-2">We Accept:</p>
            <div class="flex items-center gap-3">
              <img src="img/gcash.png" class="h-8">
              <img src="img/maya.png" class="h-8">
              <img src="img/bpi.png" class="h-8">
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ITINERARY MODAL -->
<div id="itineraryModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4 p-6 relative">
    <button onclick="document.getElementById('itineraryModal').classList.add('hidden')" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700">
      <i class="fa-solid fa-xmark text-xl"></i>
    </button>

    <h2 class="text-2xl font-semibold mb-4 text-center">Your Itinerary Schedule</h2>
    <?php if (!empty($itinerary)): ?>
      <ul class="text-sm space-y-3 max-h-80 overflow-y-auto">
        <?php foreach ($itinerary as $item): ?>
          <li class="border-b pb-2">
            <div class="flex justify-between items-center">
              <span class="font-medium text-gray-800"><?= htmlspecialchars($item['destination']) ?></span>
              <span class="text-gray-600"><?= htmlspecialchars($item['time']) ?></span>
            </div>
            <p class="text-xs text-gray-500 italic ml-1 mt-1"><?= htmlspecialchars($item['activity']) ?></p>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p class="text-gray-500 text-center text-sm">No itinerary available.</p>
    <?php endif; ?>
  </div>
</div>

<?php include 'config/include/footer.php'; ?>
<script src="js/package.js"></script>
</body>
</html>
