<?php
session_start();
include '../config/db_connect.php';

// Only allow agency
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'agency') {
    header("Location: login.php");
    exit;
}

// Handle AJAX actions
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

// Fetch all bookings
$stmt = $conn->prepare("
    SELECT q.*, 
           t.fullname AS tourist_name, 
           p.title AS package_name,
           GROUP_CONCAT(ts.name_of_tourist_spot SEPARATOR ', ') AS destinations
    FROM pay_via_qr q
    LEFT JOIN tourists t ON q.tourist_id = t.id
    LEFT JOIN packages p ON q.package_id = p.id
    LEFT JOIN package_destinations pd ON p.id = pd.package_id
    LEFT JOIN tourist_spots ts ON pd.tourist_spot_id = ts.id
    GROUP BY q.id
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
    data-id="<?= $row['id'] ?>" 
    data-status="<?= $status ?>" 
    data-destinations="<?= htmlspecialchars(implode('|', explode(',', $row['destinations']))) ?>"
    data-tourist="<?= htmlspecialchars($row['tourist_name'] ?? 'N/A') ?>"
    data-email="<?= htmlspecialchars($row['email'] ?? 'N/A') ?>"
    data-date="<?= htmlspecialchars($row['booking_date'] ?? '') ?>"
>

    <!-- Reference Number with Active Badge -->
    <td class="px-4 py-2 border text-center">
        <span class="inline-flex items-center bg-yellow-200 text-green-800 text-sm font-semibold px-3 py-1 rounded-full border border-green-300 shadow-sm">
            <span class="w-2.5 h-2.5 bg-green-500 rounded-full mr-2 animate-pulse"></span>
            <?= htmlspecialchars($row['reference_number']) ?>
        </span>
    </td>

    <td class="px-4 py-2 border"><?= htmlspecialchars($row['package_name']) ?></td>
    <td class="px-4 py-2 border"><?= $row['pax'] ?> pax</td>
    <td class="px-4 py-2 border">₱<?= number_format($row['total'] ?: $row['price'] * $row['pax'], 2) ?></td>

    <!-- Payment Proof Column -->
    <td class="px-4 py-2 border text-center">
        <?php
            $img = !empty($row['proof_image']) ? '../' . $row['proof_image'] : (!empty($row['payment_proof']) ? '../' . $row['payment_proof'] : '');
            if (!empty($img)): ?>
                <button 
                    onclick="showProofModal('<?= htmlspecialchars($img) ?>')" 
                    class="text-blue-600 hover:text-blue-400 flex items-center mx-auto transition duration-200">
                    <i class="fas fa-file-alt mr-1"></i> View
                </button>
            <?php else: ?>
                <span class="text-gray-400">No proof</span>
        <?php endif; ?>
    </td>

    <!-- Status -->
    <td class="px-4 py-2 border text-center">
        <span class="status-badge status-<?= $status ?>"><?= ucfirst($status) ?></span>
    </td>

    <!-- Actions -->
    <td class="px-4 py-2 border flex gap-1 justify-center">
        <button class="bg-gray-200 text-gray-700 px-2 py-1 rounded hover:bg-gray-300 view-details-btn" title="View Details">
            <i class="fas fa-eye"></i>
        </button>
        <?php if ($status !== 'completed'): ?>
        <button class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-500 complete-btn" title="Mark Completed">
            <i class="fas fa-check"></i>
        </button>
        <?php endif; ?>
    </td>
</tr>

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
<div id="bookingModal" class="modal hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 relative">
    <button class="absolute top-3 right-3 text-gray-500 hover:text-gray-700" onclick="closeBookingModal()">✕</button>
    
    <h2 class="text-xl font-semibold text-green-600 mb-4 text-center">Booking Details</h2>

    <div class="space-y-2 text-sm">
        <p><strong>Reference #:</strong> <span id="modalRef"></span></p>
        <p><strong>Tourist:</strong> <span id="modalTourist"></span></p>
        <p><strong>Email:</strong> <span id="modalEmail"></span></p>
        <p><strong>Package:</strong> <span id="modalPackage"></span></p>
        <p><strong>Booking Date:</strong> <span id="modalBookingDate"></span></p>
        <p><strong>Pax:</strong> <span id="modalPax"></span></p>
        <p><strong>Total:</strong> ₱<span id="modalTotal"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus" class="status-badge"></span></p>
        <p><strong>Destinations:</strong></p>
        <ul id="modalDestinations" class="list-disc list-inside text-gray-700"></ul>
    </div>

    <div class="mt-6 flex justify-end gap-2">
      <button id="approveBtn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">Approve</button>
      <button id="rescheduleBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded">Reschedule</button>
      <button id="cancelBtn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Cancel</button>
    </div>
  </div>
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

<!-- Completed Modal -->
<div id="completedConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-11/12 max-w-md p-6 relative">
    <button class="absolute top-3 right-3 text-2xl font-bold text-gray-700 hover:text-gray-900" onclick="closeCompletedModal()">&times;</button>
    <h2 class="text-xl font-semibold mb-4">Complete Booking</h2>
    <p>Are you sure you want to mark this booking as <strong>completed</strong>?</p>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="closeCompletedModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
        <button id="confirmCompletedBtn" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600">Yes, Complete</button>
    </div>
  </div>
</div>

<!-- Cancel Modal -->
<div id="cancelConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-11/12 max-w-md p-6 relative">
    <button class="absolute top-3 right-3 text-2xl font-bold text-gray-700 hover:text-gray-900" onclick="closeCancelModal()">&times;</button>
    <h2 class="text-xl font-semibold mb-4">Cancel Booking</h2>
    <p>Are you sure you want to cancel this booking?</p>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="closeCancelModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">No</button>
        <button id="confirmCancelBtn" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Yes, Cancel</button>
    </div>
  </div>
</div>
<!-- Approve Modal -->
<div id="approveConfirmModal" class="fixed inset-0 bg-black/60 flex items-center justify-center z-50 hidden">
  <div class="bg-white rounded-xl w-11/12 max-w-md p-6 relative">
    <button class="absolute top-3 right-3 text-2xl font-bold text-gray-700 hover:text-gray-900" onclick="closeApproveModal()">&times;</button>
    <h2 class="text-xl font-semibold mb-4">Approve Booking</h2>
    <p>Are you sure you want to <strong>approve</strong> this booking?</p>
    <div class="flex justify-end gap-2 mt-4">
        <button onclick="closeApproveModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
        <button id="confirmApproveBtn" class="px-4 py-2 rounded bg-blue-500 text-white hover:bg-blue-600">Yes, Approve</button>
    </div>
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
function closeCompletedModal(){ closeModal('completedConfirmModal'); }
function closeCancelModal(){ closeModal('cancelConfirmModal'); }
function closeApproveModal(){ closeModal('approveConfirmModal'); }
function showProof(url){ window.open(url,'_blank'); }

// =================== Table Filters ===================
function filterTable(){
    const statusVal = $('#statusFilter').val().toLowerCase();
    const dateVal = $('#dateFilter').val();
    const searchVal = $('#searchInput').val().toLowerCase();

    $('#bookingsTable tbody tr').each(function(){
        const row = $(this);
        const status = row.data('status');
        const date = row.data('date');
        const text = row.text().toLowerCase();
        row.toggle(
            (statusVal === '' || status === statusVal) &&
            (dateVal === '' || date === dateVal) &&
            (text.includes(searchVal))
        );
    });
}

// =================== Open Booking Modal ===================
$('.view-details-btn').click(function(){
    const row = $(this).closest('tr');
    const id = row.data('id');
    const status = row.data('status');

    // ✅ Mapping columns
    const reference = row.find('td:eq(0)').text().trim();
    const packageName = row.find('td:eq(1)').text().trim();
    const pax = row.find('td:eq(2)').text().trim();
    const total = row.find('td:eq(3)').text().replace('₱','').trim();

    // ✅ Get tourist and email
    const touristName = row.data('tourist') || 'N/A';
    const touristEmail = row.data('email') || 'N/A';

    // ✅ Fill modal fields
    $('#modalTourist').text(touristName);
    $('#modalEmail').text(touristEmail);
    $('#modalPackage').text(packageName);
    $('#modalBookingDate').text(row.data('date') || 'Not specified');
    $('#modalPax').text(pax);
    $('#modalTotal').text(total);
    $('#modalRef').text(reference);
    $('#modalStatus').text(status).attr('class', 'status-badge status-' + status);

    // ✅ Destinations (split by | for accuracy)
    const dests = row.data('destinations') ? row.data('destinations').split('|') : [];
    $('#modalDestinations').empty();
    dests.forEach(d => {
        if (d.trim() !== '') $('#modalDestinations').append('<li>' + d.trim() + '</li>');
    });

    // Buttons
    $('#approveBtn').off('click').on('click', function(){
        $('#confirmApproveBtn').data('id', id);
        showModal('approveConfirmModal');
    });

    $('#rescheduleBtn').off('click').on('click', function(){
        openRescheduleModal(id, row.data('date'));
    });

    $('#cancelBtn').off('click').on('click', function(){
        $('#confirmCancelBtn').data('id', id);
        showModal('cancelConfirmModal');
    });

    showModal('bookingModal');
});

// =================== Approve Confirmation ===================
$('#confirmApproveBtn').off('click').on('click', function(){
    const id = $(this).data('id');
    if(!id) return alert('Booking ID not set!');
    $.post('manage_booking.php', {action:'approve', id}, function(res){
        const data = JSON.parse(res);
        if(data.success){
            closeApproveModal();
            closeBookingModal();
            location.reload();
        } else {
            alert('Error approving booking.');
        }
    });
});

// =================== Complete Booking ===================
$('.complete-btn').click(function(){
    const id = $(this).closest('tr').data('id');
    $('#confirmCompletedBtn').data('id', id);
    showModal('completedConfirmModal');
});

$('#confirmCompletedBtn').off('click').on('click', function(){
    const id = $(this).data('id');
    if(!id) return alert('Booking ID not set!');
    $.post('manage_booking.php', {action:'completed', id}, function(res){
        const data = JSON.parse(res);
        if(data.success){
            closeCompletedModal();
            closeBookingModal();
            location.reload();
        } else {
            alert(data.error);
        }
    });
});

// =================== Cancel Booking ===================
$('#confirmCancelBtn').off('click').on('click', function(){
    const id = $(this).data('id');
    if(!id) return alert('Booking ID not set!');
    $.post('manage_booking.php', {action:'delete', id}, function(res){
        const data = JSON.parse(res);
        if(data.success){
            closeCancelModal();
            closeBookingModal();
            location.reload();
        } else {
            alert(data.error);
        }
    });
});

// =================== Reschedule Booking ===================
$('#rescheduleForm').off('submit').on('submit', function(e){
    e.preventDefault();
    const id = $('#rescheduleBookingId').val();
    const new_date = $('#rescheduleDate').val();
    if(!id || !new_date) return alert('Please select a new date.');

    $.post('manage_booking.php', {action:'reschedule', id, new_date}, function(res){
        const data = JSON.parse(res);
        if(data.success){
            alert('Reschedule request sent!');
            closeRescheduleModal();
            closeBookingModal();
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    });
});
</script>




</body>
</html>
