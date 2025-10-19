<?php
session_start();
include 'config/db_connect.php'; // The database connection file

$term = trim($_GET['q'] ?? '');
$results = [];

if ($term !== '') {
    $searchTerm = "%$term%";

    /** SEARCH IN TOURIST SPOTS **/
    $stmt = $conn->prepare("
        SELECT id, name_of_tourist_spot, location, description, image1, image2, image3, entrance_fee, status, 'spot' AS type, posted_by_type, owner_id
        FROM tourist_spots 
        WHERE name_of_tourist_spot LIKE ? 
           OR location LIKE ? 
           OR description LIKE ? 
           OR entrance_fee LIKE ?
    ");
    $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $row['title'] = !empty($row['name_of_tourist_spot']) ? $row['name_of_tourist_spot'] : 'Unnamed Spot';
        $row['price_display'] = !empty($row['entrance_fee']) ? "₱" . number_format($row['entrance_fee'], 2) . " Entrance Fee" : '';

        // Posted by logic
        $posted_by_name = 'Agency';
        if (!empty($row['posted_by_type'])) {
            switch($row['posted_by_type']) {
                case 'owner':
                    if (!empty($row['owner_id'])) {
                        $stmt2 = $conn->prepare("SELECT fullname FROM spot_owners WHERE id = ?");
                        $stmt2->bind_param("i", $row['owner_id']);
                        $stmt2->execute();
                        $res2 = $stmt2->get_result();
                        if ($res2 && $res2->num_rows > 0) {
                            $posted_by_name = $res2->fetch_assoc()['fullname'] . " (Spot Owner)";
                        }
                        $stmt2->close();
                    } else {
                        $posted_by_name = "Spot Owner";
                    }
                    break;
                case 'agency': $posted_by_name = "Agency"; break;
                case 'officer': $posted_by_name = "Tourism Officer"; break;
            }
        }
        $row['posted_by_name'] = $posted_by_name;

        $results[] = $row;
    }
    $stmt->close();

    /** SEARCH IN PACKAGES (with ratings) **/
    $stmt = $conn->prepare("
        SELECT p.*, 
               IFNULL(AVG(r.rating),0) AS avg_rating, 
               COUNT(r.id) AS total_reviews
        FROM packages p
        LEFT JOIN ratings r ON p.id = r.package_id
        WHERE p.title LIKE ? 
           OR p.pickup_location LIKE ? 
           OR p.dropoff_location LIKE ? 
           OR p.description LIKE ? 
           OR p.inclusion1 LIKE ? 
           OR p.inclusion2 LIKE ? 
           OR p.inclusion3 LIKE ? 
           OR p.inclusion4 LIKE ? 
           OR p.price LIKE ?
        GROUP BY p.id
    ");
    $stmt->bind_param("sssssssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $row['type'] = 'package';
        $row['location'] = 'Tour Package';

        // Posted by logic
        $posted_by_name = 'Agency';
        if (!empty($row['owner_id']) && !empty($row['posted_by_type'])) {
            switch($row['posted_by_type']) {
                case 'owner':
                    $stmt2 = $conn->prepare("SELECT fullname FROM spot_owners WHERE id = ?");
                    break;
                case 'agency':
                    $stmt2 = $conn->prepare("SELECT fullname FROM agency WHERE id = ?");
                    break;
                case 'officer':
                    $stmt2 = $conn->prepare("SELECT fullname FROM tourism_officers WHERE id = ?");
                    break;
                default: $stmt2 = null;
            }
            if ($stmt2) {
                $stmt2->bind_param("i", $row['owner_id']);
                $stmt2->execute();
                $res2 = $stmt2->get_result();
                if ($res2 && $res2->num_rows > 0) {
                    $posted_by_name = $res2->fetch_assoc()['fullname'] . ' (' . ucfirst($row['posted_by_type']) . ')';
                }
                $stmt2->close();
            }
        }
        $row['posted_by_name'] = $posted_by_name;

        $results[] = $row;
    }
    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Search Results | Tara sa Mis.Occ</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="config/css/style.css">
<script src="https://cdn.tailwindcss.com"></script>

</head>
<body class="bg-gray-50 flex flex-col min-h-screen font-[Poppins]">

<?php include 'config/include/header.php'; ?>

<div class="text-center mt-6">
    <h1 class="text-3xl md:text-4xl font-bold text-gray-800">
        Search Results for: "<span class="text-blue-600"><?= htmlspecialchars($term) ?></span>"
    </h1>
</div>

<main class="container mx-auto px-6 py-8 flex-1">
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

<?php foreach($results as $item): ?>
    <?php if($item['type'] === 'spot'): ?>
      <!-- Tourist Spot Card -->
<div class="bg-white shadow rounded-lg overflow-hidden flex flex-col card max-w-100 mx-auto">
  <div class="slider relative">
    <div class="slides">
      <?php for ($i=1; $i<=3; $i++):
          $img = $item['image'.$i];
          if ($img && file_exists("uploads/".$img)): ?>
        <img src="uploads/<?= htmlspecialchars($img) ?>" alt="Spot Image">
      <?php endif; endfor; ?>
    </div>
    <button class="prev">&lt;</button>
    <button class="next">&gt;</button>
  </div>

  <!-- Tourist Spot Info -->
  <div class="p-6 flex-1 flex flex-col justify-between">
    <div>
      <h2 class="relative text-xl font-bold">
        <?= htmlspecialchars($item['name_of_tourist_spot']) ?>
        <?php if (isset($item['status']) && $item['status'] === 'verified'): ?>
          <span class="absolute -top-3 right-0 inline-flex items-center justify-center">
              <i class="fa fa-certificate text-green-500 text-4xl"></i>
              <span class="absolute text-[6px] font-bold text-white">
                  Verified
              </span>
          </span>
        <?php else: ?>
          <span class="absolute -top-3 right-0 inline-flex items-center justify-center">
              <i class="fa fa-certificate text-orange-500 text-4xl"></i>
              <span class="absolute text-[5px] font-bold text-white">
                  Pending
              </span>
          </span>
        <?php endif; ?>
      </h2>

      <p class="text-gray-700 font-semibold text-sm mt-1">
        <?= htmlspecialchars($item['location']) ?>
      </p>

      <?php if(!empty($item['price_display'])): ?>
        <p class="text-green-600 font-bold mt-2 text-base">
          <?= $item['price_display'] ?>
        </p>
      <?php endif; ?>

      <p class="mt-2 text-gray-500 text-sm italic">
        Posted by: <?= htmlspecialchars($item['posted_by_name']) ?>
      </p>
    </div>

    <a href="explore_details.php?id=<?= $item['id'] ?>" 
       class="inline-block text-black px-6 py-2 rounded text-center bg-gradient-to-r from-yellow-400 to-green-500 
              hover:from-orange-500 hover:to-red-500 transition-colors duration-300">
      View Details <i class="fa-solid fa-circle-info ml-2"></i>
    </a>

    <div class="mt-0">
      <img src="img/footer.jpg" alt="Footer Image" class="w-full h-16 object-cover rounded-b-lg">
    </div>
  </div>
</div>

 <?php else: ?>
    <!-- Package Card -->
    <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition duration-300 overflow-hidden flex flex-col relative">

       <!-- Status Badge -->
    <?php if($item['status'] === 'approved'): ?>
        <span class="absolute top-3 left-3 bg-green-500 text-white text-xs font-semibold px-2 py-1 rounded-lg z-10 flex items-center gap-1">
            <i class="fas fa-check-circle"></i>
            Verified
        </span>
    <?php elseif($item['status'] === 'pending'): ?>
        <span class="absolute top-3 left-3 bg-yellow-400 text-white text-xs font-semibold px-2 py-1 rounded-lg z-10 flex items-center gap-1">
            <i class="fas fa-hourglass-half"></i>
            Under Review
        </span>
    <?php endif; ?>

    <!-- Dynamic Sunburst Badge -->
    <?php  
    $badge = '';
    $badgeClass = 'sunburst new';

    if ($item['created_at'] > date('Y-m-d', strtotime('-7 days'))) {
        $badge = 'New';
        $badgeClass = 'sunburst new';
    } elseif (!empty($item['bookings']) && $item['bookings'] > 50) {
        $badge = 'Best Seller';
        $badgeClass = 'sunburst top';
    } elseif ($item['avg_rating'] >= 4.5) {
        $badge = 'Top Rated';
        $badgeClass = 'sunburst top';
    } elseif ($item['price'] >= 5000) {
        $badge = 'Luxury';
        $badgeClass = 'sunburst premium';
    } elseif ($item['price'] >= 2500) {
        $badge = 'Premium';
        $badgeClass = 'sunburst premium';
    } elseif ($item['price'] <= 2000) {   
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
        if (!empty($item['image'.$i]) && file_exists("uploads/".$item['image'.$i])) {
            $mainImage = "uploads/".$item['image'.$i];
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
            ₱<?= number_format($item['price'] ?? 0, 2) ?>
        </div>

        <h2 class="text-base font-semibold text-gray-900 leading-snug">
            <?= htmlspecialchars($item['title'] ?? 'Untitled Package') ?>
        </h2>

        <!-- Rating + Reviews -->
<div class="flex items-center mt-1 text-xs text-gray-500">
    <?php
    $avg = round($item['avg_rating'] ?? 0, 1);
    $fullStars = floor($avg);
    $halfStar = ($avg - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    for ($i = 0; $i < $fullStars; $i++) echo "⭐";
    if ($halfStar) echo "⭐"; 
    for ($i = 0; $i < $emptyStars; $i++) echo "☆";
    ?>
    <span class="ml-1">(<?= $item['total_reviews'] ?? 0 ?>)</span>
</div>


        <p class="mt-2 text-gray-600 text-sm leading-snug line-clamp-2">
            <?= mb_strimwidth(htmlspecialchars($item['description'] ?? ''), 0, 60, "...") ?>
        </p>

        <div class="mt-3 flex gap-2">
            <a href="package_details.php?id=<?= $item['id'] ?? 0 ?>" 
               class="flex-1 text-center px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white font-medium rounded-lg transition transform hover:scale-105 flex items-center justify-center gap-1">
                <i class="fas fa-eye"></i>
                View Details
            </a>
            <a href="package_details.php?id=<?= $item['id'] ?? 0 ?>#bookingForm" 
               class="flex-1 text-center px-4 py-2 bg-green-500 hover:bg-green-600 text-white font-medium rounded-lg transition transform hover:scale-105 flex items-center justify-center gap-1">
                <i class="fas fa-calendar-check"></i>
                Book Now
            </a>
        </div>

        <div class="-mt-2">
            <img src="img/foo.jpg" alt="Footer Image" class="w-full h-12 object-cover rounded-b-lg">
        </div>

    </div>
</div>

<?php endif; ?>
<?php endforeach; ?>

<?php if(empty($results)): ?>
    <p class="col-span-full text-center text-gray-500">No results found.</p>
<?php endif; ?>


</div>
</main>
<?php include 'config/include/footer.php'; ?>
<script src="js/prof.js"></script>

</body>
</html>
