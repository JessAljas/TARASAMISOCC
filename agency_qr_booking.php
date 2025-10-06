<?php
session_start();
include 'db_connect.php';

// Only allow admin/agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

// Handle AJAX actions
if(isset($_POST['action'], $_POST['id'])){
    $id = intval($_POST['id']);
    if($_POST['action'] === 'approve'){
        $stmt = $conn->prepare("UPDATE pay_via_qr SET status='approved' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
        exit;
    }
    elseif($_POST['action'] === 'delete'){
        $stmt = $conn->prepare("DELETE FROM pay_via_qr WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
        exit;
    }
elseif($_POST['action'] === 'completed'){
    $conn->begin_transaction();
    try {
        // 1. Fetch booking details first
        $stmt2 = $conn->prepare("SELECT * FROM pay_via_qr WHERE id=?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $booking = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();

        if(!$booking){
            throw new Exception("Booking not found.");
        }

        // 2. Update pay_via_qr status to 'completed'
        $stmt = $conn->prepare("UPDATE pay_via_qr SET status='completed' WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // 3. Compute service fee & total amount
        $fee_per_pax = 50; // fixed service fee per pax
        $service_fee = $fee_per_pax * $booking['pax'];
        $total_amount = (float)$booking['total'] + $service_fee;

        // 4. Insert into completed_booking
        $stmt3 = $conn->prepare("INSERT INTO completed_booking 
            (booking_id, package_id, tourist_id, pax, transaction_ref, mode_of_payment, status, service_fee, total_amount, checkout_url, approved_by, dateadded) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

        $booking_id = $booking['id'];
        $package_id = $booking['package_id'];
        $tourist_id = $booking['tourist_id'];
        $pax = $booking['pax'];
        $transaction_ref = $booking['reference_number'];
        $mode_of_payment = $booking['mode_of_payment'] ?? 'GCash QR';
        $status = 'completed';
        $checkout_url = '';
        $approved_by = 'agency'; // fixed string

        $stmt3->bind_param(
            "iiiisssddss",
            $booking_id,
            $package_id,
            $tourist_id,
            $pax,
            $transaction_ref,
            $mode_of_payment,
            $status,
            $service_fee,
            $total_amount,
            $checkout_url,
            $approved_by
        );

        if(!$stmt3->execute()){
            throw new Exception("Insert into completed_booking failed: ".$stmt3->error);
        }

        $stmt3->close();
        $conn->commit();
        echo json_encode(['success'=>true]);

    } catch(Exception $e){
        $conn->rollback();
        echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
    }
    exit;
}


    elseif($_POST['action'] === 'reschedule' && isset($_POST['new_date'])){
        $new_date = $_POST['new_date'];
        $stmt = $conn->prepare("UPDATE pay_via_qr SET booking_date=? WHERE id=?");
        $stmt->bind_param("si", $new_date, $id);
        $stmt->execute();
        echo json_encode(['success'=>true]);
        exit;
    }
}

// Fetch all payments
$stmt = $conn->prepare("
    SELECT q.*, t.fullname AS tourist_name, p.title AS package_name, p.price
    FROM pay_via_qr q
    JOIN tourists t ON q.tourist_id = t.id
    JOIN packages p ON q.package_id = p.id
    ORDER BY q.booking_date DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Manage Bookings - Agency</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
.status-badge { padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 500; text-transform: capitalize; font-size: 0.875rem; }
.status-completed { background-color: #22c55e; color: white; }
.status-approved { background-color: #2563eb; color: white; }
.status-pending { background-color: #eab308; color: white; }
.status-cancelled { background-color: #ef4444; color: white; }
.modal { display: none; position: fixed; z-index: 100; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
.modal-content { background-color: #fff; margin: 10% auto; padding: 1rem; border-radius: 0.5rem; max-width: 600px; position: relative; }
.close { position: absolute; top: 0.5rem; right: 1rem; font-size: 1.5rem; cursor: pointer; }
</style>
<script>
// Filter table
function filterTable() {
    let status = document.getElementById("statusFilter").value.toLowerCase();
    let date = document.getElementById("dateFilter").value;
    let search = document.getElementById("searchInput").value.toLowerCase();

    $("#bookingsTable tbody tr").each(function(){
        let rowStatus = $(this).data("status").toLowerCase();
        let rowDate = $(this).data("date");
        let rowText = $(this).text().toLowerCase();

        let show = true;
        if(status && rowStatus !== status) show = false;
        if(date && rowDate !== date) show = false;
        if(search && !rowText.includes(search)) show = false;
        $(this).toggle(show);
    });
}

// AJAX Approve/Delete/Completed with confirmation
function bookingAction(action, id, row){
    let message = '';
    if(action==='approve') message='Approve this booking?';
    else if(action==='delete') message='Delete this booking?';
    else if(action==='completed') message='Mark this booking as completed?';

    if(confirm(message)){
        $.post("", {action: action, id: id}, function(data){
            if(data.success){
                if(action==='delete') row.remove();
                else if(action==='completed'){
                    row.find(".status-badge").removeClass("status-pending status-approved status-cancelled").addClass("status-completed").text("Completed");
                    row.find(".approve-btn, .bg-green-600, .bg-yellow-500").remove();
                }
                else if(action==='approve'){
                    row.find(".status-badge").removeClass("status-pending").addClass("status-approved").text("Approved");
                    row.find(".approve-btn").remove();
                }
            }
        }, "json");
    }
}

// Payment proof modal
function showProof(src){
    const modal = document.getElementById('proofModal');
    const img = document.getElementById('proofModalImg');
    img.src = src;
    modal.classList.remove('hidden');
}
function closeModal(){ document.getElementById('proofModal').classList.add('hidden'); }

// Reschedule modal functions
function openRescheduleModal(id, currentDate){
    document.getElementById('rescheduleBookingId').value = id;
    document.getElementById('rescheduleDate').value = currentDate;
    document.getElementById('rescheduleModal').style.display = 'block';
}
function closeRescheduleModal(){ document.getElementById('rescheduleModal').style.display = 'none'; }

$(document).ready(function(){
    $('#rescheduleForm').on('submit', function(e){
        e.preventDefault();
        let id = $('#rescheduleBookingId').val();
        let newDate = $('#rescheduleDate').val();

        if(confirm('Change booking date to '+newDate+'?')){
            $.post("", {action:'reschedule', id:id, new_date:newDate}, function(data){
                if(data.success){
                    alert('Booking date updated!');
                    $('tr[data-id="'+id+'"] td:nth-child(4)').text(new Date(newDate).toLocaleDateString('en-US', {month:'long', day:'numeric', year:'numeric'}));
                    closeRescheduleModal();
                }
            }, 'json');
        }
    });
});
</script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 font-[Poppins]">

<!-- Sidebar fixed -->
<div class="fixed top-0 left-0 h-screen w-64 bg-white shadow-md overflow-y-auto z-20">
    <?php include 'sidebar.php'; ?>
</div>

<!-- Main Content -->
<div class="ml-64 p-6">
    <h1 class="text-2xl font-bold mb-4">All Bookings - Pay via QR</h1>

  <!-- Filters & Search -->
<div class="flex justify-between items-center gap-2 mb-4">
    <div class="flex gap-2">
        <select id="statusFilter" onchange="filterTable()" class="px-3 py-2 border rounded">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>

        <input type="date" id="dateFilter" onchange="filterTable()" class="px-3 py-2 border rounded">
    </div>

    <div class="flex items-center gap-2">
    <label for="searchInput" class="text-sm font-medium text-gray-700">Search:</label>
    <input type="text" id="searchInput" placeholder="Search..." class="px-3 py-2 border rounded w-48" onkeyup="filterTable()">
</div>

</div>


    <!-- Bookings nga table -->
    <div class="bg-white shadow rounded-lg p-4 overflow-x-auto">
    <table id="bookingsTable" class="min-w-full text-sm border-collapse border border-gray-300">
        <thead class="bg-green-500 text-white">
            <tr>
                <th class="px-4 py-2 border border-gray-300">#</th>
                <th class="px-4 py-2 border border-gray-300">Tourist</th>
                <th class="px-4 py-2 border border-gray-300">Package</th>
                <th class="px-4 py-2 border border-gray-300">Booking Date</th>
                <th class="px-4 py-2 border border-gray-300">Pax</th>
                <th class="px-4 py-2 border border-gray-300">Total</th>
                <th class="px-4 py-2 border border-gray-300">Reference #</th>
                <th class="px-4 py-2 border border-gray-300">Payment Proof</th>
                <th class="px-4 py-2 border border-gray-300">Status</th>
                <th class="px-4 py-2 border border-gray-300">Actions</th>
            </tr>
            </thead>
            <tbody>
                <?php if($result->num_rows>0): $i=1; while($row = $result->fetch_assoc()):
                    $status = strtolower($row['status'] ?: 'pending');
                ?>
                <tr data-id="<?= $row['id'] ?>" data-status="<?= $status ?>" data-date="<?= $row['booking_date'] ?>">
                    <td class="px-4 py-2 border"><?= $i++ ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['tourist_name']) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['package_name']) ?></td>
                    <td class="px-4 py-2 border"><?= date("F d, Y", strtotime($row['booking_date'])) ?></td>
                    <td class="px-4 py-2 border"><?= $row['pax'] ?> pax</td>
                    <td class="px-4 py-2 border">₱<?= number_format($row['total'] ?: $row['price'] * $row['pax'], 2) ?></td>
                    <td class="px-4 py-2 border"><?= htmlspecialchars($row['reference_number']) ?></td>
                    <td class="px-4 py-2 border">
                        <?php $img = !empty($row['proof_image']) ? $row['proof_image'] : $row['payment_proof']; ?>
                        <?php if(!empty($img)): ?>
                        <button onclick="showProof('<?= htmlspecialchars($img) ?>')" class="text-blue-600 hover:text-blue-400 flex items-center">
                            <i class="fas fa-file-alt mr-1"></i> View
                        </button>
                        <?php else: ?>
                        <span class="text-gray-400">No proof</span>
                        <?php endif; ?>
                    </td>
                  <!-- Status Column -->
<td class="px-4 py-2 border text-center">
    <span class="status-badge status-<?= $status ?>">
        <?= ucfirst($status) ?>
    </span>
</td>

<!-- Actions Column -->
<td class="px-4 py-2 border border-gray-300 sticky right-0 bg-white z-10">
    <div class="grid grid-cols-2 gap-2">
        <?php if($status!=='approved' && $status!=='completed'): ?>
        <button class="approve-btn bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-400"
            onclick="bookingAction('approve', <?= $row['id'] ?>, $(this).closest('tr'))">
            <i class="fas fa-check"></i> 
        </button>
        <?php endif; ?>

        <?php if($status!=='completed'): ?>
        <button class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-500"
            onclick="bookingAction('completed', <?= $row['id'] ?>, $(this).closest('tr'))">
            <i class="fas fa-clipboard-check"></i> 
        </button>

        <button class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-400"
            onclick="openRescheduleModal(<?= $row['id'] ?>, '<?= $row['booking_date'] ?>')">
            <i class="fas fa-calendar-alt"></i> 
        </button>

        <button class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-400"
            onclick="bookingAction('delete', <?= $row['id'] ?>, $(this).closest('tr'))">
            <i class="fas fa-trash"></i>
        </button>
        <?php endif; ?>
    </div>
</td>


                </tr>
                <?php endwhile; else: ?>
                <tr>
                    <td colspan="10" class="text-center py-4 text-gray-500">No bookings found.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Payment proof nga modal -->
<div id="proofModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
    <div class="relative">
        <button onclick="closeModal()" class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-gray-300">&times;</button>
        <img id="proofModalImg" src="" alt="Payment Proof" class="max-w-lg max-h-[90vh] w-auto h-auto rounded shadow-lg mx-auto">
    </div>
</div>

<!-- Reschedule nga Modal -->
<div id="rescheduleModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeRescheduleModal()">&times;</span>
        <h2 class="text-xl font-bold mb-4">Reschedule Booking</h2>
        <form id="rescheduleForm">
            <input type="hidden" name="id" id="rescheduleBookingId">
            <label class="block mb-2">New Booking Date:</label>
            <input type="date" name="new_date" id="rescheduleDate" class="border px-3 py-2 rounded w-full mb-4" required>
            <button type="submit" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-400">Save</button>
        </form>
    </div>
</div>

</body>
</html>
