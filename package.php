<?php
session_start();
include 'config/db_connect.php'; // The database connection file

// ✅ Fetch all packages with avg rating, total reviews, and bookings count
$res = $conn->query("
    SELECT p.*, 
           COALESCE(AVG(r.rating), 0) AS avg_rating,
           COUNT(r.id) AS total_reviews,
           (SELECT COUNT(*) FROM bookings b WHERE b.package_id = p.id) AS bookings
    FROM packages p
    LEFT JOIN ratings r ON p.id = r.package_id
    GROUP BY p.id
    ORDER BY p.created_at DESC
");

$packages = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $posted_by_name = 'Agency';

        if (!empty($row['owner_id']) && !empty($row['posted_by_type'])) {
            switch($row['posted_by_type']) {
                case 'owner':
                    $stmt = $conn->prepare("SELECT fullname FROM spot_owners WHERE id = ?");
                    break;
                case 'agency':
                    $stmt = $conn->prepare("SELECT fullname FROM agency WHERE id = ?");
                    break;
                case 'officer':
                    $stmt = $conn->prepare("SELECT fullname FROM tourism_officers WHERE id = ?");
                    break;
                default:
                    $stmt = null;
            }
            if ($stmt) {
                $stmt->bind_param("i", $row['owner_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $posted_by_name = $result->fetch_assoc()['fullname'] . ' (' . ucfirst($row['posted_by_type']) . ')';
                }
                $stmt->close();
            }
        }

        $row['posted_by_name'] = $posted_by_name;
        $packages[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tour Packages | Tara sa Mis.Occ</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="config/css/style.css">
<script src="https://cdn.tailwindcss.com"></script>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50 flex flex-col min-h-screen font-[Poppins]">

   <?php include 'config/include/header.php'; ?>

<div class="text-center mt-6">
    <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
        Explore Our Tour Packages
    </h1>
  <p class="text-4xl font-bold text-green-800" style="font-family: 'Brush Script MT', 'Lucida Handwriting', cursive;">
  Book your tour now and get exciting discounts for group bookings!
</p>

</div>

<main class="mx-auto p-6 flex-1 max-w-8xl">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

<?php foreach($packages as $package): ?>
    <?php if($package['status'] === 'rejected') continue; ?>

    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition duration-300 overflow-hidden flex flex-col relative">

    <!-- Status Badge -->
    <?php if($package['status'] === 'approved'): ?>
        <span class="absolute top-3 left-3 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded-lg z-10 flex items-center gap-1">
            <i class="fas fa-check-circle"></i>
            Verified
        </span>
    <?php elseif($package['status'] === 'pending'): ?>
        <span class="absolute top-3 left-3 bg-yellow-400 text-white text-xs font-semibold px-2 py-1 rounded-lg z-10 flex items-center gap-1">
            <i class="fas fa-hourglass-half"></i>
            Under Review
        </span>
    <?php endif; ?>

        <!-- Dynamic Sunburst Badge -->
        <?php  
        $badge = '';
        $badgeClass = 'sunburst new';

        if ($package['created_at'] > date('Y-m-d', strtotime('-7 days'))) {
            $badge = 'New';
            $badgeClass = 'sunburst new';
        } elseif (!empty($package['bookings']) && $package['bookings'] > 50) {
            $badge = 'Best Seller';
            $badgeClass = 'sunburst top';
        } elseif ($package['avg_rating'] >= 4.5) {
            $badge = 'Top Rated';
            $badgeClass = 'sunburst top';
        } elseif ($package['price'] >= 5000) {
            $badge = 'Luxury';
            $badgeClass = 'sunburst premium';
        } elseif ($package['price'] >= 2500) {
            $badge = 'Premium';
            $badgeClass = 'sunburst premium';
        } elseif ($package['price'] <= 2000) {   
            $badge = 'Lowest Price';
            $badgeClass = 'sunburst new';
        }
        ?>

    <?php if($badge): ?>
        <div class="absolute top-3 right-3 <?= $badgeClass ?> z-10">
            <?= $badge ?>
        </div>
    <?php endif; ?>

    <!-- Main Image -->
    <?php 
    $mainImage = null;
    for ($i=1; $i<=4; $i++) {
        if (!empty($package['image'.$i]) && file_exists("uploads/".$package['image'.$i])) {
            $mainImage = "uploads/".$package['image'.$i];
            break;
        }
    }
    ?>
    <img src="<?= $mainImage ? htmlspecialchars($mainImage) : 'placeholder.jpg' ?>" 
         class="w-full h-56 object-cover rounded-t-xl">

<!-- Card Content -->
<div class="relative bg-white rounded-lg shadow p-4 pt-6">

    <!-- Sunburst Price Badge -->
<div class="absolute top-2 right-4 w-16 h-16 rounded-full bg-red-400 flex items-center justify-center text-white font-bold text-xs shadow-lg
            before:content-[''] before:absolute before:inset-0 before:rounded-full before:border-4 before:border-yellow-300">
    ₱<?= number_format($package['price'] ?? 0, 2) ?>
</div>


    <!-- Package Title -->
    <h2 class="text-base font-semibold text-gray-900 leading-snug">
        <?= htmlspecialchars($package['title'] ?? 'Untitled Package') ?>
    </h2>

    <!-- Rating + Reviews -->
    <div class="flex items-center mt-1 text-xs text-gray-500">
        <?php
        $avg = round($package['avg_rating'], 1);
        $fullStars = floor($avg);
        $halfStar = ($avg - $fullStars) >= 0.5 ? 1 : 0;
        $emptyStars = 5 - $fullStars - $halfStar;

        for ($i = 0; $i < $fullStars; $i++) echo "⭐";
        if ($halfStar) echo "⭐"; 
        for ($i = 0; $i < $emptyStars; $i++) echo "☆";
        ?>
        <span class="ml-1">(<?= $package['total_reviews'] ?>)</span>
    </div>

    <!-- Short Description -->
    <p class="mt-2 text-gray-600 text-sm leading-snug line-clamp-2">
        <?= mb_strimwidth(htmlspecialchars($package['description'] ?? ''), 0, 60, "...") ?>
    </p>

   <!-- Buttons -->
<div class="mt-3 flex gap-2">
  <!-- View Details Button -->
  <a href="package_details.php?id=<?= $package['id'] ?? 0 ?>" 
     class="flex-1 text-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition transform hover:scale-105 flex items-center justify-center gap-1">
      <i class="fas fa-eye"></i>
      View Details
  </a>

  <!-- Book Now Button -->
  <a href="package_details.php?id=<?= $package['id'] ?? 0 ?>#bookingForm"
     class="flex-1 text-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition transform hover:scale-105 flex items-center justify-center gap-1">
      <i class="fas fa-calendar-check"></i>
      Book Now
  </a>
</div>


    <!-- Card Footer with Image -->
    <div class="-mt-2"> <!-- smaller negative margin -->
        <img src="img/foo.jpg" alt="Footer Image" class="w-full h-12 object-cover rounded-b-lg">
    </div>
    </div>
    </div>

<?php endforeach; ?>

<?php if(empty($packages)): ?>
    <p class="col-span-full text-center text-gray-500">No packages available yet.</p>
<?php endif; ?>

</div>
</main>

<?php include 'config/include/footer.php'; ?>
</body>
</html>
