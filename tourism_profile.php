<?php
session_start();
include 'db_connect.php';

// Only anf tourism officers ray gi allow
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'tourism_officers') {
    header("Location: login.php");
    exit;
}

$userId = $_SESSION['user']['id'];

// Handle profile update via sa POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $profile_image = $_FILES['profile_image'] ?? null;

    $message = "";

    if ($fullname === "" || $email === "") {
        $message = "Full Name and Email are required.";
    } else {
        // Handle sa profile image nga gi upload
        $filename = $_SESSION['user']['profile_image'] ?? null;
        if ($profile_image && $profile_image['tmp_name']) {
            $ext = pathinfo($profile_image['name'], PATHINFO_EXTENSION);
            $filename = 'profile_'.$userId.'.'.$ext;
            move_uploaded_file($profile_image['tmp_name'], 'uploads/'.$filename);
        }

        $stmt = $conn->prepare("UPDATE tourism_officers SET fullname=?, phone=?, email=?, profile_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $phone, $email, $filename, $userId);
        if ($stmt->execute()) {
            $message = "Profile updated successfully.";
            $_SESSION['user']['fullname'] = $fullname;
            $_SESSION['user']['phone'] = $phone;
            $_SESSION['user']['email'] = $email;
            $_SESSION['user']['profile_image'] = $filename;
        } else {
            $message = "Error updating profile.";
        }
        $stmt->close();
    }
}

// Fetch sa officers nga data
$stmt = $conn->prepare("SELECT fullname, phone, email, profile_image FROM tourism_officers WHERE id=? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$officer = $result->fetch_assoc();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourism Officer Profile | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen flex flex-col font-[Poppins]">

<!-- MAIN CONTENT -->
<div class="flex-grow flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-8 flex flex-col items-center space-y-6">
        
        <!-- Page Title -->
        <h1 class="text-3xl md:text-3xl font-bold text-center">Tourism Officer <br>Profile</h1>

        <!-- Profile Image -->
        <img src="<?= !empty($officer['profile_image']) ? 'uploads/'.$officer['profile_image'] : 'uploads/default.png' ?>" 
             alt="Profile Image" class="w-32 h-32 rounded-full object-cover border-4 border-green-500 shadow-md">

        <!-- Full Name -->
        <h2 class="text-2xl font-bold"><?= htmlspecialchars($officer['fullname']) ?></h2>
        
        <!-- Email & Phone -->
        <p class="text-gray-600"><?= htmlspecialchars($officer['email']) ?></p>
        <p class="text-gray-600"><?= htmlspecialchars($officer['phone'] ?? '-') ?></p>

        <!-- Message -->
        <?php if(!empty($message)): ?>
            <div class="w-full p-3 bg-green-100 text-green-800 rounded text-center font-medium">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Buttons -->
        <div class="flex flex-col w-full space-y-3">
            <button onclick="openEditModal()" class="w-full bg-green-600 text-white py-2 rounded-xl hover:bg-green-700 transition">
                Edit Profile
            </button>
            <a href="tourism_dashboard.php" class="text-center text-green-600 underline hover:text-green-800 transition">
                ← Back to Home
            </a>
        </div>
    </div>
</div>


<!-- EDIT MODAL -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 relative">
        <button onclick="closeEditModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800 text-lg font-bold">
            ✕
        </button>
        <h2 class="text-2xl font-semibold mb-4 text-center">Edit Profile</h2>
        <form method="POST" enctype="multipart/form-data" class="space-y-4">
            <div>
                <label class="block font-medium mb-1">Full Name</label>
                <input type="text" name="fullname" value="<?= htmlspecialchars($officer['fullname']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($officer['phone'] ?? '') ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500">
            </div>
            <div>
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($officer['email']) ?>" class="w-full border border-gray-300 rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500" required>
            </div>
            <div>
                <label class="block font-medium mb-1">Profile Image</label>
                <input type="file" name="profile_image" accept="image/*">
            </div>
            <button type="submit" name="update_profile" class="w-full bg-green-600 text-white py-2 rounded-xl hover:bg-green-700 transition">Save Changes</button>
        </form>
    </div>
</div>

<script>
function openEditModal() {
    document.getElementById('editModal').classList.remove('hidden');
}
function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}
</script>

<!-- FOOTER -->
<footer class="bg-green-500 text-white py-6 shadow-inner mt-auto">
  <div class="container mx-auto flex flex-col items-center space-y-4 text-center px-4">

    <!-- Logo and Title -->
    <div class="flex flex-col items-center space-y-2">
      <div class="flex items-center space-x-2">
        <img src="img/bee-logo.png" alt="Bee Tours Logo" class="w-12 h-12">
        <h5 class="font-semibold text-lg">Travel Bee Tours</h5>
      </div>
      
      <!-- Tagline with Province nga Logo -->
      <div class="flex items-center space-x-2 mt-2">
        <img src="img/prov-logo.png" alt="Province Logo" class="w-10 h-10">
        <p class="text-sm md:text-base font-medium max-w-xs md:max-w-md">
          Misamisnon Magpuyong Malinawon Malamboon ug Malipayon
        </p>
      </div>
    </div>

    <!-- Copyright -->
    <p class="text-sm mt-2">© 2025 Tara sa Mis.Occ. All rights reserved.</p>
  </div>
</footer>

</body>
</html>
