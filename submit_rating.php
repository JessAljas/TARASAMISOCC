<?php
session_start();
include 'config/db_connect.php'; // The database connection file

// Only tourists can submit ug ratings
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist') {
    header("Location: login.php");
    exit;
}

$tourist_id = $_SESSION['user']['id'];
$package_id = $_POST['package_id'] ?? 0;
$rating     = $_POST['rating'] ?? 0;

if ($package_id && $rating >= 1 && $rating <= 5) {
    // E check if tourist is already rated na in this package
    $stmt = $conn->prepare("SELECT id FROM ratings WHERE tourist_id=? AND package_id=?");
    $stmt->bind_param("ii", $tourist_id, $package_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        // Update sa rating
        $stmt = $conn->prepare("UPDATE ratings SET rating=?, created_at=NOW() WHERE id=?");
        $stmt->bind_param("ii", $rating, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        // Insert sa new rating dadtu sa database
        $stmt = $conn->prepare("INSERT INTO ratings (tourist_id, package_id, rating) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $tourist_id, $package_id, $rating);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: package_details.php?id=" . $package_id);
exit;
?>
