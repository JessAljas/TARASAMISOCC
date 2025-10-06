<?php
session_start();
include 'db_connect.php';

//  Only allow tourists nga login
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist') {
    header("Location: login.php");
    exit;
}

$tourist_id   = $_SESSION['user']['id'];
$package_id   = $_POST['package_id'] ?? 0;
$pax          = $_POST['pax'] ?? 1;
$total        = $_POST['total'] ?? 0;
$booking_date = $_POST['booking_date'] ?? date('Y-m-d'); // automatic nga mo set karon sa current date if wala nag select

//Fetch package details
$package = [
    'title' => 'Selected Package',
    'pickup_location' => 'TBA',
    'dropoff_location' => 'TBA'
];
$stmt = $conn->prepare("SELECT title, pickup_location, dropoff_location FROM packages WHERE id = ?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $package['title'] = $row['title'];
    $package['pickup_location'] = $row['pickup_location'];
    $package['dropoff_location'] = $row['dropoff_location'];
}
$stmt->close();

//Compute GCash with 2.5% service fee
$gcash_total = $total + ($total * 0.025);
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

<?php include 'header.php'; ?>

<div class="flex-grow flex justify-center p-4">
    <div class="bg-white shadow-lg rounded-lg w-full max-w-6xl p-6 md:p-10 grid grid-cols-1 md:grid-cols-3 gap-8">

        <!-- LEFT SIDE: Booking ug  Customer details form -->
        <div class="md:col-span-2 space-y-6">
            <div class="border rounded-lg p-5 bg-gray-50">
                <h2 class="text-xl font-semibold mb-3">Package Details</h2>
                <p><span class="font-semibold">Package:</span> <?= htmlspecialchars($package['title']) ?></p>
                <p><span class="font-semibold">Number of Pax:</span> <?= htmlspecialchars($pax) ?></p>
                <p><span class="font-semibold"><i class="fa-solid fa-location-dot"></i> Pickup Location:</span> <?= htmlspecialchars($package['pickup_location']) ?></p>
                <p><span class="font-semibold"><i class="fa-solid fa-flag-checkered"></i> Dropoff Location:</span> <?= htmlspecialchars($package['dropoff_location']) ?></p>
                <p><span class="font-semibold"><i class="fa-solid fa-calendar-days"></i> Booking Date:</span> <?= htmlspecialchars(date("F j, Y", strtotime($booking_date))) ?></p>
                <p class="text-lg font-bold text-green-600">₱<?= number_format($total,2) ?></p>
            </div>

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
                        <input type="text" name="phone" required class="w-full border rounded px-3 py-2">
                    </div>
                </form>
            </div>
        </div>

        <!-- RIGHT SIDE: Payment ug Terms -->
        <div class="space-y-6">
            <div class="border rounded-lg p-5 bg-gray-50">
                <h2 class="text-xl font-semibold mb-4 text-center">TERMS AND CONDITIONS:</h2>
                <ul class="text-xs space-y-2 list-disc list-inside">
                    <li class="text-orange-900"><i class="fa-solid fa-circle-info"></i> Booking may only be guaranteed once payment has been made.</li>
                    <li class="text-orange-900"><i class="fa-solid fa-circle-info"></i> Itinerary might CHANGE without prior notice according to local conditions.</li>
                    <li class="text-orange-900"><i class="fa-solid fa-circle-info"></i> We have the right to CANCEL or RESCHEDULE any trip under the following circumstances:
                        <ul class="list-disc list-inside ml-5 text-orange-800">
                            <li>Lack of joiners in a scheduled trip</li>
                            <li>In case of Natural Calamity or reports of typhoon that may compromise passenger safety</li>
                        </ul>
                    </li>
                    <li class="text-orange-900"><i class="fa-solid fa-circle-info"></i> Travel organizer / Management is not responsible for any accidents during trips and not liable for any lost/damaged items or leaving behind valuables at hotels or other areas.</li>
                    <li class="text-red-600 font-semibold"><i class="fa-solid fa-circle-info"></i> NO payment, NO reservation.</li>
                    <li class="text-orange-900"><i class="fa-solid fa-circle-info"></i> Paying means you agree with our Terms & Conditions.</li>
                </ul>

                <br>
                <h2 class="text-xl font-semibold mb-4 text-center">Payment Options</h2>

                <!-- GCash/QR Payment -->
                <form method="POST" action="gcash_payment.php" onsubmit="return copyBookingInfo(this)" class="mb-6">
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

                    <p class="text-1xl font-bold text-green-800 text-center mb-3">
                        ₱<?= number_format($gcash_total,2) ?> (with service fee)
                    </p>

                    <p class="text-xs text-red-600 mt-4 text-center">
                        <i class="fa-solid fa-circle-info"></i> Note: GCash Service Fee (2.5%) applies.
                    </p><br>

                    <button type="submit" class="flex items-center justify-center gap-2 w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded font-semibold">
                        <i class="fa-solid fa-mobile-screen"></i> Pay via GCash
                    </button>
                </form>

                <!--PayMongo nga button payment -->
                <form method="POST" action="paymongo_checkout.php" onsubmit="return copyBookingInfo(this)" class="mb-3">
                    <input type="hidden" name="tourist_id" value="<?= $tourist_id ?>">
                    <input type="hidden" name="package_id" value="<?= $package_id ?>">
                    <input type="hidden" name="pax" value="<?= $pax ?>">
                    <input type="hidden" name="total" value="<?= $total ?>">
                    <input type="hidden" name="mode_of_payment" value="PayMongo">
                    <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">
                    <input type="hidden" name="fullname">
                    <input type="hidden" name="address">
                    <input type="hidden" name="email">
                    <input type="hidden" name="phone">

                    <button type="submit" class="flex items-center justify-center gap-2 w-full bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-3 rounded font-semibold">
                        <i class="fa-solid fa-credit-card"></i> Pay via PayMongo
                    </button>
                </form>

                <div class="mt-4">
                    <p class="text-sm font-semibold mb-2">We Accept:</p>
                    <div class="flex items-center gap-3">
                        <img src="img/gcash.png" alt="GCash" class="h-8">
                        <img src="img/maya.png" alt="Maya" class="h-8">
                        <img src="img/bpi.png" alt="BPI" class="h-8">
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include 'footer.php'; ?>

<script>
// Copy form values into hidden fields before submitting sa payment forms
function copyBookingInfo(form) {
    const bookingForm = document.getElementById("bookingInfo");
    const data = new FormData(bookingForm);
    form.fullname.value = data.get("fullname");
    form.address.value = data.get("address");
    form.email.value = data.get("email");
    form.phone.value = data.get("phone");

    if (!form.fullname.value || !form.address.value || !form.email.value || !form.phone.value) {
        alert("Please fill out all fields before proceeding to payment.");
        return false;
    }
    return true;
}
</script>
</body>
</html>
