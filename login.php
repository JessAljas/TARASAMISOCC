<?php
session_start(); // Start the session

include 'config/db_connect.php'; // The database connection file

$error = ""; 
$table = ""; // Table name to check



// Handle form submission for login
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $table = $_POST['table']; // Get the selected table (spot_owner, tourism_officer, or tourist)

    if ($email && $password && $table) {
        // Handle spot_owner login
        if ($table === 'spot_owner') {
            $stmt = $conn->prepare("SELECT * FROM spot_owners WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $spot_owner_result = $stmt->get_result();
            $stmt->close();

            if ($spot_owner_result && $spot_owner_result->num_rows === 1) {
                $spot_owner = $spot_owner_result->fetch_assoc();
                if (password_verify($password, $spot_owner['password'])) {
                    // Successful login
                    $_SESSION['user'] = [
                        'id' => $spot_owner['id'],
                        'fullname' => $spot_owner['fullname'],
                        'email' => $spot_owner['email'],
                        'role' => 'spot_owner',
                        'profile_image' => $spot_owner['profile_image'] ?? null,
                        'phone_number' => $spot_owner['phone_number'] ?? null
                    ];
                    header("Location: tourist_spot_owner_dashboard.php");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Account not found.";
            }
        }
        // Handle tourism_officer login
        elseif ($table === 'tourism_officer') {
            $stmt = $conn->prepare("SELECT * FROM tourism_officers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $tourism_officer_result = $stmt->get_result();
            $stmt->close();

            if ($tourism_officer_result && $tourism_officer_result->num_rows === 1) {
                $tourism_officer = $tourism_officer_result->fetch_assoc();
                if (password_verify($password, $tourism_officer['password'])) {
                    // Successful login
                    $_SESSION['user'] = [
                        'id' => $tourism_officer['id'],
                        'fullname' => $tourism_officer['fullname'],
                        'email' => $tourism_officer['email'],
                        'role' => 'tourism_officer',
                        'profile_image' => $tourism_officer['profile_image'] ?? null,
                        'phone_number' => $tourism_officer['phone_number'] ?? null
                    ];
                    header("Location: tourism_officer_dashboard.php");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Account not found.";
            }
        }
        // Handle tourist login
        elseif ($table === 'tourists') {
            $stmt = $conn->prepare("SELECT * FROM tourists WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $tourist_result = $stmt->get_result();
            $stmt->close();

            if ($tourist_result && $tourist_result->num_rows === 1) {
                $tourist = $tourist_result->fetch_assoc();
                if (password_verify($password, $tourist['password'])) {
                    // Successful login
                    $_SESSION['user'] = [
                        'id' => $tourist['id'],
                        'fullname' => $tourist['fullname'],
                        'email' => $tourist['email'],
                        'role' => 'tourist',
                        'profile_image' => $tourist['profile_image'] ?? null,
                        'phone_number' => $tourist['phone_number'] ?? null
                    ];
                    header("Location: Homepage.php"); // Redirect to tourist dashboard
                    exit;
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Account not found.";
            }
        }
    } else {
        $error = "Please enter email, password, and select a role.";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Login | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
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
<div class="w-full max-w-md bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-8 space-y-6 transition-all duration-300">

  <!-- Role Selection Buttons Above the Logo -->
  <div class="flex flex-col sm:flex-row sm:justify-between sm:space-x-4 mb-6">
  <a href="./owner/login.php?role=spot_owner" 
   class="flex justify-center items-center bg-gradient-to-r from-yellow-500 via-green-500 to-green-600 text-white py-2 px-6 rounded-md shadow-md hover:scale-105 transform transition duration-300 focus:outline-none focus:ring-2 focus:ring-yellow-400 text-sm">
   <i class="fa-solid fa-user-tie mr-2"></i>
   Login as Spot Owner
</a>

 <a href="./admin/admin_login.php?role=tourism_officer" 
   class="flex justify-center items-center bg-gradient-to-r from-green-500 via-yellow-400 to-blue-700 text-white py-2 px-6 rounded-md shadow-md hover:scale-105 transform transition duration-300 focus:outline-none focus:ring-2 focus:ring-blue-400 text-sm">
   <i class="fa-solid fa-briefcase mr-2"></i>
   Login as Tourism Officer
</a>

  </div>

  <div class="flex flex-col items-center mb-6">
    <img class="w-24 h-24 mb-3 rounded-full border-4 border-blue-500 shadow-lg" src="img/logo.png" alt="logo" />
    <h1 class="text-3xl font-bold text-blue-800">Tara sa Mis.Occ</h1>
   <h1 class="text-1xl font-bold text-black-800">
  Login as Tourist
  <i class="fas fa-hand-point-down text-green-500"></i>
</h1>
  </div>

  <?php if ($error): ?>
      <p class="text-red-600 text-center mb-4"><?= htmlspecialchars($error) ?></p>
  <?php endif; ?>

  <form class="space-y-4" method="POST" action="">
    <div>
      <label for="email" class="block mb-2 text-sm font-medium text-black">Your email:</label>
      <input type="email" name="email" id="email"
        class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-black"
        placeholder="Enter your email" required>
    </div>

    <div>
      <label for="password" class="block mb-2 text-sm font-medium text-black">Password:</label>
      <div class="relative">
        <input type="password" name="password" id="password"
          placeholder="Enter your password"
          class="bg-white border border-gray-300 text-black placeholder-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-10"
          required>
        <button type="button" onclick="togglePassword()" class="absolute inset-y-0 right-0 flex items-center px-3 text-black hover:text-gray-800">
          <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path id="eyePath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
          </svg>
        </button>
      </div>
    </div>

    <!-- Hidden Input for Table -->
    <input type="hidden" name="table" id="table" value="tourists" /> <!-- Default is tourists -->

    <button type="submit"
      class="w-full flex items-center justify-center gap-2 text-white bg-gradient-to-r from-yellow-500 to-yellow-600 hover:bg-yellow-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
      <i class="fas fa-sign-in-alt"></i> 
      Login
    </button>
    <p class="text-sm font-light text-black">
         <i class="fa-solid fa-user"></i> Don’t have an account yet? <a href="register.php" class="font-medium text-black hover:underline">Register</a> 
    </p>

      <div class="mt-2 border-t pt-2 text-xs text-gray-800 text-center leading-relaxed px-2">
        <i class="fa-solid fa-lock text-blue-500"></i> 
        By registering, you agree to our 
        <a href="index.php" class="text-blue-700 hover:underline">Privacy Policy</a> and 
        <a href="index.php" class="text-blue-700 hover:underline">Terms of Service</a>.<br>
  </form>
</div>
</section>

<!-- Footer -->
<footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <!-- Logo -->
  <div class="flex flex-col items-center space-y-2">
    <div class="flex space-x-2">
      <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
      <img src="img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
    </div>
    <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
  </div>


    <!-- Navigation Links -->
    <div class="flex flex-wrap justify-center gap-4 text-white text-base">
      <a href="index.php" class="hover:text-blue-700 transition">Home</a>
      <a href="login.php" class="hover:text-blue-700 transition">Explore</a>
      <a href="login.php" class="hover:text-blue-700 transition">Packages</a>
      <a href="login.php" class="hover:text-blue-700 transition">Contact</a>
    </div>

    <!-- Social Media Icons -->
    <div class="flex space-x-4 text-xl text-white">
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-twitter"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-youtube"></i></a>
    </div>

  </div>

  <!-- Footer Bottom -->
  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
</footer>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/explo-details.js"></script>

</body>
</html>
