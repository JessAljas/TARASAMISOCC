<?php
session_start();
include 'db_connect.php'; // Database connection file

// Enable error nga reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = '';
$error = '';

// Redirect if wala ka logged in
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit;
}

// Tourist info from session
$user_id = $_SESSION['user']['id'];
$user_email = $_SESSION['user']['email'] ?? '';
$user_name = $_SESSION['user']['name'] ?? $user_email;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $error = "Message field is required.";
    } else {
        $receiver_role = 'agency';
        $subject = $user_name;
        $sender_role = 'tourist';

        // Code nga make sure naka register ang tourist
        $check = $conn->prepare("SELECT id FROM tourists WHERE id = ?");
        $check->bind_param("i", $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $error = "Invalid tourist ID.";
        }
        $check->close();

        // Insert message ug inquiries/message
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO inquiries (sender_id, sender_role, receiver_role, subject, message, status, created_at) VALUES (?, ?, ?, ?, ?, 'unread', NOW())");
            $stmt->bind_param("issss", $user_id, $sender_role, $receiver_role, $subject, $message);
            if ($stmt->execute()) {
                $success = "Message sent successfully!";
            } else {
                $error = "Failed to send message: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Contact | Tara sa Mis.Occ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" />
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-[Poppins]">

<?php include 'header.php'; ?>

<section class="bg-gray-100 py-12">
  <div class="container mx-auto px-4 lg:px-20 flex flex-col lg:flex-row gap-10">

    <!-- Contact nga Form -->
    <div class="w-full lg:w-1/2 bg-white rounded-2xl shadow-lg p-8 hover:shadow-2xl transition">
      <h2 class="text-3xl font-bold text-blue-900 mb-6">Get in Touch</h2>

      <?php if ($success): ?>
        <p class="bg-green-100 text-green-800 border border-green-300 p-4 rounded mb-4 shadow-sm">
          <?= htmlspecialchars($success) ?>
        </p>
      <?php elseif ($error): ?>
        <p class="bg-red-100 text-red-800 border border-red-300 p-4 rounded mb-4 shadow-sm">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label class="block font-medium text-gray-700 mb-1">Message</label>
          <textarea name="message" rows="5" required
                    placeholder="Write your message here..."
                    class="w-full px-4 py-3 border rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-400 transition"></textarea>
        </div>
        <button type="submit"
                class="bg-yellow-400 hover:bg-yellow-500 text-blue-900 font-bold px-6 py-3 rounded-lg w-full flex items-center justify-center transition">
          <i class="fas fa-paper-plane mr-2"></i> Send Message
        </button>
      </form>
    </div>

    <!-- Contact Info ug Map -->
    <div class="w-full lg:w-1/2 flex flex-col gap-6">
      <div class="bg-white rounded-2xl shadow-lg p-8 hover:shadow-2xl transition">
        <h2 class="text-3xl font-bold text-blue-900 mb-4">Contact Information</h2>
        <div class="space-y-3 text-gray-700">
          <p class="flex items-center"><i class="fas fa-map-marker-alt text-yellow-400 mr-3"></i>Municipality of Tudela, Misamis Occidental, Philippines</p>
          <p class="flex items-center"><i class="fas fa-phone-alt text-yellow-400 mr-3"></i>+63 912 345 6789</p>
          <p class="flex items-center"><i class="fas fa-envelope text-yellow-400 mr-3"></i>contact@tarasamisocc.com</p>
          <p class="flex items-center"><i class="fab fa-facebook text-yellow-400 mr-3"></i>
            <a href="https://www.facebook.com/TaraSaMisOcc" target="_blank" class="hover:text-blue-600 underline">Tara Sa Mis.Occ</a>
          </p>
          <p class="flex items-center"><i class="fas fa-globe text-yellow-400 mr-3"></i>
            <a href="http://www.tarasamisocc.com" target="_blank" class="hover:text-blue-600 underline">www.tarasamisocc.com</a>
          </p>
        </div>
      </div>

      <div class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition">
        <div id="map" class="w-full h-72 rounded-lg"></div>
      </div>
    </div>
  </div>
</section>

<?php include 'footer.php'; ?>
<script src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"></script>
<script>
  var map = L.map('map').setView([8.241387, 123.846906], 17);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);

  var customIcon = L.icon({
      iconUrl: 'img/logo.png',
      iconSize: [50, 50],
      iconAnchor: [25, 50],
      popupAnchor: [0, -45]
  });

  L.marker([8.241387, 123.846906], { icon: customIcon }).addTo(map)
      .bindPopup('<b>Tara sa Mis.Occ Main Office</b><br>Municipal Hall, Tudela, Misamis Occidental')
      .openPopup();
</script>
</body>
</html>
