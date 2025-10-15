<?php
session_start();
include '../config/db_connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

   $roles = [
    'agency' => ['table' => 'agency', 'redirect' => 'agency_dashboard.php'],
    'tourism_officers' => ['table' => 'tourism_officers', 'redirect' => '../tourism/staff_dashboard.php']
];

    $found = false;

    foreach ($roles as $role => $info) {
        $table = $info['table'];
        $stmt = $conn->prepare("SELECT * FROM `$table` WHERE email = ?");
        if (!$stmt) {
            $error = "Database error: " . $conn->error;
            break;
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res && $res->num_rows === 1) {
            $user = $res->fetch_assoc();
            if (isset($user['password']) && password_verify($password, $user['password'])) {
                $_SESSION['user'] = [
                    'id' => $user['id'],
                    'fullname' => $user['fullname'],
                    'email' => $user['email'],
                    'role' => $role,
                    'profile_image' => $user['profile_image'] ?? null,
                    'phone_number' => $user['phone_number'] ?? null
                ];

                header("Location: " . $info['redirect']);
                exit;
            } else {
                $error = "Invalid email or password.";
                $found = true;
                break;
            }
        }
    }

    if (!$found && empty($error)) {
        $error = "Account not found.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Login | Tara sa Mis.Occ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="flex flex-col min-h-screen justify-between bg-gray-100 font-[Poppins]">

  <!-- Login Section -->
  <section class="flex-grow flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white/90 backdrop-blur-sm rounded-2xl shadow-lg p-8 space-y-6 transition-all duration-300">

      <!-- Logo at the babaw sa Login card -->
      <div class="flex justify-center mb-4">
        <img src="../img/prov-logo.png" alt="Provincial Logo" class="w-24 h-24 object-contain">
      </div>

      <!-- Header ni Admin -->
      <div class="text-center mb-6">
        <h1 class="text-2xl font-bold text-blue-800">Administrator & Staff <br>Login</h1>
      </div>

      <!-- If naay error mo display -->
      <?php if ($error): ?>
        <p class="text-red-600 text-center mb-4"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>

      <!-- Login Form -->
      <form method="POST" action="" class="space-y-4">

        <!-- Email Field sa Login-->
        <div>
          <label for="email" class="block mb-2 text-sm font-medium text-black">Email</label>
          <input type="email" name="email" id="email" placeholder="Enter your username"
                 class="bg-white border border-gray-300 text-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 placeholder-black"
                 required>
        </div>

        <!-- Password Field sa Login -->
        <div>
          <label for="password" class="block mb-2 text-sm font-medium text-black">Password</label>
          <div class="relative">
            <input type="password" name="password" id="password" placeholder="Enter your password"
                   class="bg-white border border-gray-300 text-black placeholder-black rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 pr-10"
                   required>
            <button type="button" onclick="togglePassword()"
                    class="absolute inset-y-0 right-0 flex items-center px-3 text-black hover:text-gray-800">
              <svg id="eyeIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                   viewBox="0 0 24 24" stroke="currentColor">
                <path id="eyePath" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Login Button -->
        <button type="submit"
                class="w-full flex items-center justify-center gap-2 text-white bg-yellow-500 hover:bg-yellow-800 focus:ring-4 focus:outline-none focus:ring-yellow-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
          <i class="fas fa-sign-in-alt"></i>
          Login
        </button>

      </form>
    </div>
  </section>

  <!-- Start sa Footer -->
  <footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">

    <!-- Logo sa Footer -->
  <div class="flex flex-col items-center space-y-2">
    <div class="flex space-x-2">
      <img src="../img/logo.png" alt="Tara sa MisOcc Logo" class="w-14 h-14 rounded-full border-2 border-blue-900">
      <img src="../img/bee-logo.png" alt="Bee Logo" class="w-14 h-14">
      <img src="../img/prov-logo.png" alt="Bee Logo" class="w-14 h-14">
    </div>
    <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
  </div>
    <!-- Bottom sa Footer-->
  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
  </footer>
  
  <script src="../script.js"></script>

</body>
</html>
