<?php
session_start();
include '../config/db_connect.php';

// Redirect if wala ka logged in as admin/agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

// Get/Pagkuha sa owner_id
$owner_id = $_GET['owner_id'] ?? 0;
if (!$owner_id) {
    header("Location: agency_registered_spot_owners.php");
    exit;
}

// Get owner details sa spot owners table
$stmt = $conn->prepare("SELECT * FROM spot_owners WHERE id = ?");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$owner) {
    die("Owner not found!");
}

// Fetch tourist spots by this owner
$stmt = $conn->prepare("SELECT * FROM tourist_spots WHERE owner_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $owner_id);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Owner Tourist Spots | Agency Panel</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-100 min-h-screen flex flex-col font-[Poppins]">

<!-- Header -->
<header class="bg-green-500 text-white shadow p-4">
  <div class="container mx-auto flex items-center">
    
    <!-- Back arrow  -->
    <a href="agency_registered_tourist_spots.php" class="flex items-center gap-2 text-white hover:text-gray-200 font-semibold">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
    </a>
    
    <!-- Title tourist by.. -->
    <h1 class="text-2xl font-bold ml-4 truncate">
      Tourist Spots by <?= htmlspecialchars($owner['fullname']) ?>
    </h1>

  </div>
</header>

<main class="container mx-auto p-6 flex-1">

  <!-- Tourist Spot Owner Info -->
  <div class="bg-white shadow rounded-lg p-6 mb-6 flex items-center gap-4">
    <?php if (!empty($owner['profile_image']) && file_exists("uploads/".$owner['profile_image'])): ?>
      <img src="uploads/<?= htmlspecialchars($owner['profile_image']) ?>" alt="Profile" class="w-20 h-20 rounded-full object-cover border">
    <?php else: ?>
      <div class="w-20 h-20 bg-gray-300 rounded-full flex items-center justify-center text-gray-600">N/A</div>
    <?php endif; ?>
    <div>
      <h2 class="text-lg font-bold text-gray-700"><?= htmlspecialchars($owner['fullname']) ?></h2>
      <p class="text-gray-500"><?= htmlspecialchars($owner['email']) ?> | <?= htmlspecialchars($owner['phone_number']) ?></p>
    </div>
  </div>

  <!-- Tourist Spots Table -->
  <div class="overflow-x-auto">
    <table class="min-w-full bg-white shadow rounded-lg overflow-hidden">
      <thead class="bg-green-500 text-white">
        <tr>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Images</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Name</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Description</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Activity</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Location</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Entrance Fee</th>
          <th class="px-6 py-3 text-left text-sm font-medium uppercase">Posted At</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($res && $res->num_rows > 0): ?>
          <?php while ($spot = $res->fetch_assoc()): ?>
          <tr class="hover:bg-gray-50 align-top">
            <!-- Images -->
            <td class="px-6 py-4">
              <div class="flex flex-col gap-2">
                <?php for ($i = 1; $i <= 3; $i++): ?>
                  <?php if (!empty($spot["image$i"]) && file_exists("uploads/".$spot["image$i"])): ?>
                    <img src="uploads/<?= htmlspecialchars($spot["image$i"]) ?>" alt="Spot Image" class="w-24 h-24 rounded object-cover">
                  <?php endif; ?>
                <?php endfor; ?>
                <?php if (empty($spot['image1']) && empty($spot['image2']) && empty($spot['image3'])): ?>
                  <div class="w-24 h-24 bg-gray-200 rounded flex items-center justify-center text-gray-600">N/A</div>
                <?php endif; ?>
              </div>
            </td>

            <td class="px-6 py-4 font-semibold"><?= htmlspecialchars($spot['name']) ?></td>
            <td class="px-6 py-4"><?= nl2br(htmlspecialchars($spot['description'])) ?></td>
            <td class="px-6 py-4"><?= htmlspecialchars($spot['activity']) ?: "N/A" ?></td>
            <td class="px-6 py-4"><?= htmlspecialchars($spot['location']) ?></td>
            <td class="px-6 py-4"><?= $spot['entrance_fee'] ? "₱".number_format($spot['entrance_fee'],2) : "N/A" ?></td>
            <td class="px-6 py-4"><?= date('M d, Y', strtotime($spot['created_at'])) ?></td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No tourist spots posted by this owner.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</main>

<script src=isset/script.js></script>

</body>
</html>
