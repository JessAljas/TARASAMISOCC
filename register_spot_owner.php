<?php
session_start();
include 'db_connect.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name_of_tourist_spot = trim($_POST['name_of_tourist_spot']);
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = trim($_POST['phone_number'] ?? '');
    $profile_image = null;

    // Handle sa profile image upload sa register sa spot owner
    if (!empty($_FILES['profile_image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

        $fileName = time() . "_" . basename($_FILES["profile_image"]["name"]);
        $targetFile = $targetDir . $fileName;

        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $targetFile)) {
            $profile_image = $targetFile;
        }
    }

    // Check if email is already nag exists
    $stmt_check = $conn->prepare("SELECT id FROM spot_owners WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();
    if ($res_check->num_rows > 0) {
        $error = "Email is already registered.";
    } else {
        // Insert sa spot_owners table
        $stmt = $conn->prepare("INSERT INTO spot_owners (name_of_tourist_spot, fullname, email, password, profile_image, phone_number, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $name_of_tourist_spot, $fullname, $email, $password, $profile_image, $phone_number);

        if ($stmt->execute()) {
            $success = "Registration successful! <a href='login_spot_owner.php' class='underline text-blue-700'>Login here</a>";
        } else {
            $error = "Error: " . $stmt->error;
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register Tourist Spot Owner | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 flex flex-col min-h-screen justify-between">

<section class="flex-grow flex items-center justify-center px-4 py-10">
<div class="w-full max-w-md bg-white/70 backdrop-blur-sm rounded-2xl shadow-lg p-8 space-y-6">

    <div class="flex flex-col items-center">
        <h1 class="text-3xl font-bold text-blue-800">Register as Spot Owner</h1>
    </div>

    <?php if ($error): ?>
        <p class="text-red-600 text-center"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="text-green-600 text-center"><?= $success ?></p>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div>
            <label class="block mb-2 text-sm font-medium text-black">Tourist Spot Name</label>
            <input type="text" name="name_of_tourist_spot" required
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-black">Full Name</label>
            <input type="text" name="fullname" required
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-black">Email</label>
            <input type="email" name="email" required
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-black">Password</label>
            <input type="password" name="password" required
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-black">Phone Number</label>
            <input type="text" name="phone_number"
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <div>
            <label class="block mb-2 text-sm font-medium text-black">Profile Image</label>
            <input type="file" name="profile_image"
                class="bg-white border border-gray-300 text-black rounded-lg block w-full p-2.5">
        </div>

        <button type="submit"
            class="w-full text-white bg-yellow-500 hover:bg-yellow-800 font-medium rounded-lg text-sm px-5 py-2.5">
            Register
        </button>

        <p class="text-sm font-light text-black text-center">
            Already have an account? <a href="login_spot_owner.php" class="underline text-blue-700">Login here</a>
        </p>
    </form>
</div>
</section>
</body>
</html>
