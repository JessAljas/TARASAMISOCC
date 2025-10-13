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

    /** SEARCH IN PACKAGES **/
    $stmt = $conn->prepare("
        SELECT *
        FROM packages 
        WHERE title LIKE ? 
           OR pickup_location LIKE ? 
           OR dropoff_location LIKE ? 
           OR description LIKE ? 
           OR inclusion1 LIKE ? 
           OR inclusion2 LIKE ? 
           OR inclusion3 LIKE ? 
           OR inclusion4 LIKE ? 
           OR price LIKE ?
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
      <!-- Tourist Spot Name -->
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

      <!-- Location -->
      <p class="text-gray-700 font-semibold text-sm mt-1">
        <?= htmlspecialchars($item['location']) ?>
      </p>

      <!-- Entrance Fee -->
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
      class="mt-4 inline-block text-black px-4 py-2 rounded text-center bg-gradient-to-r from-yellow-400 to-green-500 
              hover:from-orange-500 hover:to-red-500 transition-colors duration-300">
        View Details
    </a>
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

        <?php  
        // 🏷️ Dynamic Sunburst Badge Logic
        $badge = '';
        $badgeClass = 'sunburst new';
        if (!empty($item['created_at']) && $item['created_at'] > date('Y-m-d', strtotime('-7 days'))) {
            $badge = 'New';
        } elseif (!empty($item['bookings']) && $item['bookings'] > 50) {
            $badge = 'Best Seller';
            $badgeClass = 'sunburst top';
        } elseif (!empty($item['avg_rating']) && $item['avg_rating'] >= 4.5) {
            $badge = 'Top Rated';
            $badgeClass = 'sunburst top';
        } elseif (!empty($item['price']) && $item['price'] >= 5000) {
            $badge = 'Luxury';
            $badgeClass = 'sunburst premium';
        } elseif (!empty($item['price']) && $item['price'] >= 2500) {
            $badge = 'Premium';
            $badgeClass = 'sunburst premium';
        } elseif (!empty($item['price']) && $item['price'] <= 2000) {   
            $badge = 'Lowest Price';
        }
        ?>

        <?php if($badge): ?>
            <div class="absolute top-3 right-3 <?= $badgeClass ?> z-10"><?= $badge ?></div>
        <?php endif; ?>

        <!-- Image Slider -->
        <div class="slider-container relative">
            <?php
            $images = [];
            for($i=1;$i<=3;$i++){
                if(!empty($item['image'.$i]) && file_exists("uploads/".$item['image'.$i])) {
                    $images[] = "uploads/".$item['image'.$i];
                }
            }
            ?>
            <?php if(!empty($images)): ?>
                <?php foreach($images as $i=>$img): ?>
                    <img src="<?= htmlspecialchars($img) ?>" class="<?= $i===0 ? 'active' : '' ?> w-full h-56 object-cover">
                <?php endforeach; ?>
            <?php else: ?>
                <img src="assets/no-image.png" class="active w-full h-56 object-cover">
            <?php endif; ?>
        </div>

        <!-- Package Info -->
        <div class="p-5 flex flex-col flex-1">
            <h2 class="text-base font-semibold text-gray-900 leading-snug">
                <?= htmlspecialchars($item['title'] ?? 'Untitled Package') ?>
            </h2>

            <!-- Ratings -->
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

            <!-- Price -->
            <p class="mt-2 text-green-600 font-bold text-lg">
                ₱<?= number_format($item['price'] ?? 0, 2) ?>
            </p>

            <!-- Short Description -->
            <p class="mt-1 text-gray-600 text-sm leading-snug line-clamp-2">
                <?= mb_strimwidth(htmlspecialchars($item['description'] ?? ''), 0, 60, "...") ?>
            </p>

            <!-- Posted By -->
            <p class="mt-3 text-gray-400 text-xs">
                Posted by: <?= htmlspecialchars($item['posted_by_name'] ?? 'Agency') ?>
            </p>

            <!-- View Button -->
            <a href="package_details.php?id=<?= $item['id'] ?? 0 ?>" 
               class="mt-3 inline-block w-full text-center bg-gradient-to-r from-yellow-500 to-green-500 text-white font-medium px-4 py-2 rounded-lg hover:opacity-90 transition">
                View Details
            </a>
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
