<?php
session_start();
include 'db_connect.php';

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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
.card { min-height: 450px; }
.slider { overflow: hidden; position: relative; height: 250px; }
.slides { display: flex; transition: transform 0.5s ease-in-out; height: 250px; }
.slides img { width: 100%; flex-shrink: 0; object-fit: cover; height: 250px; }
.slider button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.7); border: none; padding: 0.5rem; cursor: pointer; border-radius: 9999px; }
.slider button:hover { background: rgba(255,255,255,0.9); }
.slider .prev { left: 0.5rem; }
.slider .next { right: 0.5rem; }

/* Packages slider */
.slider-container { position: relative; height: 12rem; overflow: hidden; }
.slider-container img { width: 100%; height: 12rem; object-fit: cover; display: none; }
.slider-container img.active { display: block; }
.arrow { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.7); border-radius: 9999px; padding: 0.5rem; cursor: pointer; }
.arrow:hover { background: rgba(255,255,255,0.9); }
.arrow.left { left: 0.5rem; }
.arrow.right { right: 0.5rem; }
</style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen font-[Poppins]">

<?php include 'header.php'; ?>

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

            <div class="p-6 flex-1 flex flex-col justify-between">
              <div>
                <h2 class="text-lg font-semibold flex items-center gap-2">
                    <?= htmlspecialchars($item['title']) ?>
                    <?php if (isset($item['status']) && $item['status'] === 'verified'): ?>
                        <span class="bg-green-100 text-green-600 text-xs font-medium px-2 py-1 rounded-full">✔ Verified</span>
                    <?php else: ?>
                        <span class="bg-orange-100 text-orange-600 text-xs font-medium px-2 py-1 rounded-full">❌ Under-verify</span>
                    <?php endif; ?>
                </h2>

                <p class="text-gray-600 text-sm mt-1"><?= htmlspecialchars($item['location']) ?></p>

                <?php if(!empty($item['price_display'])): ?>
                    <p class="text-green-600 font-bold mt-1 text-sm"><?= $item['price_display'] ?></p>
                <?php endif; ?>

                <p class="mt-2 text-gray-700 text-sm leading-snug">
                    <?= htmlspecialchars(mb_substr($item['description'],0,50)) ?>...
                </p>

                <p class="mt-2 text-gray-500 text-sm italic">
                    Posted by: <?= htmlspecialchars($item['posted_by_name']) ?>
                </p>
              </div>

             <a href="explore_details.php?id=<?= $item['id'] ?>" 
        class="mt-4 inline-block bg-gradient-to-r from-green-400 to-yellow-400 text-black px-4 py-2 rounded text-center 
                hover:bg-gradient-to-r hover:from-red-400 hover:via-pink-400 hover:to-red-500 transition-colors duration-300">
        View Details
        </a>

            </div>
        </div>
    <?php else: ?>
        <!-- Package Card -->
        <div class="bg-white rounded-2xl shadow-md hover:shadow-xl transition duration-300 overflow-hidden flex flex-col card w-full max-w-100 mx-auto">
            <div class="slider-container">
                <?php
                $images = [];
                for($i=1;$i<=3;$i++){
                    if(!empty($item['image'.$i]) && file_exists("uploads/".$item['image'.$i])){
                        $images[] = "uploads/".$item['image'.$i];
                    }
                }
                ?>
                <?php if(!empty($images)): ?>
                    <?php foreach($images as $i=>$img): ?>
                        <img src="<?= htmlspecialchars($img) ?>" class="<?= $i===0?'active':'' ?>">
                    <?php endforeach; ?>
                    <button class="arrow left">&#10094;</button>
                    <button class="arrow right">&#10095;</button>
                <?php else: ?>
                    <img src="assets/no-image.png" class="active">
                <?php endif; ?>
            </div>
            <div class="p-5 flex flex-col flex-1">
                <h2 class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($item['title']) ?></h2>
                <div class="flex items-center justify-between mt-2">
                    <p class="text-green-600 font-bold text-lg">₱<?= number_format($item['price']??0,2) ?></p>
                    <span class="bg-yellow-200 text-yellow-700 text-xs px-2 py-1 rounded-full">Popular</span>
                </div>

                <p class="mt-3 text-gray-600 text-sm leading-snug">
                    <?= htmlspecialchars(mb_substr($item['description'],0,50)) ?>...
                </p>

                <p class="mt-3 text-gray-400 text-xs">Posted by: <?= htmlspecialchars($item['posted_by_name']) ?></p>
                <a href="package_details.php?id=<?= $item['id'] ?>" class="mt-4 inline-block bg-gradient-to-r from-yellow-500 to-green-500 text-white font-medium px-4 py-2 rounded-lg text-center hover:opacity-90 transition">View Details</a>
            </div>
        </div>
    <?php endif; ?>
<?php endforeach; ?>

<?php if(empty($results)): ?>
    <p class="col-span-full text-center text-gray-500">No results found.</p>
<?php endif; ?>

</div>
</main>

<?php include 'footer.php'; ?>

<script>
// Tourist Spot slider
document.querySelectorAll('.slider').forEach(slider => {
    const slides = slider.querySelector('.slides');
    const images = slides.querySelectorAll('img');
    let index = 0;
    const prevBtn = slider.querySelector('.prev');
    const nextBtn = slider.querySelector('.next');
    function showSlide(i){
        if(i<0) i = images.length-1;
        if(i>=images.length) i=0;
        slides.style.transform = `translateX(-${i*100}%)`;
        index=i;
    }
    prevBtn.addEventListener('click',()=>showSlide(index-1));
    nextBtn.addEventListener('click',()=>showSlide(index+1));
    let autoSlide=setInterval(()=>showSlide(index+1),4000);
    function resetInterval(){ clearInterval(autoSlide); autoSlide=setInterval(()=>showSlide(index+1),4000); }
});


</script>

</body>
</html>
