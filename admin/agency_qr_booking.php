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

        // Get booking
        $stmtBooking = $conn->prepare("SELECT tourist_id, booking_date FROM pay_via_qr WHERE id=?");
        $stmtBooking->bind_param("i", $id);
        $stmtBooking->execute();
        $bookingData = $stmtBooking->get_result()->fetch_assoc();
        $stmtBooking->close();

        if ($bookingData) {
            $tourist_id = $bookingData['tourist_id'];
            $old_date = $bookingData['booking_date'];

            // Update booking with new date
            $stmt = $conn->prepare("UPDATE pay_via_qr SET reschedule_date=?, status='reschedule_requested' WHERE id=?");
            $stmt->bind_param("si", $new_date, $id);
            $stmt->execute();

            // Notify tourist
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
    <meta charset="UTF-8" />
    <title>Manage Bookings - Agency</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css">
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
    </head>
    <body class="bg-gray-100 font-[Poppins]">

    <div class="fixed top-0 left-0 h-screen w-64 bg-white shadow-md overflow-y-auto z-20">
        <?php include 'sidebar.php'; ?>
    </div>

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
                        <th class="px-4 py-2 border">#</th>
                        <th class="px-4 py-2 border">Tourist</th>
                        <th class="px-4 py-2 border">Package</th>
                        <th class="px-4 py-2 border">Booking Date</th>
                        <th class="px-4 py-2 border">Pax</th>
                        <th class="px-4 py-2 border">Total</th>
                        <th class="px-4 py-2 border">Reference #</th>
                        <th class="px-4 py-2 border">Payment Proof</th>
                        <th class="px-4 py-2 border">Status</th>
                        <th class="px-4 py-2 border">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): $i = 1; while ($row = $result->fetch_assoc()):
                        $status = strtolower($row['status'] ?: 'pending'); ?>
                        <tr data-id="<?= $row['id'] ?>" data-status="<?= $status ?>" data-date="<?= $row['booking_date'] ?>">
                            <td class="px-4 py-2 border"><?= $i++ ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['tourist_name']) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['package_name']) ?></td>
                            <td class="px-4 py-2 border"><?= date("F d, Y", strtotime($row['booking_date'])) ?></td>
                            <td class="px-4 py-2 border"><?= $row['pax'] ?> pax</td>
                            <td class="px-4 py-2 border">₱<?= number_format($row['total'] ?: $row['price'] * $row['pax'], 2) ?></td>
                            <td class="px-4 py-2 border"><?= htmlspecialchars($row['reference_number']) ?></td>
                            <td class="px-4 py-2 border">
                                <?php
                                $img = !empty($row['proof_image']) ? '../' . $row['proof_image'] : '../' . $row['payment_proof'];
                                if (!empty($img)): ?>
                                    <button onclick="showProof('<?= htmlspecialchars($img) ?>')" class="text-blue-600 hover:text-blue-400 flex items-center">
                                        <i class="fas fa-file-alt mr-1"></i> View
                                    </button>
                                <?php else: ?>
                                    <span class="text-gray-400">No proof</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-2 border text-center">
                                <span class="status-badge status-<?= $status ?>"><?= ucfirst($status) ?></span>
                            </td>
                            <td class="px-4 py-2 border sticky right-0 bg-white z-10">
                                <div class="grid grid-cols-2 gap-2">
                                    <?php if ($status !== 'approved' && $status !== 'completed'): ?>
                                        <button class="approve-btn bg-blue-500 text-white px-2 py-1 rounded hover:bg-blue-400"
                                            onclick="bookingAction('approve', <?= $row['id'] ?>, this)" title="Approve Booking">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($status !== 'completed'): ?>
                                        <button class="bg-green-600 text-white px-2 py-1 rounded hover:bg-green-500"
                                            onclick="bookingAction('completed', <?= $row['id'] ?>, this)" title="Mark as Completed">
                                            <i class="fas fa-clipboard-check"></i>
                                        </button>
                                        <button class="bg-yellow-500 text-white px-2 py-1 rounded hover:bg-yellow-400"
                                            onclick="openRescheduleModal(<?= $row['id'] ?>, '<?= $row['booking_date'] ?>')" title="Reschedule Booking">
                                            <i class="fas fa-calendar-alt"></i>
                                        </button>
                                        <button class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-400"
                                            onclick="openDeleteModal(<?= $row['id'] ?>, this)" title="Delete Booking">
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg w-96 max-w-full p-6 relative text-center">
            <button type="button" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl font-bold" onclick="closeDeleteModal()">&times;</button>
            <h2 class="text-lg font-semibold mb-4">Are you sure you want to delete this booking?</h2>
            <div class="flex justify-center gap-4 mt-4">
                <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Yes, Delete</button>
            </div>
        </div>
    </div>

    <!-- Payment Proof Modal -->
    <div id="proofModal" class="hidden fixed inset-0 bg-black/60 flex items-center justify-center z-50">
        <div class="relative">
            <button onclick="closeModal()" class="absolute top-2 right-2 text-white text-2xl font-bold hover:text-gray-300">&times;</button>
            <img id="proofModalImg" src="" alt="Payment Proof" class="max-w-lg max-h-[90vh] w-auto h-auto rounded shadow-lg mx-auto" />
        </div>
    </div>

    <!-- Reschedule Modal -->
    <div id="rescheduleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeRescheduleModal()">&times;</span>
            <h2 class="text-xl font-semibold mb-4">Reschedule Booking</h2>

            <!-- Success message -->
            <div id="rescheduleSuccessMsg" class="hidden mb-4 p-2 bg-green-100 text-green-800 rounded text-sm text-center">
                Request sent successfully!
            </div>

            <form id="rescheduleForm" class="space-y-4">
                <input type="hidden" id="rescheduleBookingId" name="id" value="" />
                <label for="rescheduleDate" class="block font-medium">New Booking Date</label>
                <input type="date" id="rescheduleDate" name="new_date" required class="border px-3 py-2 rounded w-full" />
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="closeRescheduleModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-blue-700">Save</button>
                </div>
            </form>
        </div>
    </div>

    <script src=isset/manage_booking.js></script>
    </body>
    </html>
