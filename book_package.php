<?php
session_start();
include 'db_connect.php';

// Redirect if not logged in
if(!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user']['id'] ?? 0;

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id = $_POST['package_id'] ?? 0;
    $pax = $_POST['pax'] ?? 1;
    $booking_date = $_POST['booking_date'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';

    // Fetch sa price and title of package
    $stmt = $conn->prepare("SELECT price, title FROM packages WHERE id=?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $package = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $total_price = $package['price'] * $pax;

    // Generate reference number
    $reference_number = 'REF'.time().rand(1000,9999);

    if($payment_method === 'paymongo') {
        // Save booking with status nga pending
        $stmt = $conn->prepare("
            INSERT INTO bookings (tourist_id, package_id, booking_date, pax, status, total, reference_number)
            VALUES (?, ?, ?, ?, 'pending', ?, ?)
        ");
        $stmt->bind_param("iisids", $user_id, $package_id, $booking_date, $pax, $total_price, $reference_number);
        $stmt->execute();
        $booking_id = $stmt->insert_id;
        $stmt->close();

        // Save info for PayMongo checkout
        $_SESSION['paymongo_booking_id'] = $booking_id;
        $_SESSION['paymongo_amount'] = $total_price;
        header("Location: paymongo_checkout.php");
        exit;

    } elseif($payment_method === 'gcash') {
        // Handle file upload for GCash payment nga proof
        if(isset($_FILES['payment_proof']) && $_FILES['payment_proof']['error'] === 0) {
            $ext = pathinfo($_FILES['payment_proof']['name'], PATHINFO_EXTENSION);
            $filename = 'gcash_'.time().'_'.rand(1000,9999).'.'.$ext;
            $target = "uploads/".$filename;
            move_uploaded_file($_FILES['payment_proof']['tmp_name'], $target);

            // Save booking with uploaded nga payment proof
            $stmt = $conn->prepare("
                INSERT INTO bookings (tourist_id, package_id, booking_date, pax, status, total, reference_number, payment_proof)
                VALUES (?, ?, ?, ?, 'pending', ?, ?, ?)
            ");
            $stmt->bind_param("iisidss", $user_id, $package_id, $booking_date, $pax, $total_price, $reference_number, $filename);
            $stmt->execute();
            $stmt->close();

            header("Location: booking_success.php?ref=".$reference_number);
            exit;

        } else {
            die("Please upload GCash payment proof.");
        }

    } else {
        die("Invalid payment method.");
    }

} else {
    die("Invalid access.");
}
