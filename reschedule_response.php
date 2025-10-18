<?php
session_start();
include 'config/db_connect.php';

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist'){
    exit(json_encode(['success'=>false,'error'=>'Unauthorized']));
}

$tourist_id = $_SESSION['user']['id'];
$id = intval($_POST['id'] ?? 0);
$action = $_POST['action'] ?? '';

$stmt = $conn->prepare("SELECT booking_date, reschedule_date FROM pay_via_qr WHERE id=? AND tourist_id=?");
$stmt->bind_param("ii",$id,$tourist_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$booking){
    echo json_encode(['success'=>false,'error'=>'Booking not found']);
    exit;
}

if($action==='approve'){
    $stmt = $conn->prepare("UPDATE pay_via_qr SET booking_date=reschedule_date, reschedule_date=NULL, status='approved' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
} elseif($action==='decline'){
    $stmt = $conn->prepare("UPDATE pay_via_qr SET reschedule_date=NULL, status='approved' WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success'=>true]);
