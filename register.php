<?php
session_start();
include 'config/db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $fullname = trim($_POST['fullname']);
    $address = trim($_POST['address']);
    $email = trim($_POST['email']);
    $raw_password = trim($_POST['password']);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $profile_image = null;

    if (strlen($raw_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $password = password_hash($raw_password, PASSWORD_DEFAULT);

        if (!empty($_FILES['profile_image']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
            $targetFile = $targetDir . $fileName;

            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
                $profile_image = $targetFile;
            }
        }

        $stmt_check = $conn->prepare("SELECT id FROM tourists WHERE email = ?");
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $res_check = $stmt_check->get_result();

        if ($res_check->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            $stmt = $conn->prepare("INSERT INTO tourists (fullname, address, email, password, profile_image, phone_number, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())");
            $stmt->bind_param("ssssss", $fullname, $address, $email, $password, $profile_image, $phone_number);

            if ($stmt->execute()) {
                $success = "Registration successful! <a href='login.php' class='underline text-blue-700'>Login here</a>";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register as Tourist | Tara sa Mis.Occ</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
body {
    background-image: url('img/capitol.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
}
</style>
</head>
<body class="bg-gray-900 flex flex-col min-h-screen justify-between font-[Poppins]">

<section class="flex-grow flex items-center justify-center px-4 py-10">
  <div class="w-full max-w-md bg-white/80 backdrop-blur-sm rounded-2xl shadow-lg p-6 space-y-4 transition-all duration-300">

    <div class="flex flex-col items-center">
      <img class="w-20 h-20 mb-2 rounded-full border-4 border-blue-500 shadow-lg" src="img/logo.png" alt="logo" />
      <h1 class="text-2xl font-bold text-blue-800">Tara sa Mis.Occ</h1>
      <h2 class="text-lg font-semibold text-yellow-600 mt-1">Register as Tourist</h2>
    </div>

    <?php if ($error): ?>
      <p class="text-red-600 text-center text-sm"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
      <p class="text-green-600 text-center text-sm"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-3 text-sm">
      <div>
        <label class="block mb-1 font-medium text-black">Full Name</label>
        <input type="text" name="fullname" required
          class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-black">
      </div>

      <div>
        <label class="block mb-1 font-medium text-black">Address</label>
        <input type="text" name="address" required
          class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-black">
      </div>

      <div>
        <label class="block mb-1 font-medium text-black">Phone Number</label>
        <input type="text" name="phone_number"
          class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-black">
      </div>

      <div>
        <label class="block mb-1 font-medium text-black">Email</label>
        <input type="email" name="email" required
          class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 placeholder-black">
      </div>

      <div>
        <label class="block mb-1 font-medium text-black">Password (min 6 characters)</label>
        <div class="relative">
          <input type="password" name="password" id="password" minlength="6"
            class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2 pr-10" required>
          <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center px-2 text-black hover:text-gray-800">
            <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
          </button>
        </div>
      </div>

      <div>
        <label class="block mb-1 font-medium text-black">Profile Image</label>
        <input type="file" name="profile_image"
          class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2">
      </div>

      <button type="submit" 
        class="w-full bg-gradient-to-r from-green-600 to-yellow-600 text-white font-semibold py-2.5 rounded-lg shadow-md transition">
        <i class="fa-solid fa-user-check mr-2"></i> Register
      </button>

      <p class="text-center text-xs text-black flex justify-center items-center gap-1">
        <i class="fa-solid fa-user"></i>
        Already have an account? 
        <a href="login.php" class="font-medium text-black hover:underline ml-1">Login here</a>
      </p>

      <div class="mt-2 border-t pt-2 text-xs text-gray-800 text-center leading-relaxed px-2">
        <i class="fa-solid fa-lock text-blue-500"></i> 
        By registering, you agree to our 
        <a href="index.php" class="text-blue-700 hover:underline">Privacy Policy</a> and 
        <a href="index.php" class="text-blue-700 hover:underline">Terms of Service</a>.<br>
      </div>
    </form>
  </div>
</section>

<!-- Footer -->
<footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <div class="flex flex-col items-center space-y-2">
      <div class="flex space-x-2">
        <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
        <img src="img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
      </div>
      <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
    </div>

    
    <div class="flex flex-wrap justify-center gap-4 text-white text-base">
      <a href="login.php" class="hover:text-blue-700 transition">Home</a>
      <a href="login.php" class="hover:text-blue-700 transition">Explore</a>
      <a href="login.php" class="hover:text-blue-700 transition">Packages</a>
      <a href="login.php" class="hover:text-blue-700 transition">Contact</a>
    </div>

    <div class="flex space-x-4 text-xl text-white">
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-twitter"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-youtube"></i></a>
    </div>

  </div>

  <div class="mt-2 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>

<script>
function togglePassword() {
    const passwordField = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");
    if (passwordField.type === "password") {
        passwordField.type = "text";
        eyeIcon.classList.add("text-blue-500");
    } else {
        passwordField.type = "password";
        eyeIcon.classList.remove("text-blue-500");
    }
}
</script>

</body>
</html>
