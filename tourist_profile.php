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

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
        $upload_dir = __DIR__ . '/uploads/';
        $web_upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

        $ext = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
        $filename = 'user_' . $tourist_id . '_' . time() . '.' . $ext; // unique filename
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
while ($row = $all_bookings->fetch_assoc()) {
    $row['total'] = $row['total'] ?: ($row['package_price'] * ($row['pax'] ?? 1));
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
<div class="shadow-lg rounded-2xl p-8 text-center mb-8">
 <img id="profileDisplay" 
     src="<?= htmlspecialchars($display_image) ?>?t=<?= time() ?>" 
     alt="Profile Image" 
     class="w-32 h-32 rounded-full border-4 border-yellow-400 mx-auto object-cover">

    <h2 class="mt-4 text-2xl font-semibold text-gray-800">
        <?= htmlspecialchars($tourist['fullname']) ?>
    </h2>
    <p class="text-gray-600"><?= htmlspecialchars($tourist['email']) ?></p>
    <p class="text-gray-600"><?= htmlspecialchars($tourist['phone_number']) ?></p>
    <button onclick="openEditModal()" 
            class="mt-4 px-6 py-2 rounded-full bg-yellow-500 hover:bg-green-600 text-white font-medium shadow">
        Edit Profile
    </button>
</div>

<!-- Booking Tabs -->
<div class="flex justify-center mb-6">
    <button id="tab-current" class="px-6 py-2 bg-green-600 text-white font-medium">Current / Pending</button>
    <button id="tab-history" class="px-6 py-2 bg-gray-300 text-gray-700 font-medium">History</button>
</div>

<!-- Current Bookings -->
<div id="current-section">
    <div class="overflow-x-auto bg-white rounded-xl shadow-md">
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
<div id="history-section" class="hidden">
    <div class="overflow-x-auto bg-white rounded-xl shadow-md">
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
                        <button onclick="showProof('<?= htmlspecialchars($proof_path) ?>')" 
                                class="text-blue-600 hover:underline text-sm font-semibold">
                            View Proof
                        </button>
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
</body>
</html>
