<?php
session_start();
include 'db_connect.php';

// Prevent caching to avoid back-button access after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect if already logged in as a spot owner
if (isset($_SESSION['user']['id']) && ($_SESSION['user']['role'] ?? '') === 'spot_owner') {
    header("Location: tourist_spot_owner_dashboard.php");
    exit;
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if ($email && $password) {
        $stmt = $conn->prepare("SELECT * FROM spot_owners WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $owner = $res->fetch_assoc();
        $stmt->close();

        if ($owner && password_verify($password, $owner['password'])) {
            // Secure session
            session_regenerate_id(true); // prevents session fixation
            $_SESSION['user'] = [
                'id' => $owner['id'],
                'fullname' => $owner['fullname'],
                'email' => $owner['email'],
                'profile_image' => $owner['profile_image'] ?? null,
                'phone_number' => $owner['phone_number'] ?? null,
                'role' => 'spot_owner'
            ];
            header("Location: tourist_spot_owner_dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Please fill in both fields.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tourist Spot Owner Login</title>
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>
</head>
<body class="flex flex-col min-h-screen bg-gray-100 font-[Poppins]">

  <!-- Main content -->
  <main class="flex-grow flex items-center justify-center">
    <div class="bg-white shadow-xl rounded-2xl p-10 w-full max-w-lg">
      <h1 class="text-4xl md:text-5xl font-extrabold text-black text-center mb-4">Tourist Spot Owner</h1>
      <p class="text-center text-black mb-8">Login to manage your tourist spots</p>

      <?php if($error): ?>
        <div class="bg-red-100 text-red-700 p-3 mb-4 rounded-lg text-center font-medium">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-6">
        <!-- Email -->
        <div>
          <label class="block text-black mb-2 font-semibold">Email</label>
          <input type="email" name="email" class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-400 focus:border-transparent" placeholder="Enter your email" required>
        </div>

        <!-- Password with proper eye toggle -->
        <div class="relative">
          <label class="block text-black mb-2 font-semibold">Password</label>
          <input type="password" id="password" name="password" autocomplete="off" 
                 class="w-full p-3 pr-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-lime-400 focus:border-transparent" 
                 placeholder="Enter your password" required>
          <button type="button" onclick="togglePassword()" 
                  class="absolute inset-y-0 right-3 flex items-center text-gray-500 hover:text-gray-700 focus:outline-none">
            <i id="passIcon" class="fas fa-eye"></i>
          </button>
        </div>

       <button type="submit" 
        class="w-full flex items-center justify-center gap-2 bg-lime-500 text-white p-3 rounded-lg font-semibold hover:bg-lime-600 transition-colors duration-300">
        <i class="fas fa-sign-in-alt"></i>
        Login
      </button>
      </form>

      <p class="text-center text-black mt-6">© <?= date('Y') ?> Tourist Spot Management</p>
    </div>
  </main>

  <!-- Footer -->
  <footer class="bg-green-600 py-3 w-full">
    <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">
      <div class="flex flex-col items-center space-y-2">
        <div class="flex space-x-2">
          <img src="img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
          <img src="img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
          <img src="img/prov-logo.png" alt="Prov Logo" class="w-14 h-14">
        </div>
        <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
      </div>
      <div class="mt-4 text-center text-white text-sm">
        &copy; 2025 Tara sa MisOcc. All rights reserved.
      </div>
    </div>
  </footer>

  <!-- Toggle password script -->
  <script>
  function togglePassword() {
      const password = document.getElementById('password');
      const icon = document.getElementById('passIcon');
      if (password.type === "password") {
          password.type = "text";
          icon.classList.remove("fa-eye");
          icon.classList.add("fa-eye-slash");
      } else {
          password.type = "password";
          icon.classList.remove("fa-eye-slash");
          icon.classList.add("fa-eye");
      }
  }
  </script>
</body>
</html>
