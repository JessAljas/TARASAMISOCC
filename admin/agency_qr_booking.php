<?php
session_start();
include '../config/db_connect.php';

// Only allow agency
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'agency') {
    header("Location: login.php");
    exit;
}

// ================== Handle AJAX actions ==================
if (isset($_POST['action'], $_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action'];

    switch ($action) {
        case 'approve':
            $stmt = $conn->prepare("UPDATE pay_via_qr SET status='approved' WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;

        case 'delete':
            $stmt = $conn->prepare("DELETE FROM pay_via_qr WHERE id=?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;

        case 'completed':
            $conn->begin_transaction();
            try {
                $stmt2 = $conn->prepare("SELECT * FROM pay_via_qr WHERE id=?");
                $stmt2->bind_param("i", $id);
                $stmt2->execute();
                $booking = $stmt2->get_result()->fetch_assoc();
                $stmt2->close();
                if (!$booking) throw new Exception("Booking not found.");

                $stmt = $conn->prepare("UPDATE pay_via_qr SET status='completed' WHERE id=?");
                $stmt->bind_param("i", $id);
                $stmt->execute();

                $fee_per_pax = 50;
                $service_fee = $fee_per_pax * $booking['pax'];
                $total_amount = (float)$booking['total'] + $service_fee;

                $stmt3 = $conn->prepare("INSERT INTO completed_booking 
                    (booking_id, package_id, tourist_id, pax, transaction_ref, mode_of_payment, status, service_fee, total_amount, checkout_url, approved_by, dateadded) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt3->bind_param(
                    "iiiisssddss",
                    $booking['id'],
                    $booking['package_id'],
                    $booking['tourist_id'],
                    $booking['pax'],
                    $booking['reference_number'],
                    $booking['mode_of_payment'] ?? 'GCash QR',
                    'completed',
                    $service_fee,
                    $total_amount,
                    '',
                    'agency'
                );
                if (!$stmt3->execute()) throw new Exception("Insert failed: " . $stmt3->error);
                $stmt3->close();
                $conn->commit();
                echo json_encode(['success' => true]);
            } catch (Exception $e) {
                $conn->rollback();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            exit;

        case 'reschedule':
            if (!isset($_POST['new_date'])) {
                echo json_encode(['success' => false, 'error' => 'New date not provided']);
                exit;
            }

            $new_date = $_POST['new_date'];
            $stmtBooking = $conn->prepare("SELECT tourist_id, booking_date FROM pay_via_qr WHERE id=?");
            $stmtBooking->bind_param("i", $id);
            $stmtBooking->execute();
            $bookingData = $stmtBooking->get_result()->fetch_assoc();
            $stmtBooking->close();

            if ($bookingData) {
                $tourist_id = $bookingData['tourist_id'];
                $old_date = $bookingData['booking_date'];
                $stmt = $conn->prepare("UPDATE pay_via_qr SET reschedule_date=?, status='reschedule_requested' WHERE id=?");
                $stmt->bind_param("si", $new_date, $id);
                $stmt->execute();

                $message = "Agency proposed to reschedule your booking #$id from " 
                        . date("F d, Y", strtotime($old_date)) 
                        . " to " . date("F d, Y", strtotime($new_date)) 
                        . ". Please confirm.";
                $stmtNotif = $conn->prepare("INSERT INTO notifications (tourist_id, message, booking_id) VALUES (?, ?, ?)");
                $stmtNotif->bind_param("isi", $tourist_id, $message, $id);
                $stmtNotif->execute();
                $stmtNotif->close();

                echo json_encode([
                    'success' => true,
                    'new_date' => date("F d, Y", strtotime($new_date))
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Booking not found']);
            }
            exit;
    }
}

// ================== Handle fetch itinerary AJAX ==================
if (isset($_GET['fetch_itinerary'])) {
    $booking_id = intval($_GET['booking_id']);

    // Get booking info including package_id
    $stmt = $conn->prepare("SELECT package_id FROM pay_via_qr WHERE id=?");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $booking = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$booking) {
        echo json_encode(['success'=>false,'error'=>'Booking not found']);
        exit;
    }

    $package_id = $booking['package_id'];

    // Fetch package details
    $stmtPackage = $conn->prepare("SELECT title, pickup_location, dropoff_location FROM packages WHERE id=?");
    $stmtPackage->bind_param("i", $package_id);
    $stmtPackage->execute();
    $packageData = $stmtPackage->get_result()->fetch_assoc();
    $stmtPackage->close();

    // Fetch pickup and dropoff times from itinerary table
    $pickup_time = $dropoff_time = '';
    $stmtTime = $conn->prepare("SELECT time FROM itinerary WHERE package_id=? ORDER BY time ASC LIMIT 1");
    $stmtTime->bind_param("i", $package_id);
    $stmtTime->execute();
    $rowTime = $stmtTime->get_result()->fetch_assoc();
    if ($rowTime) $pickup_time = date("g:i A", strtotime($rowTime['time']));
    $stmtTime->close();

    $stmtTime = $conn->prepare("SELECT time FROM itinerary WHERE package_id=? ORDER BY time DESC LIMIT 1");
    $stmtTime->bind_param("i", $package_id);
    $stmtTime->execute();
    $rowTime = $stmtTime->get_result()->fetch_assoc();
    if ($rowTime) $dropoff_time = date("g:i A", strtotime($rowTime['time']));
    $stmtTime->close();

    // Fetch full itinerary
    $stmt = $conn->prepare("SELECT time, destination_name, activity_type FROM itinerary WHERE package_id=? ORDER BY time ASC");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $itinerary = [];

    // Add pickup location as first entry
    if (!empty($packageData['pickup_location'])) {
        $itinerary[] = [
            'time' => $pickup_time,
            'destination_name' => $packageData['pickup_location'],
            'activity_type' => 'pickup'
        ];
    }

    // Add normal itinerary
    while ($row = $result->fetch_assoc()) {
        $itinerary[] = $row;
    }
    $stmt->close();

    // Add dropoff location as last entry
    if (!empty($packageData['dropoff_location'])) {
        $itinerary[] = [
            'time' => $dropoff_time,
            'destination_name' => $packageData['dropoff_location'],
            'activity_type' => 'dropoff'
        ];
    }

    // Return JSON including package info
    echo json_encode([
        'success' => true,
        'itinerary' => $itinerary,
        'package' => [
            'title' => $packageData['title'] ?? 'N/A',
            'pickup_location' => $packageData['pickup_location'] ?? 'TBA',
            'dropoff_location' => $packageData['dropoff_location'] ?? 'TBA',
            'pickup_time' => $pickup_time,
            'dropoff_time' => $dropoff_time
        ]
    ]);
    exit;
}

// ================== Fetch bookings for table ==================
$stmt = $conn->prepare("
    SELECT 
        q.*,
        q.fullname AS tourist_name,
        q.phone AS tourist_phone,
        q.address AS tourist_address,
        p.title AS package_name
    FROM pay_via_qr q
    LEFT JOIN packages p ON q.package_id = p.id
    ORDER BY q.booking_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>Manage Bookings - Agency</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet" />
<style>
body { font-family: 'Poppins', sans-serif; }
.status-badge { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 500; text-transform: capitalize; font-size: 0.875rem; }
.status-completed { background-color: #22c55e; color: white; }
.status-approved { background-color: #2563eb; color: white; }
.status-pending { background-color: #eab308; color: white; }
.status-cancelled { background-color: #ef4444; color: white; }
.modal { display: none; position: fixed; z-index: 50; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
.modal-content { background-color: #fff; margin: 5% auto; padding: 1rem; border-radius: 0.5rem; max-width: 600px; position: relative; }
.close { position: absolute; top: 0.5rem; right: 1rem; font-size: 1.5rem; cursor: pointer; }
</style>
</head>
<body class="bg-gray-100">

<!-- Sidebar -->
<div class="fixed top-0 left-0 h-screen w-64 bg-white shadow-md overflow-y-auto z-20">
    <?php include 'sidebar.php'; ?>
</div>

<div class="ml-64 p-6">
<h1 class="text-2xl font-bold mb-4">All Bookings - Pay via QR</h1>

<!-- Filters -->
<div class="flex justify-between items-center gap-2 mb-4">
    <div class="flex gap-2">
        <select id="statusFilter" onchange="filterTable()" class="px-3 py-2 border rounded">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
        <input type="date" id="dateFilter" onchange="filterTable()" class="px-3 py-2 border rounded" />
    </div>
    <div class="flex items-center gap-2">
        <label for="searchInput" class="text-sm font-medium text-gray-700">Search:</label>
        <input type="text" id="searchInput" placeholder="Search..." class="px-3 py-2 border rounded w-48" onkeyup="filterTable()" />
    </div>
</div>
<!-- Bookings Table -->
<div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
<table id="bookingsTable" class="min-w-full text-sm border-collapse border border-gray-300">
<thead class="bg-green-500 text-white">
<tr>
    <th class="px-4 py-2 border">Reference #</th>
    <th class="px-4 py-2 border">Package</th>
    <th class="px-4 py-2 border">Pax</th>
    <th class="px-4 py-2 border">Total</th>
    <th class="px-4 py-2 border">Payment Proof</th>
    <th class="px-4 py-2 border">Status</th>
    <th class="px-4 py-2 border">Actions</th>
</tr>
</thead>
<tbody>
<?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()):
    $status = strtolower($row['status'] ?: 'pending'); ?>
<tr 
    data-id="<?= htmlspecialchars($row['id'] ?? '') ?>" 
    data-status="<?= htmlspecialchars($status ?? '') ?>" 
    data-destinations="<?= htmlspecialchars(implode('|', explode(',', $row['destinations'] ?? ''))) ?>"
    data-tourist="<?= htmlspecialchars($row['tourist_name'] ?? 'N/A') ?>"
    data-email="<?= htmlspecialchars($row['email'] ?? 'N/A') ?>"
    data-phone="<?= htmlspecialchars($row['phone'] ?? 'N/A') ?>"
    data-address="<?= htmlspecialchars($row['address'] ?? 'N/A') ?>"
    data-date="<?= htmlspecialchars($row['booking_date'] ?? '') ?>"
    class="hover:bg-gray-50 transition-colors duration-200"
>
    <!-- Reference Number with Active Badge -->
    <td class="px-4 py-3 border text-center">
        <span class="inline-flex items-center bg-yellow-100 text-green-800 text-sm font-semibold px-3 py-1 rounded-full border border-green-300 shadow-sm">
            <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
            <?= htmlspecialchars($row['reference_number'] ?? 'N/A') ?>
        </span>
    </td>

    <!-- Package Name -->
    <td class="px-4 py-3 border"><?= htmlspecialchars($row['package_name'] ?? 'N/A') ?></td>

    <!-- Pax -->
    <td class="px-4 py-3 border"><?= htmlspecialchars($row['pax'] ?? '0') ?> pax</td>

    <!-- Total -->
    <td class="px-4 py-3 border">
        ₱<?= number_format(($row['total'] ?? ($row['price'] ?? 0) * ($row['pax'] ?? 1)), 2) ?>
    </td>

    <!-- Payment Proof -->
    <td class="px-4 py-3 border text-center">
        <?php
            $img = '';
            if (!empty($row['proof_image'])) {
                $img = '../' . $row['proof_image'];
            } elseif (!empty($row['payment_proof'])) {
                $img = '../' . $row['payment_proof'];
            }

            if (!empty($img)): ?>
                <button 
                    onclick="showProofModal('<?= htmlspecialchars($img) ?>')" 
                    class="text-blue-600 hover:text-blue-400 flex items-center justify-center mx-auto transition duration-200">
                    <i class="fas fa-file-alt mr-1"></i> View
                </button>
            <?php else: ?>
                <span class="text-gray-400 italic">No proof</span>
        <?php endif; ?>
    </td>

    <!-- Status -->
    <td class="px-4 py-3 border text-center">
        <span class="px-3 py-1 rounded-full text-sm font-medium 
            <?= ($status ?? '') === 'approved' ? 'bg-green-100 text-green-700' : 
                (($status ?? '') === 'pending' ? 'bg-yellow-100 text-yellow-700' : 
                'bg-red-100 text-red-700') ?>">
            <?= ucfirst($status ?? 'Unknown') ?>
        </span>
    </td>

    <!-- Actions -->
    <td class="px-4 py-3 border text-center">
        <button 
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1.5 rounded-lg transition duration-200 shadow-sm flex items-center justify-center gap-1 view-details-btn"
            title="View Details"
        >
            <i class="fas fa-eye"></i>
            <span class="text-sm font-medium">View</span>
        </button>
    </td>
</tr>

<?php endwhile; else: ?>
<tr>
    <td colspan="7" class="text-center py-4 text-gray-500">No bookings found.</td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>


<!-- ✅ Payment Proof Modal -->
<div id="proofModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="relative bg-white rounded-lg shadow-xl p-4">
        <!-- Close button -->
        <button onclick="closeProofModal()" 
                class="absolute -top-3 -right-3 bg-red-600 text-white rounded-full w-7 h-7 flex items-center justify-center text-lg hover:bg-red-500">
            &times;
        </button>
        <!-- Title -->
        <h2 class="text-lg font-semibold text-center mb-3 text-green-700">Payment Proof</h2>
        <!-- Image -->
        <img id="proofModalImg" src="" alt="Payment Proof" class="max-w-lg max-h-[80vh] w-auto h-auto rounded-lg border border-gray-300 shadow-lg">
    </div>
</div>

<!-- ✅ JavaScript -->
<script>
function showProofModal(imgSrc) {
    const modal = document.getElementById('proofModal');
    const modalImg = document.getElementById('proofModalImg');
    modalImg.src = imgSrc;
    modal.classList.remove('hidden');
}

function closeProofModal() {
    const modal = document.getElementById('proofModal');
    const modalImg = document.getElementById('proofModalImg');
    modal.classList.add('hidden');
    modalImg.src = '';
}
</script>

<!-- Booking Modal -->
<div id="bookingModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-6 relative animate-fadeIn">
    
    <!-- Close Button -->
    <button 
      class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 transition"
      onclick="closeBookingModal()">✕</button>
    
    <!-- Title -->
    <h2 class="text-2xl font-semibold text-green-600 mb-5 text-center border-b pb-2">
      Booking Details
    </h2>

<!-- Booking Info -->
<div class="space-y-2 text-sm text-gray-800">
    <p><strong>Reference #:</strong> <span id="modalRef" class="text-gray-600"></span></p>
    <p><strong>Tourist:</strong> <span id="modalTourist" class="text-gray-600"></span></p>
    <p><strong>Email:</strong> <span id="modalEmail" class="text-gray-600"></span></p>
    <p><strong>Phone:</strong> <span id="modalPhone" class="text-gray-600"></span></p>
    <p><strong>Address:</strong> <span id="modalAddress" class="text-gray-600"></span></p>
    <p><strong>Package:</strong> <span id="modalPackage" class="text-gray-600"></span></p>
    <p><strong>Booking Date:</strong> <span id="modalBookingDate" class="text-gray-600"></span></p>
    <p><strong>Pax:</strong> <span id="modalPax" class="text-gray-600"></span></p>
    <p><strong>Total:</strong> <span class="text-green-700 font-semibold">₱<span id="modalTotal"></span></span></p>
    <p><strong>Status:</strong> 
        <span id="modalStatus" class="px-2 py-1 rounded text-xs font-medium text-white"></span>
    </p>

    <!-- Destinations -->
    <div class="mt-4">
        <p class="font-semibold text-gray-900 mb-1">Pickup & Drop-off:</p>
        <ul id="modalPickupDropoff" class="list-disc list-inside text-gray-700 ml-3"></ul>

        <p class="font-semibold text-gray-900 mt-4 mb-1">Full Itinerary:</p>
        <table id="modalItinerary" class="w-full text-sm border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-100 text-gray-700">
                <tr>
                    <th class="px-3 py-2 text-left">Time</th>
                    <th class="px-3 py-2 text-left">Destination</th>
                    <th class="px-3 py-2 text-left">Activity</th>
                </tr>
            </thead>
            <tbody class="text-gray-600"></tbody>
        </table>
    </div>
</div>

<!-- Action Buttons -->
<div class="mt-6 flex flex-wrap justify-end gap-2">
    <button id="approveBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition">Approve</button>
    <button id="completeBtn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition">Complete</button>
    <button id="rescheduleBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded transition">Reschedule</button>
    <button id="cancelBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition">Cancel</button>
</div>


<!-- Reschedule Modal -->
<div id="rescheduleModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-11/12 max-w-md p-6 relative">
    <button class="absolute top-3 right-3 text-2xl font-bold text-gray-700 hover:text-gray-900" onclick="closeRescheduleModal()">&times;</button>
    <h2 class="text-xl font-semibold mb-4">Reschedule Booking</h2>
    <form id="rescheduleForm" class="space-y-3">
        <input type="hidden" id="rescheduleBookingId">
        <label class="block font-medium">New Booking Date</label>
        <input type="date" id="rescheduleDate" required class="border px-3 py-2 rounded w-full">
        <div class="flex justify-end gap-2 mt-2">
            <button type="button" onclick="closeRescheduleModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
            <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Save</button>
        </div>
    </form>
  </div>
</div>

<script>
// =================== Helpers ===================
function showModal(id){ document.getElementById(id).style.display='flex'; }
function closeModal(id){ document.getElementById(id).style.display='none'; }
function closeBookingModal(){ closeModal('bookingModal'); }
function openRescheduleModal(id, oldDate){ 
    $('#rescheduleBookingId').val(id); 
    $('#rescheduleDate').val(oldDate); 
    showModal('rescheduleModal'); 
}
function closeRescheduleModal(){ closeModal('rescheduleModal'); }

// =================== Global current booking ID ===================
let currentBookingId = null;

// =================== Open Booking Modal ===================
$(document).on('click', '.view-details-btn', function(){
    const row = $(this).closest('tr');
    currentBookingId = row.data('id');
    const status = row.data('status');

    // Basic info
    $('#modalRef').text(row.find('td:eq(0)').text().trim());
    $('#modalPackage').text(row.find('td:eq(1)').text().trim());
    $('#modalPax').text(row.find('td:eq(2)').text().trim());
    $('#modalTotal').text(row.find('td:eq(3)').text().replace('₱','').trim());
    $('#modalTourist').text(row.data('tourist') || 'N/A');
    $('#modalEmail').text(row.data('email') || 'N/A');
    $('#modalPhone').text(row.data('phone') || 'N/A');
    $('#modalAddress').text(row.data('address') || 'N/A');
    $('#modalBookingDate').text(row.data('date') || 'Not specified');
    $('#modalStatus').text(status).attr('class', 'px-2 py-1 rounded text-xs font-medium text-white ' + 
        (status === 'approved' ? 'bg-green-500' : status === 'pending' ? 'bg-yellow-500' : 'bg-gray-400'));

    // Clear previous itinerary and pickup/dropoff
    $('#modalItinerary tbody').empty();
    $('#modalPickupDropoff').empty();

    // Fetch itinerary from PHP
    $.getJSON('manage_booking.php', { fetch_itinerary: 1, booking_id: currentBookingId }, function(data){
        if (data.success && data.itinerary.length > 0) {
            data.itinerary.forEach(i => {
                let rowClass = '';
                let icon = '';

                if(i.activity_type === 'pickup') {
                    rowClass = 'bg-blue-100 font-semibold';
                    icon = '🚗 Pickup';
                } else if(i.activity_type === 'dropoff') {
                    rowClass = 'bg-red-100 font-semibold';
                    icon = '🏁 Dropoff';
                } else {
                    icon = i.activity_type.charAt(0).toUpperCase() + i.activity_type.slice(1);
                }

                // Append to itinerary table
                $('#modalItinerary tbody').append(`
                    <tr class="border-t ${rowClass}">
                        <td class="px-3 py-1">${i.time}</td>
                        <td class="px-3 py-1">${i.destination_name}</td>
                        <td class="px-3 py-1">${icon}</td>
                    </tr>
                `);

                // Append to Pickup & Drop-off list
                if(i.activity_type === 'pickup' || i.activity_type === 'dropoff'){
                    $('#modalPickupDropoff').append(
                        `<li><strong>${i.activity_type.charAt(0).toUpperCase() + i.activity_type.slice(1)}:</strong> ${i.destination_name} at ${i.time}</li>`
                    );
                }
            });
        } else {
            $('#modalItinerary tbody').append('<tr><td colspan="3" class="px-3 py-2 text-center text-gray-400 italic">No itinerary found</td></tr>');
            $('#modalPickupDropoff').append('<li class="italic text-gray-400">No pickup/dropoff info</li>');
        }
    });

    // Show buttons depending on status
    $('#approveBtn').toggle(status === 'pending');
    $('#completeBtn').toggle(status === 'approved');
    $('#rescheduleBtn').toggle(status === 'pending' || status === 'approved');
    $('#cancelBtn').toggle(status !== 'completed' && status !== 'canceled');

    showModal('bookingModal');
});

// =================== Modal Button Actions ===================
$('#approveBtn').click(function(){
    if(!currentBookingId) return alert('Booking ID not set!');
    if(!confirm('Approve this booking?')) return;
    $.post('manage_booking.php', { action:'approve', id: currentBookingId }, function(res){
        const data = JSON.parse(res);
        if(data.success){
            alert('Booking approved!');
            closeBookingModal();
            location.reload();
        } else alert('Error: ' + (data.error || 'Unknown'));
    });
});

$('#completeBtn').click(function(){
    if(!currentBookingId) return alert('Booking ID not set!');
    if(!confirm('Mark this booking as completed?')) return;
    $.post('manage_booking.php', { action:'completed', id: currentBookingId }, function(res){
        const data = JSON.parse(res);
        if(data.success){
            alert('Booking completed!');
            closeBookingModal();
            location.reload();
        } else alert('Error: ' + (data.error || 'Unknown'));
    });
});

$('#cancelBtn').click(function(){
    if(!currentBookingId) return alert('Booking ID not set!');
    if(!confirm('Cancel this booking?')) return;
    $.post('manage_booking.php', { action:'delete', id: currentBookingId }, function(res){
        const data = JSON.parse(res);
        if(data.success){
            alert('Booking canceled!');
            closeBookingModal();
            location.reload();
        } else alert('Error: ' + (data.error || 'Unknown'));
    });
});

$('#rescheduleBtn').click(function(){
    if(!currentBookingId) return alert('Booking ID not set!');
    const oldDate = $('#modalBookingDate').text();
    openRescheduleModal(currentBookingId, oldDate);
});

// =================== Reschedule Form Submit ===================
$('#rescheduleForm').on('submit', function(e){
    e.preventDefault();
    const id = $('#rescheduleBookingId').val();
    const new_date = $('#rescheduleDate').val();
    if(!id || !new_date) return alert('Please select a new date.');

    $.post('manage_booking.php', { action:'reschedule', id, new_date }, function(res){
        const data = JSON.parse(res);
        if(data.success){
            alert('Booking rescheduled!');
            closeRescheduleModal();
            closeBookingModal();
            location.reload();
        } else alert('Error: ' + (data.error || 'Unknown'));
    });
});
</script>

</body>
</html>