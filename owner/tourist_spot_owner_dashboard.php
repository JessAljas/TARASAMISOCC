<?php
session_start();
include '../config/db_connect.php';

// Redirect if not logged in or role mismatch
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: tourist_spot_login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch owner info
$stmt = $conn->prepare("SELECT * FROM spot_owners WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_img = !empty($owner['profile_image']) ? '../uploads/' . $owner['profile_image'] : '../img/default_profile.png';

// Handle profile update
$profile_update_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $profile_image = $owner['profile_image'];

    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $new_image = 'profile_' . time() . '.' . $ext;
        if (!is_dir('../uploads')) mkdir('../uploads', 0777, true);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], '../uploads/' . $new_image);
        if (!empty($owner['profile_image']) && file_exists('../uploads/' . $owner['profile_image'])) {
            unlink('../uploads/' . $owner['profile_image']);
        }
        $profile_image = $new_image;
    }

    $stmt = $conn->prepare("UPDATE spot_owners SET fullname=?, email=?, phone_number=?, profile_image=? WHERE id=?");
    $stmt->bind_param("ssssi", $fullname, $email, $phone, $profile_image, $user_id);
    if ($stmt->execute()) {
        $profile_update_msg = "Profile updated successfully!";
        $owner['fullname'] = $fullname;
        $owner['email'] = $email;
        $owner['phone_number'] = $phone;
        $owner['profile_image'] = $profile_image;
        $_SESSION['user']['fullname'] = $fullname;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['profile_image'] = $profile_image;
    } else {
        $profile_update_msg = "Failed to update profile.";
    }
    $stmt->close();
}

// Handle sending inquiry
$inquiry_msg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    $receiver_role = $_POST['receiver_role'] ?? '';
    $allowed_roles = ['tourism_officer', 'agency'];

    if ($subject && $message_text && in_array($receiver_role, $allowed_roles, true)) {
        $stmt = $conn->prepare("INSERT INTO inquiries (sender_id, sender_role, receiver_role, subject, message, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())");
        $role = 'spot_owner';
        $stmt->bind_param("issss", $user_id, $role, $receiver_role, $subject, $message_text);

        if ($stmt->execute()) {
            $inquiry_msg = "Message sent successfully to " . htmlspecialchars($receiver_role) . "!";
        } else {
            $inquiry_msg = "Failed to send message: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $inquiry_msg = "Please fill all fields and select a valid recipient.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourist Spot Owner Dashboard</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-green-100 min-h-screen flex flex-col font-[Poppins]">

<!-- Header -->
<header class="bg-green-500 text-white shadow p-6 flex justify-between items-center">
    <h1 class="text-2xl font-bold">Tourist Spot Owner Dashboard</h1>
    <div class="relative">
        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-2 bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-user-circle text-2xl"></i>
            <span class="font-semibold"><?= htmlspecialchars($owner['fullname']) ?></span>
            <i class="fas fa-caret-down"></i>
        </button>
        <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg overflow-hidden hidden z-50">
            <a href="javascript:void(0);" onclick="openModal('editModal')" class="block px-5 py-3 font-semibold text-green-700 hover:bg-green-100 transition">
                <i class="fas fa-user-edit mr-2 text-green-600"></i> Profile
            </a>
            <a href="javascript:void(0);" onclick="openModal('messageModal')" class="block px-5 py-3 font-semibold text-green-700 hover:bg-green-100 transition">
                <i class="fas fa-envelope mr-2 text-green-600"></i> Send Inquiry
            </a>
        <button onclick="openModal('logoutModal')" class="w-full text-left px-5 py-3 font-semibold text-red-600 hover:bg-red-100 transition flex items-center">
            <i class="fas fa-sign-out-alt mr-2 text-red-500"></i> Logout
        </button>
        </div>
    </div>
</header>
<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md text-center">
        <h2 class="text-lg font-semibold mb-4 text-red-600">Confirm Logout</h2>
        <p class="mb-6">Are you sure you want to logout?</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeModal('logoutModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <a href="../logout.php" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">Logout</a>
        </div>
    </div>
</div>

<!-- Welcome Card -->
<div class="flex flex-1 justify-center items-center">
    <div class="text-center bg-white p-16 rounded-xl shadow-lg m-6">
        <h1 class="text-4xl md:text-5xl font-bold text-green-600 mb-4">
            Welcome to Your Dashboard
        </h1>
        <p class="text-xl text-gray-700 mb-6">
            Hello, <?= htmlspecialchars($owner['fullname']) ?>!
        </p>
        <a href="add.php" class="bg-green-600 text-white px-8 py-3 rounded-lg hover:bg-green-700 transition">
         <i class="fas fa-landmark"></i> Manage Tourist Spots
        </a>
    </div>
</div>

<!-- Messages -->
<div id="modalMessageContainer" class="w-full flex justify-center mb-4 absolute top-4 left-0 z-50">
    <?php if($profile_update_msg): ?>
        <p class="bg-green-100 text-green-700 px-4 py-2 rounded shadow"><?= htmlspecialchars($profile_update_msg) ?></p>
    <?php endif; ?>
    <?php if($inquiry_msg): ?>
        <p class="bg-green-100 text-green-700 px-4 py-2 rounded shadow"><?= htmlspecialchars($inquiry_msg) ?></p>
    <?php endif; ?>
</div>

<!-- Send Inquiry Modal -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex justify-center items-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md">
        <h2 class="text-lg font-semibold mb-4 text-green-600">Send Inquiry</h2>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="send_inquiry" value="1">
            <input type="hidden" name="receiver_role" value="agency">
            
            <div>
                <label>Email:</label>
                <input type="text" name="subject" required class="w-full p-2 border rounded">
            </div>
            <div>
                <label>Message</label>
                <textarea name="message" required class="w-full p-2 border rounded"></textarea>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeModal('messageModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded flex items-center gap-2">
                    <i class="fa-solid fa-paper-plane"></i> Send
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}
function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}
</script>

</body>
</html>
