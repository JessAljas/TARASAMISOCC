<?php 
session_start();
include '../config/db_connect.php';

// Check if logged in as spot owner
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user']['id'];

// Fetch all bookings with status Approved or Completed
$query = "
    SELECT 
        b.booking_date,
        b.pax,
        b.status
    FROM bookings b
    WHERE b.status IN ('Approved', 'Completed')
    ORDER BY b.booking_date ASC
";

$result = mysqli_query($conn, $query);
if (!$result) {
    die("Query failed: " . mysqli_error($conn));
}

// Counters
$total_visitors = 0;
$completed_bookings = 0;
$approved_bookings = 0;
$upcoming_visits = 0;

$today = date('Y-m-d');
$week_ahead = date('Y-m-d', strtotime('+7 days'));

$bookings_data = mysqli_fetch_all($result, MYSQLI_ASSOC);

foreach ($bookings_data as $row) {
    $total_visitors += (int)$row['pax'];
    if ($row['status'] === 'Completed') $completed_bookings++;
    if ($row['status'] === 'Approved') $approved_bookings++;
    if ($row['booking_date'] >= $today && $row['booking_date'] <= $week_ahead) $upcoming_visits++;
}

// Monthly visitors (no join needed since tourist_spot_id is removed)
$current_month = date('m');
$current_year = date('Y');

$monthly_visitors_query = "
    SELECT SUM(pax) AS total_visitors
    FROM bookings
    WHERE status IN ('Approved', 'Completed')
      AND MONTH(booking_date) = '$current_month'
      AND YEAR(booking_date) = '$current_year'
";

$monthly_result = mysqli_query($conn, $monthly_visitors_query);
$monthly_data = mysqli_fetch_assoc($monthly_result);
$total_visitors_this_month = (int)($monthly_data['total_visitors'] ?? 0);

// Reset result pointer for table usage
mysqli_data_seek($result, 0);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Visitor Schedule</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen font-[Poppins]">
    <?php include 'owner_header.php'; ?>

    <div class="flex flex-col items-center py-10 px-4">
        <h2 class="text-3xl font-semibold text-gray-800 mb-10">📅 Visitor Schedule</h2>

        <!-- SUMMARY BOXES -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 w-full max-w-6xl mb-10">
            <div class="bg-emerald-600 text-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                <i class="fa-solid fa-users text-5xl mb-3"></i>
                <span class="text-4xl font-bold"><?php echo $total_visitors_this_month; ?></span>
                <span class="text-sm uppercase tracking-wider mt-2">Visitors This Month</span>
            </div>
            <div class="bg-green-500 text-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                <i class="fa-solid fa-calendar-alt text-5xl mb-3"></i>
                <span class="text-4xl font-bold"><?php echo $upcoming_visits; ?></span>
                <span class="text-sm uppercase tracking-wider mt-2">Upcoming Visits</span>
            </div>
            <div class="bg-green-700 text-white rounded-xl shadow-md p-6 flex flex-col items-center justify-center">
                <i class="fa-solid fa-check-circle text-5xl mb-3"></i>
                <span class="text-4xl font-bold"><?php echo $completed_bookings; ?></span>
                <span class="text-sm uppercase tracking-wider mt-2">Completed Visits</span>
            </div>
        </div>

        <!-- FILTER SECTION -->
        <div class="bg-white p-6 shadow-md rounded-xl mb-10 w-full max-w-4xl flex flex-col sm:flex-row sm:items-end gap-4">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-600">Select Date Range:</label>
                <div class="flex gap-2 mt-2">
                    <input type="date" id="startDate" class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                    <input type="date" id="endDate" class="border border-gray-300 rounded-lg px-3 py-2 w-full focus:ring-2 focus:ring-emerald-500 focus:outline-none">
                </div>
            </div>
            <button onclick="filterTable()" class="bg-emerald-600 text-white px-6 py-2 rounded-lg hover:bg-emerald-700 transition duration-200">🔍 Filter</button>
        </div>

        <!-- TABLE SECTION -->
        <div class="w-full max-w-4xl bg-white shadow-lg rounded-xl overflow-hidden mb-6">
            <table class="min-w-full border-collapse text-center">
                <thead class="bg-emerald-600 text-white uppercase text-sm tracking-wider">
                    <tr>
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Visit Date</th>
                        <th class="py-3 px-4">No. of Guests</th>
                        <th class="py-3 px-4">Status</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700" id="scheduleTable">
                    <?php
                    if (count($bookings_data) > 0) {
                        $i = 1;
                        foreach ($bookings_data as $row) {
                            $status_color = match($row['status']) {
                                'Completed' => 'text-green-600 font-semibold',
                                'Approved' => 'text-yellow-600 font-semibold',
                                default => 'text-gray-600',
                            };
                            $booking_date_formatted = date('F d, Y', strtotime($row['booking_date']));
                            $booking_date_raw = $row['booking_date'];
                            echo "
                            <tr class='border-b hover:bg-gray-50 transition'>
                                <td class='py-3 px-4 font-medium text-gray-600'>{$i}</td>
                                <td class='py-3 px-4 text-emerald-600 font-semibold' data-date='{$booking_date_raw}'>{$booking_date_formatted}</td>
                                <td class='py-3 px-4 text-gray-800'>{$row['pax']}</td>
                                <td class='py-3 px-4 {$status_color}'>{$row['status']}</td>
                            </tr>";
                            $i++;
                        }
                    } else {
                        echo "<tr><td colspan='4' class='py-6 text-gray-500 italic'>No scheduled visitors found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

    <script>
        function filterTable() {
            const startDate = new Date(document.getElementById('startDate').value);
            const endDate = new Date(document.getElementById('endDate').value);
            const rows = document.querySelectorAll('#scheduleTable tr');

            rows.forEach(row => {
                const dateText = row.cells[1]?.getAttribute('data-date');
                if (!dateText) return;
                const rowDate = new Date(dateText);
                if ((!isNaN(startDate) && rowDate < startDate) || (!isNaN(endDate) && rowDate > endDate)) {
                    row.style.display = 'none';
                } else {
                    row.style.display = '';
                }
            });
        }
    </script>

</body>
</html>
