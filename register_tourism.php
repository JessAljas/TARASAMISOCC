<?php
session_start();
include 'db_connect.php';

$error = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);

    // Check if ang email already ga exists
    $stmt = $conn->prepare("SELECT id FROM tourism_officers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $error = "Email already registered.";
    } else {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO tourism_officers (fullname, email, password, phone) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullname, $email, $hashed, $phone);
        if ($stmt->execute()) {
            // Auto-login after sa registration
            $_SESSION['user'] = [
                'id' => $stmt->insert_id,
                'fullname' => $fullname,
                'email' => $email,
                'role' => 'tourism_officers',
                'profile_image' => null,
                'phone' => $phone
            ];
            header("Location: tourism_dashboard.php");
            exit;
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Register Tourism Officer</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
<div class="bg-white shadow-md rounded px-8 pt-6 pb-8 w-full max-w-md">
    <h1 class="text-2xl font-bold text-yellow-600 text-center mb-6">Register as Tourism Officer</h1>
    <?php if ($error): ?>
        <p class="text-red-600 text-center mb-4"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST" class="space-y-4">
        <div>
            <label class="block text-gray-700">Full Name</label>
            <input type="text" name="fullname" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-yellow-300">
        </div>
        <div>
            <label class="block text-gray-700">Email</label>
            <input type="email" name="email" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-yellow-300">
        </div>
        <div>
            <label class="block text-gray-700">Password</label>
            <input type="password" name="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-yellow-300">
        </div>
        <div>
            <label class="block text-gray-700">Phone</label>
            <input type="text" name="phone" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:ring-yellow-300">
        </div>
        <button type="submit" class="w-full bg-yellow-600 text-black py-2 rounded hover:bg-green-600 font-semibold">Register</button>
    </form>
    <p class="mt-4 text-center text-gray-700">
        Already have an account? <a href="admin_login.php" class="text-blue-600 hover:underline">Login here</a>
    </p>
</div>
</body>
</html>