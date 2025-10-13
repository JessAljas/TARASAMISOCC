<?php
// Static nga services array
$services = [
    ['title'=>'Local Tour Packages','description'=>'Experience curated day tours around Misamis Occidental’s top tourist destinations.','icon'=>'fas fa-map-marked-alt','rating'=>4.5],
    ['title'=>'Transportation Included','description'=>'We have a fully aircon van transportation where arranged by travel agencies and is already part of the package.','icon'=>'fas fa-bus','rating'=>5],
    ['title'=>'Professional Tour Guides','description'=>'Knowledgeable local tour guides will assist you throughout your trip to make it fun and educational.','icon'=>'fas fa-user-tie','rating'=>5]
];

// Feedbacks nga naka static rasab
$feedbacks = [
    ['name'=>'Tourism_Ozamiz City','img'=>'img/feedbacker1.jpg','comment'=>'Very reliable partner!','stars'=>4],
    ['name'=>'Tourism-Oroquieta City','img'=>'img/feedbacker2.png','comment'=>'Excellent experience!','stars'=>5],
    ['name'=>'Tourism_Tangub City','img'=>'img/feedbacker3.jpg','comment'=>'Team is responsive.','stars'=>4]
];

// Helper function para sa star ratings
function renderStars($rating){
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars >= 0.5)?1:0;
    $emptyStars = 5 - $fullStars - $halfStar;
    $starsHtml = '';
    for ($i=0;$i<$fullStars;$i++) $starsHtml .= '<i class="fas fa-star text-yellow-400"></i>';
    if($halfStar) $starsHtml .= '<i class="fas fa-star-half-alt text-yellow-400"></i>';
    for ($i=0;$i<$emptyStars;$i++) $starsHtml .= '<i class="far fa-star text-yellow-400"></i>';
    return $starsHtml;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>Services | Tara sa Mis.Occ</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous"/>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
<link rel="stylesheet" href="config/css/style2.css">
</head>
<body class="bg-gray-100 font-[Poppins]">

   <?php include 'config/include/header.php'; ?>

<!-- Services nga Section -->
<section class="py-12 px-6 text-center">
  <h1 class="text-3xl font-bold text-blue-900 mb-4">Our Services</h1>
  <p class="max-w-2xl mx-auto text-gray-700 text-base sm:text-lg mb-10">
    Explore exciting day tours in Misamis Occidental! All packages include transportation and follow fixed itineraries.
  </p>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach($services as $service): ?>
        <div class="bg-white p-6 rounded-lg shadow-md transition-transform transform hover:-translate-y-2 hover:shadow-xl">
            <i class="<?= $service['icon'] ?> text-blue-800 text-3xl mb-4"></i>
            <h2 class="text-xl font-semibold mb-2"><?= $service['title'] ?></h2>
            <div class="mb-2"><?= renderStars($service['rating']) ?></div>
            <p class="text-gray-600 mb-4"><?= $service['description'] ?></p>
        </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- Feedback nga Section -->
<section class="bg-white py-12 px-4">
  <h2 class="text-2xl sm:text-3xl font-bold text-center text-blue-900 mb-8">What Our Partners Say</h2>
  <div class="swiper mySwiper max-w-6xl mx-auto">
    <div class="swiper-wrapper">
      <?php foreach($feedbacks as $fb): ?>
        <div class="swiper-slide bg-blue-50 p-6 rounded-xl shadow-md text-center">
          <img src="<?= $fb['img'] ?>" class="w-20 h-20 rounded-full mx-auto mb-4 object-cover"/>
          <h3 class="text-lg font-semibold text-blue-900"><?= $fb['name'] ?></h3>
          <p class="text-sm text-gray-600 italic mb-2">"<?= $fb['comment'] ?>"</p>
          <div class="text-yellow-400 text-sm"><?php for($i=0;$i<5;$i++) echo $i<$fb['stars']?'★':'☆'; ?></div>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="swiper-pagination mt-4"></div>
  </div>
</section>

<!-- About or History nga Section -->
<section class="bg-blue-30 py-16 px-6">
  <div class="max-w-5xl mx-auto text-center">
    <h2 class="text-3xl font-bold text-blue-900 mb-6">About Tara sa MisOcc</h2>
    <p class="text-gray-700 text-lg leading-relaxed mb-4">
      <strong>Tara sa MisOcc</strong> is a tourism platform founded to promote the natural beauty, 
      culture, and heritage of Misamis Occidental. It was built in collaboration with local 
      agencies, tour operators, and the provincial tourism office to provide travelers with 
      seamless booking experiences and authentic adventures.
    </p>
    <p class="text-gray-700 text-lg leading-relaxed mb-4">
      The platform highlights destinations across Ozamiz City, Oroquieta City, Tangub City, 
      and nearby municipalities-featuring tour packages that include transportation, guided tours, 
      and curated itineraries. 
    </p>
<p class="text-orange-500 text-lg leading-relaxed">
  "Our mission: Is to connect tourists with the wonders of Misamis Occidental while 
  supporting local communities and small businesses. By choosing Tara sa MisOcc, 
  you don’t just book a trip—you become part of a movement that celebrates local 
  tourism, culture, and sustainability". <br><br>

  <span class="calligra-text">
    Misamisnon Magpuyong Malinawon Malamboon ug Malipayon! <br>
    (Misamisnon: Let us live peacefully, harmoniously, and happily!)
  </span>
</p>
  </div>
</section>

<?php include 'config/include/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script src="js/package.js"></script>

</body>
</html>
