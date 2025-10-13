<?php
session_start();
include 'config/db_connect.php'; // The database connection file

// Initialize the variables
$success = false;
$error   = '';
$show_form = true;
$reference_number = '';

// Get POST nga data
$package_id   = $_POST['package_id'] ?? 0;
$pax          = $_POST['pax'] ?? 1;
$booking_date = $_POST['booking_date'] ?? null;
$total        = $_POST['total'] ?? 0;

// Customer nga details
$fullname = $_POST['fullname'] ?? '';
$address  = $_POST['address'] ?? '';
$email    = $_POST['email'] ?? '';
$phone    = $_POST['phone'] ?? '';

// Fetch package details
$stmt = $conn->prepare("SELECT * FROM packages WHERE id=?");
$stmt->bind_param("i", $package_id);
$stmt->execute();
$package = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$package) $error = "Package not found.";

// Handle payment confirmation nga code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_payment']) && empty($error)) {
    $screenshot_path  = null;

    // Handle the screenshot upload para sa payment proof
    if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] === 0) {
        $upload_dir = 'uploads/gcash/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['payment_screenshot']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
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

    // Insert payment record into sa table nga pay_via_qr
    if (empty($error)) {
// Code nga mo generate sa unique transaction nga reference 
        do {
            $reference_number = 'ref' . substr(bin2hex(random_bytes(3)), 0, 5); // 3 bytes - 6 hex chars, take first 5
            $check_stmt = $conn->prepare("SELECT id FROM pay_via_qr WHERE reference_number = ?");
            $check_stmt->bind_param("s", $reference_number);
            $check_stmt->execute();
            $check_stmt->store_result();
        } while ($check_stmt->num_rows > 0);
        $check_stmt->close();


        $stmt2 = $conn->prepare("INSERT INTO pay_via_qr 
        (tourist_id, fullname, address, email, phone, package_id, booking_date, pax, total, amount, status, payment_date, proof_image, reference_number, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW(), ?, ?, NOW())");


        $payment_proof = $screenshot_path;
        $amount = $total;
        $proof_image = $screenshot_path;

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
            $total,                    
            $amount,                   
            $proof_image,              
            $reference_number     
        );

        if($stmt2->execute()){
            $success = true;
            $show_form = false;
        } else {
            if (!empty($screenshot_path) && file_exists($screenshot_path)) @unlink($screenshot_path);
            $error = "Failed to save payment. ".$stmt2->error;
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
<link href="https://fonts.googleapis.com/css2?family=Fredericka+the+Great&display=swap" rel="stylesheet">

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
        <!-- Green circle with Font Awesome check sa Payment Gcash qr nga payment -->
        <div class="w-20 h-20 mx-auto mb-4 flex items-center justify-center rounded-full bg-green-500">
            <i class="fa-solid fa-check text-green-900 text-4xl"></i>
        </div>

        <h2 class="text-3xl font-bold mb-4">Payment Successful!</h2>
       <h4 class="text-xl mb-6 text-gray-700">
    <span class="font-bold italic text-red-600">Thank you for choosing us!</span><br> 
   <span>
    We will further review the submitted receipt.
    </span><br>
    <h1 class=" italic text-red-600">
       NOTE:  Please always check the date of your tour since it may be <br>rescheduled due to weather, as stated in our policy.
    </h2>
    </h4>

        <div class="mb-6 text-gray-700 text-lg space-y-1">
            <p><strong>Date of Tour:</strong> <?= htmlspecialchars($booking_date ?? 'N/A') ?></p>
            <p><strong>Pax:</strong> <?= htmlspecialchars($pax) ?></p>
            <p><strong>Total:</strong> ₱<?= number_format((float)$total,2) ?></p>
            <p>
                <strong>Reference No.:</strong> 
                <span class="text-red-600" style="font-family: Arial, sans-serif; font-weight: bold; font-size: 1.25rem;">
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
                <img src="" alt="GCash QR" class="w-60 h-60 border rounded-lg mb-4">
                <p class="text-center text-gray-600 text-sm mb-6">Scan using your GCash app</p>

                <form method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 w-full max-w-md text-lg">
                    <input type="hidden" name="package_id" value="<?= intval($package_id) ?>">
                    <input type="hidden" name="pax" value="<?= intval($pax) ?>">
                    <input type="hidden" name="booking_date" value="<?= htmlspecialchars($booking_date) ?>">
                    <input type="hidden" name="total" value="<?= htmlspecialchars($total) ?>">

                    <input type="hidden" name="fullname" value="<?= htmlspecialchars($fullname) ?>">
                    <input type="hidden" name="address" value="<?= htmlspecialchars($address) ?>">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">

                    <label class="font-medium">Upload Screenshot</label>
                    <input type="file" name="payment_screenshot" accept="image/*" required class="border p-3 rounded w-full">

                    <button type="submit" name="confirm_payment" class="bg-green-600 text-white py-3 rounded hover:bg-green-700 mt-4 w-full font-semibold text-lg">Confirm Payment</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include 'config/include/footer.php'; ?>
</body>
</html>
