<?php
session_start();
include '../config/db_connect.php';
header('Content-Type: application/json');

// Read JSON input
$input = json_decode(file_get_contents('php://input'), true);

if(!isset($input['id']) || !isset($input['dates'])){
    echo json_encode(['success'=>false,'message'=>'Missing data']);
    exit;
}

$pkg_id = intval($input['id']);
$dates = array_map('strval', $input['dates']); // ensure strings
$dates_json = json_encode($dates);

// Update DB safely using prepared statement
$stmt = $conn->prepare("UPDATE packages SET closed_dates=? WHERE id=?");
$stmt->bind_param('si', $dates_json, $pkg_id);

if($stmt->execute()){
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false,'message'=>'DB update failed: ' . $conn->error]);
}
