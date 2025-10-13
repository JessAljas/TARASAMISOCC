<?php
include 'config/db_connect.php'; // The database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone_number = $_POST['phone_number'];

    // Handle sa profile image upload sa register
    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $profile_image = $target_dir . basename($_FILES['profile_image']['name']);
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);
    }

    // Insert the new agency into the database
    $stmt = $conn->prepare("INSERT INTO agency (fullname, email, password, phone_number, profile_image) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $fullname, $email, $password, $phone_number, $profile_image);

    if ($stmt->execute()) {
        echo "<script>alert('Agency registered successfully!'); window.location.href='admin_login.php';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html>
<head>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <title>Register as Agency</title>
</head>
<body>
    <h2>Agency Registration</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="fullname" placeholder="Full Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="text" name="phone" placeholder="Phone Number" required><br>
        <input type="file" name="profile_image"><br>
        <button type="submit">Register</button>
    </form>
</body>
</html>
