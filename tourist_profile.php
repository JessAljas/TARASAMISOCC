<?php
session_start();
include 'config/db_connect.php';

// Only allow tourists
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourist') {
    header("Location: login.php");
    exit;
}

$tourist_id = $_SESSION['user']['id'];
$success = $error = '';

// Default profile image
$default_image = 'uploads/default-profile.png';

// Fetch current tourist info
$stmt = $conn->prepare("SELECT * FROM tourists WHERE id = ?");
$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$tourist = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Initial profile image
$profile_image_path = !empty($tourist['profile_image']) ? $tourist['profile_image'] : $default_image;

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone_number'] ?? '';

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = __DIR__ . '/uploads/';
        $web_upload_dir = 'uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $tourist_id . '_' . time() . '.' . $ext;
        $target_file = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target_file)) {
            if (!empty($tourist['profile_image']) && $tourist['profile_image'] !== $default_image && file_exists(__DIR__ . '/' . $tourist['profile_image'])) {
                unlink(__DIR__ . '/' . $tourist['profile_image']);
            }
            $profile_image_path = $web_upload_dir . $filename;
        } else {
            $error = "Failed to upload profile image.";
        }
    }

    if (!$error) {
        $stmt = $conn->prepare("UPDATE tourists SET fullname=?, email=?, phone_number=?, profile_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $email, $phone, $profile_image_path, $tourist_id);
        if ($stmt->execute()) {
            $success = "Profile updated successfully.";
            $_SESSION['user']['fullname'] = $fullname;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['phone_number'] = $phone;
            $_SESSION['user']['profile_image'] = $profile_image_path;
            $tourist['fullname'] = $fullname;
            $tourist['email'] = $email;
            $tourist['phone_number'] = $phone;
            $tourist['profile_image'] = $profile_image_path;
        } else {
            $error = "Failed to update profile in database.";
        }
        $stmt->close();
    }
}

// Final display image
$display_image = $_SESSION['user']['profile_image'] ?? $profile_image_path ?? $default_image;

// Fetch bookings
$sql = "SELECT q.*, p.title AS package_title, p.price AS package_price
        FROM pay_via_qr q
        JOIN packages p ON q.package_id = p.id
        WHERE q.tourist_id = ?
        ORDER BY q.booking_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $tourist_id);
$stmt->execute();
$all_bookings = $stmt->get_result();
$stmt->close();

$current_bookings = [];
$history_bookings = [];
$reschedule_requests = [];

while ($row = $all_bookings->fetch_assoc()) {
    $row['total'] = $row['total'] ?: ($row['package_price'] * ($row['pax'] ?? 1));

    if (!empty($row['reschedule_date']) && strtolower($row['status']) === 'reschedule_requested') {
        $reschedule_requests[] = $row;
    }

    if (in_array(strtolower($row['status']), ['completed', 'rejected'])) {
        $history_bookings[] = $row;
    } else {
        $current_bookings[] = $row;
    }
}


// Function to get proof path
function getProofPath($filename) {
    $path = 'uploads/gcash/' . basename($filename);
    return (!empty($filename) && file_exists($path)) ? $path : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>My Profile</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-[Poppins]">

<?php include 'config/include/header.php'; ?>

<div class="container mx-auto px-4 py-8 flex-1">

    <?php if($success): ?>
        <div class="bg-green-100 text-green-800 p-4 mb-4 rounded-lg text-center"><?= $success ?></div>
    <?php elseif($error): ?>
        <div class="bg-red-100 text-red-800 p-4 mb-4 rounded-lg text-center"><?= $error ?></div>
    <?php endif; ?>

<!-- Profile Card -->
<div class="shadow-lg rounded-2xl p-8 text-center mb-8 bg-green-50 max-w-2xl mx-auto">
 <img id="profileDisplay" 
     src="<?= htmlspecialchars($display_image) ?>?t=<?= time() ?>" 
     alt="Profile Image" 
     class="w-32 h-32 rounded-full border-4 border-green-500 mx-auto object-cover">

    <h2 class="mt-4 text-2xl font-semibold text-gray-800">
        <?= htmlspecialchars($tourist['fullname']) ?>
    </h2>
    <p class="text-gray-600"><?= htmlspecialchars($tourist['email']) ?></p>
    <p class="text-gray-600"><?= htmlspecialchars($tourist['phone_number']) ?></p>
   <div class="flex justify-center mt-4">
    <button onclick="openEditModal()" 
        class="px-6 py-2 rounded-full bg-yellow-500 hover:bg-green-600 text-white font-medium shadow flex items-center justify-center space-x-2">
        <i class="fas fa-pencil-alt"></i>
        <span>Edit Profile</span>
    </button>
</div>
</div>

<?php if(!empty($reschedule_requests)): ?>
    <div class="max-w-4xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <?php foreach($reschedule_requests as $row): ?>
                <div class="flex flex-col justify-between p-3 bg-green-50 rounded-lg border border-green-200 max-w-sm mx-auto shadow-sm">
                    
                    <!-- Icon and Booking Details -->
                    <div class="mb-2 flex items-center space-x-2">
                        <i class="fas fa-calendar-alt text-yellow-500 text-base"></i>
                        <span class="font-semibold text-gray-700 text-sm">Reschedule Request</span>
                    </div>
                    
                    <div class="mb-2 space-y-1 text-sm">
                        <p><strong>Package:</strong> <?= htmlspecialchars($row['package_title'] ?? 'N/A') ?></p>
                        <p><strong>Original Date:</strong> <?= htmlspecialchars($row['booking_date'] ?? 'N/A') ?></p>
                        <p><strong>Proposed New Date:</strong> <?= htmlspecialchars($row['reschedule_date'] ?? 'N/A') ?></p>
                        <p><strong>Reason:</strong> <?= htmlspecialchars($row['reason'] ?? 'Weather Condition / Safety Policy') ?></p>
                        <p class="text-xs text-gray-600 mt-1">
                            If you don’t prefer this schedule, you can 
                            <a href="<?= $base ?>contact.php" class="text-blue-600 underline hover:text-blue-800">
                                contact us or send a message
                            </a>. Thank you for understanding, and we wish you a wonderful tour!
                        </p>
                    </div>
                    
                    <!-- Accept Button -->
                    <div class="flex justify-end">
                        <button onclick="respondReschedule(<?= $row['id'] ?>,'approve')" 
                                class="px-3 py-1.5 bg-red-600 text-white rounded hover:bg-green-700 text-xs">
                            Okay
                        </button>
                    </div>

                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php else: ?>
    <p class="text-gray-500 text-center">No reschedule requests at the moment.</p>
<?php endif; ?>


<!-- Booking Tabs -->
<div class="flex justify-center mb-6">
    <button id="tab-current" class="px-6 py-2 bg-green-600 text-white font-medium">Current</button>
    <button id="tab-history" class="px-6 py-2 bg-gray-300 text-gray-700 font-medium">History</button>
</div>

<!-- Current Bookings -->
<div id="current-section" class="flex justify-center">
    <div class="overflow-x-auto bg-white rounded-xl shadow-md max-w-5xl w-full mx-4">
        <table class="min-w-full text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Package</th>
                    <th class="px-4 py-2">Tour Date</th>
                    <th class="px-4 py-2">Pax</th>
                    <th class="px-4 py-2">Total</th>
                    <th class="px-4 py-2">Status / Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php if($current_bookings): ?>
                    <?php foreach($current_bookings as $row): ?>
                    <tr class="border-b">
                        <td class="px-4 py-2"><?= htmlspecialchars($row['package_title']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($row['booking_date']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($row['pax']) ?></td>
                        <td class="px-4 py-2">₱ <?= number_format($row['total'], 2) ?></td>
                        <td class="px-4 py-2 flex items-center space-x-2">
                            <?php 
                                $statusColor = match(strtolower($row['status'])) {
                                    'pending' => 'bg-yellow-200 text-yellow-800',
                                    'approved','completed' => 'bg-green-200 text-green-800',
                                    'rejected' => 'bg-red-200 text-red-800',
                                    default => 'bg-gray-200 text-gray-800'
                                };
                            ?>
                            <span class="px-2 py-1 rounded-full text-sm font-medium <?= $statusColor ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                            <?php 
                                $proof_path = getProofPath($row['proof_image']);
                            ?>
                            <?php if($proof_path): ?>
                                <button onclick="showProof('<?= htmlspecialchars($proof_path) ?>')" class="text-blue-600 hover:underline text-sm font-semibold">View Proof</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-6 text-gray-500">No current bookings.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- History Bookings -->
<div id="history-section" class="hidden flex justify-center">
    <div class="overflow-x-auto bg-white rounded-xl shadow-md max-w-5xl w-full mx-4">
        <table class="min-w-full text-left">
            <thead class="bg-gray-100">
                <tr>
                    <th class="px-4 py-2">Package</th>
                    <th class="px-4 py-2">Tour Date</th>
                    <th class="px-4 py-2">Pax</th>
                    <th class="px-4 py-2">Total</th>
                    <th class="px-4 py-2">Status / Proof</th>
                </tr>
            </thead>
            <tbody>
                <?php if($history_bookings): ?>
                    <?php foreach($history_bookings as $row): ?>
                    <tr class="border-b">
                        <td class="px-4 py-2"><?= htmlspecialchars($row['package_title']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($row['booking_date']) ?></td>
                        <td class="px-4 py-2"><?= htmlspecialchars($row['pax']) ?></td>
                        <td class="px-4 py-2">₱ <?= number_format($row['total'], 2) ?></td>
                        <td class="px-4 py-2 flex items-center space-x-2">
                            <?php 
                                $statusColor = match(strtolower($row['status'])) {
                                    'pending' => 'bg-yellow-200 text-yellow-800',
                                    'approved','completed' => 'bg-green-200 text-green-800',
                                    'rejected' => 'bg-red-200 text-red-800',
                                    default => 'bg-gray-200 text-gray-800'
                                };
                            ?>
                            <span class="px-2 py-1 rounded-full text-sm font-medium <?= $statusColor ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                            <?php 
                                $proof_path = getProofPath($row['proof_image']);
                            ?>
                            <?php if($proof_path): ?>
                                <button onclick="showProof('<?= htmlspecialchars($proof_path) ?>')" class="text-blue-600 hover:underline text-sm font-semibold">View Proof</button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" class="text-center py-6 text-gray-500">No booking history.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>
<!-- Proof Modal -->
<div id="proofModal" class="hidden fixed inset-0 bg-black bg-opacity-50 items-center justify-center z-50">
    <div class="bg-white p-2 rounded-lg relative max-w-[90vw] max-h-[90vh] flex items-center justify-center">
        <button 
            onclick="closeProof()" 
            class="absolute top-2 right-2 text-white hover:text-black-600 font-bold text-2xl"
            style="text-shadow: 0 0 3px black;">&times;</button>
        <img id="proofImg" src="" alt="Proof Image" class="max-w-[95vw] max-h-[90vh] object-contain rounded-lg">
    </div>
</div>

<!-- Edit Profile Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-2xl shadow-lg max-w-lg w-full relative">
      <button onclick="closeEditModal()" class="absolute top-2 right-2 text-red-600 font-bold text-lg">&times;</button>
      <h3 class="text-lg font-semibold mb-4 text-gray-700">Edit Profile</h3>
      <form method="POST" enctype="multipart/form-data" class="space-y-4">
          <input type="hidden" name="update_profile" value="1">
          <div>
              <label class="block text-sm font-medium">Full Name</label>
              <input type="text" name="fullname" value="<?= htmlspecialchars($tourist['fullname']) ?>" class="w-full border rounded-lg px-3 py-2" required>
          </div>
          <div>
              <label class="block text-sm font-medium">Email</label>
              <input type="email" name="email" value="<?= htmlspecialchars($tourist['email']) ?>" class="w-full border rounded-lg px-3 py-2" required>
          </div>
          <div>
              <label class="block text-sm font-medium">Phone</label>
              <input type="text" name="phone_number" value="<?= htmlspecialchars($tourist['phone_number']) ?>" class="w-full border rounded-lg px-3 py-2" required>
          </div>
          <div>
              <label class="block text-sm font-medium">Profile Image</label>
              <input type="file" id="profileInput" name="profile_image" accept="image/*" class="w-full border rounded-lg px-3 py-2">
          </div>
          <button type="submit" class="bg-green-600 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg">Save Changes</button>
      </form>
  </div>
</div>

<?php include 'config/include/footer.php'; ?>
<script src="js/prof.js"></script>
<script>
function respondReschedule(bookingId, action){
    fetch('reschedule_response.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:`id=${bookingId}&action=${action}`
    }).then(res=>res.json()).then(data=>{
        if(data.success){
            alert('Your response has been recorded.');
            location.reload();
        } else {
            alert('Error: ' + data.error);
        }
    }).catch(err=>alert('Error: ' + err));
}
</script>
</body>
</html>
