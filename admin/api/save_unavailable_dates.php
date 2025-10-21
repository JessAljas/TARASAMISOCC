<?php
session_start();
include '../config/db_connect.php';

header('Content-Type: application/json');
error_reporting(0); // prevent warnings from breaking JSON

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

$package_id = isset($data['package_id']) ? intval($data['package_id']) : null;
$dates = isset($data['dates']) && is_array($data['dates']) ? $data['dates'] : [];

if ($package_id === null) {
    echo json_encode(['success' => false, 'message' => 'No package selected']);
    exit;
}

// Begin transaction
$conn->begin_transaction();

try {
    // Delete old dates for this package
    $stmtDel = $conn->prepare("DELETE FROM unavailable_dates WHERE package_id = ?");
    $stmtDel->bind_param("i", $package_id);
    $stmtDel->execute();

    // Insert new dates
    if (count($dates) > 0) {
        $stmtIns = $conn->prepare("INSERT INTO unavailable_dates (package_id, date) VALUES (?, ?)");
        foreach ($dates as $date) {
            // Optional: validate date format YYYY-MM-DD
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) continue;
            $stmtIns->bind_param("is", $package_id, $date);
            $stmtIns->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Unavailable dates saved successfully.']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
