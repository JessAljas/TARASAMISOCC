<?php
session_start();
include 'config/db_connect.php'; // The database connection file

$success = false;
$error = '';
$show_form = true;
$reference_number = '';

// Get POST data
$package_id   = $_POST['package_id'] ?? 0;
$pax          = $_POST['pax'] ?? 1;
$booking_date = $_POST['booking_date'] ?? null;
$total        = $_POST['total'] ?? 0;
$fullname     = $_POST['fullname'] ?? '';
$address      = $_POST['address'] ?? '';
$email        = $_POST['email'] ?? '';
$phone        = $_POST['phone'] ?? '';

// ✅ Fetch package info
$stmt = $conn->prepare("SELECT * FROM packages WHERE id=?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) $error = "Package not found.";

// ✅ Discount computation
$original_price = $package['price'] ?? 0;
$discount_rate = 0;
if ($pax == 3) $discount_rate = 0.4;
elseif ($pax == 5) $discount_rate = 0.10;
elseif ($pax == 8) $discount_rate = 0.12;

$discounted_price = $original_price - ($original_price * $discount_rate);
$you_save = $original_price * $discount_rate;

// ✅ If user already included service fee, don’t recompute again
$gcash_total = $total > 0 ? $total : ($discounted_price + ($discounted_price * 0.025));

// ✅ Handle payment confirmation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment']) && empty($error)) {
    $screenshot_path = null;

    // Upload screenshot
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $upload_dir = 'uploads/gcash/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ext, $allowed)) $error = "Invalid image type.";
        else {
            $filename = uniqid("gcash_") . '.' . $ext;
            $target_file = $upload_dir . $filename;
            if (!move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $target_file)) {
                $error = "Failed to upload screenshot.";
            } else {
                $screenshot_path = $target_file;
            }
        }
    } else {
        $error = "Please upload a screenshot.";
    }

    // ✅ Save payment
    if (empty($error)) {
        do {
            $reference_number = 'ref' . substr(bin2hex(random_bytes(3)), 0, 5);
            $check_stmt = $conn->prepare("SELECT id FROM pay_via_qr WHERE reference_number = ?");
            $check_stmt->bind_param("s", $reference_number);
            $check_stmt->execute();
            $check_stmt->store_result();
        } while ($check_stmt->num_rows > 0);
        $check_stmt->close();

        $stmt2 = $conn->prepare("INSERT INTO pay_via_qr 
            (tourist_id, fullname, address, email, phone, package_id, booking_date, pax, total, amount, status, payment_date, proof_image, reference_number, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, NOW())");

        $stmt2->bind_param(
            "issssissddss",
            $_SESSION['user']['id'],
            $fullname,
            $address,
            $email,
            $phone,
            $package_id,
            $booking_date,
            $pax,
            $gcash_total,
            $gcash_total,
            $screenshot_path,
            $reference_number
        );

        if ($stmt2->execute()) {
            $success = true;
            $show_form = false;
        } else {
            if (!empty($screenshot_path) && file_exists($screenshot_path)) @unlink($screenshot_path);
            $error = "Failed to save payment. " . $stmt2->error;
        }
        $stmt2->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>GCash Payment</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>

<body class="flex flex-col min-h-screen bg-gray-100 font-[Poppins]">
<header class="sticky top-0 z-50 bg-white shadow">
  <?php include 'config/include/header.php'; ?>
</header>

<main class="flex-1 flex flex-col items-center justify-center p-4">
  <div class="w-full max-w-3xl bg-white rounded-lg shadow-xl p-8">

    <?php if (!empty($error)): ?>
      <p class="text-red-600 mb-6 text-center text-lg"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
      <div class="text-center">
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center rounded-full bg-green-500">
          <i class="fa-solid fa-check text-green-900 text-4xl"></i>
        </div>

        <h2 class="text-3xl font-bold mb-4 text-green-700">Payment Successful!</h2>
        <h4 class="text-lg mb-6 text-gray-700">
          Thank you for choosing us! <br>We will review your submitted receipt.
        </h4>

        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded-md mb-4 text-sm text-gray-800">
          <i class="fa-solid fa-camera text-yellow-600 mr-1"></i> 
          Please screenshot this confirmation as proof of booking.
        </div>

<div class="mb-6 text-gray-700 text-sm space-y-0.5 leading-tight">
  <p><strong>Package:</strong> <?= htmlspecialchars($package['title'] ?? '') ?></p>
  <p><strong>Date of Tour:</strong> <?= htmlspecialchars($booking_date ?? 'N/A') ?></p>
  <p><strong>Pax:</strong> <?= htmlspecialchars($pax) ?></p>
  <p><strong>Original Price:</strong><span class="line-through text-gray-500 ml-1">
    ₱<?= number_format($original_price, 2) ?>
  </span>
</p>

  <?php if ($discount_rate > 0): ?>
    <p><strong>Discount (<?= $discount_rate * 100 ?>%):</strong> -₱<?= number_format($you_save, 2) ?></p>
  <?php endif; ?>
  <p><strong>Discounted Price:</strong> ₱<?= number_format($discounted_price, 2) ?></p>

  <!-- Highlighted Total -->
  <p class="mt-2">
    <strong class="text-green-600 text-lg">Total Paid:</strong> 
    <span class="text-green-700 font-extrabold text-xl ml-1">
      ₱<?= number_format($gcash_total, 2) ?>
    </span>
  </p>

  <!-- Highlighted Reference Number -->
  <p>
    <strong class="text-gray-700">Reference No.:</strong>
    <span class="text-red-600 font-bold text-lg ml-1">
      <?= htmlspecialchars($reference_number) ?>
    </span>
  </p>
</div>



        <a href="Homepage.php" class="text-green-600 hover:underline font-semibold text-lg">Back to Home</a>
      </div>
    <?php endif; ?>

    <?php if ($show_form): ?>
      <div class="flex flex-col items-center">
        <h3 class="font-semibold mb-4 text-2xl text-gray-700">Scan QR & Upload Proof</h3>
        <img src="img/gcash_qr.png" alt="GCash QR" class="w-60 h-60 border rounded-lg mb-4">
        <p class="text-center text-gray-600 text-sm mb-6">Scan using your GCash app</p>

        <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 w-full max-w-md text-lg">
          <input type="hidden" name="package_id" value="<?= intval($package_id) ?>">
          <input type="hidden" name="pax" value="<?= intval($pax) ?>">
          <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">
          <input type="hidden" name="total" value="<?= htmlspecialchars($gcash_total) ?>">
          <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
          <input type="hidden" name="address" value="<?= htmlspecialchars($address) ?>">
          <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
          <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">

          <label class="font-medium">Upload Screenshot</label>
          <input type="file" name="payment_screenshot" accept="image/*" required class="border p-3 rounded w-full">

          <div class="flex justify-between gap-3 mt-4">
            <button type="submit" name="confirm_payment"
              class="w-1/2 bg-green-600 hover:bg-green-700 text-white py-2 rounded font-medium">
              <i class="fa-solid fa-check-circle"></i> Confirm
            </button>

            <a href="preview_booking.php" 
              class="w-1/2 bg-red-500 text-white py-2 rounded font-medium text-center hover:bg-red-600">
              <i class="fa-solid fa-xmark"></i> Cancel
            </a>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include 'config/include/footer.php'; ?>
</body>
</html>
