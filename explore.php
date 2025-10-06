<?php
session_start();
include 'db_connect.php';

// Fetch all tourist spots except sa rejected
$res = $conn->query("SELECT * FROM tourist_spots WHERE status != 'rejected' OR status IS NULL ORDER BY created_at DESC");

$spots = [];
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        // Default posted_by_name is from owner_name nga column
        $posted_by_name = !empty($row['owner_name']) 
            ? $row['owner_name'] 
            : 'Unknown Owner';

        // Optional nga code: add role label
        if (!empty($row['posted_by_type'])) {
            $posted_type = strtolower(trim($row['posted_by_type']));
            switch ($posted_type) {
                case 'owner':
                    $posted_by_name .= " (Spot Owner)";
                    break;
                case 'agency':
                    $posted_by_name .= " (Agency)";
                    break;
                case 'tourism_officer':
                case 'tourism':
                    $posted_by_name .= " (Tourism Officer)";
                    break;
                default:
                    $posted_by_name .= " (" . ucfirst($row['posted_by_type']) . ")";
            }
        }

        // Ensure spot name always nga present
        $row['name_of_tourist_spot'] = !empty($row['name_of_tourist_spot']) 
            ? $row['name_of_tourist_spot'] 
            : 'Unnamed Spot';

        // Format sa entrance fee
        $row['entrance_fee_display'] = !empty($row['entrance_fee']) 
            ? "₱" . number_format($row['entrance_fee'], 2) . " Entrance Fee" 
            : '';

        $row['posted_by_name'] = $posted_by_name;
        $spots[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Explore Tourist Spots | Tara sa Mis.Occ</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<style>
.slider { overflow: hidden; position: relative; height: 60% !important; }
.slides { display: flex; transition: transform 0.5s ease-in-out; }
.slides img { width: 100%; flex-shrink: 0; object-fit: cover; height: 250px; }
.slider button { position: absolute; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.7); border: none; padding: 0.5rem; cursor: pointer; border-radius: 9999px; }
.slider button:hover { background: rgba(255,255,255,0.9); }
.slider .prev { left: 0.5rem; }
.slider .next { right: 0.5rem; }
.card { min-height: 420px; }
</style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen font-[Poppins]">

<?php include 'header.php'; ?>

<div class="flex justify-center mt-2">
    <h1 class="text-black drop-shadow-xl text-3xl font-bold mb-4" style="font-family: 'Poppins', sans-serif;">
        Travel ta Diri sa Mis.Occ.!
    </h1>
</div>

<main class="mx-auto p-6 flex-1 max-w-8xl">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach($spots as $spot): ?>
      <div class="bg-white shadow rounded-lg overflow-hidden flex flex-col card">
        <!-- Image Slider sa tourist spots -->
        <div class="slider relative">
          <div class="slides">
            <?php for ($i=1; $i<=3; $i++):
                $img = $spot['image'.$i];
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
            <?= htmlspecialchars($spot['name_of_tourist_spot']) ?>

            <?php if (isset($spot['status']) && $spot['status'] === 'verified'): ?>
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


            <!-- Location sa tourist spots-->
            <p class="text-gray-700 font-semibold text-sm mt-1"><?= htmlspecialchars($spot['location']) ?></p>

            <!-- Entrance Fee sa tourist spots -->
            <?php if(!empty($spot['entrance_fee_display'])): ?>
                <p class="text-green-600 font-bold mt-2 text-base">
                    <?= $spot['entrance_fee_display'] ?>
                </p>
            <?php endif; ?>

            <p class="mt-2 text-gray-500 text-sm italic">
                Posted by: <?= htmlspecialchars($spot['posted_by_name']) ?>
            </p>
          </div>

          <a href="explore_details.php?id=<?= $spot['id'] ?>" 
            class="mt-4 inline-block text-black px-4 py-2 rounded text-center bg-gradient-to-r from-yellow-400 to-green-500 
                    hover:from-orange-500 hover:to-red-500 transition-colors duration-300">
                View Details
          </a>

        </div>
      </div>
    <?php endforeach; ?>
</div>
</main>

<?php include 'footer.php'; ?>

<script>
// Slider sa picture Functionality
document.addEventListener('DOMContentLoaded', () => {
    const sliders = document.querySelectorAll('.slider');
    sliders.forEach(slider => {
        const slides = slider.querySelector('.slides');
        const images = slides.querySelectorAll('img');
        let index = 0;
        const prevBtn = slider.querySelector('.prev');
        const nextBtn = slider.querySelector('.next');

        function showSlide(i) {
            if(i < 0) i = images.length - 1;
            if(i >= images.length) i = 0;
            slides.style.transform = `translateX(-${i * 100}%)`;
            index = i;
        }

        prevBtn.addEventListener('click', () => { showSlide(index - 1); resetInterval(); });
        nextBtn.addEventListener('click', () => { showSlide(index + 1); resetInterval(); });

        let autoSlide = setInterval(() => showSlide(index + 1), 4000);
        function resetInterval() {
            clearInterval(autoSlide);
            autoSlide = setInterval(() => showSlide(index + 1), 4000);
        }
    });
});
</script>

</body>
</html>
