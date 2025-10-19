<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tara sa MisOcc.com</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="config/css/style.css">
</head>
<body class="bg-gradient-to-b from-green-50 via-green-100 to-white min-h-screen flex flex-col font-[Poppins]">

<!-- Main content -->
<main class="flex-1 flex flex-col items-center justify-center px-5 py-16 space-y-16">

  <div class="flex flex-col-reverse lg:flex-row items-center justify-between max-w-6xl w-full gap-12">

    <!-- Text Section (Left) -->
    <div class="flex flex-col items-start gap-6 fade-in-up max-w-xl lg:max-w-2xl">
      <h1 class="text-3xl sm:text-4xl md:text-5xl font-extrabold text-gradient">
        Tara sa Mis Occ!
      </h1>
      <p class="text-gray-700 text-lg sm:text-xl leading-relaxed">
        <strong>Tara sa Mis.Occ</strong> makes exploring Misamis Occidental seamless, fast, and enjoyable. Whether you're a tourist seeking exciting destinations, a local business promoting services, or an agency managing tour packages, our platform brings everything together in one place.
        <br><br>
        Plan trips with real-time booking, interactive maps, curated itineraries, and secure payments. Discover hidden gems, book packages instantly, and create unforgettable memories with ease.
      </p>

      <!-- Top Links for Privacy & Terms -->
      <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-4 text-sm text-gray-700 w-full">
        <button onclick="openModal('privacyModal')" class="hover:text-green-800 underline">Privacy Policy</button>
        <span class="hidden sm:inline">|</span>
        <button onclick="openModal('termsModal')" class="hover:text-orange-800 underline">Terms & Conditions</button>
      </div>

      <!-- Learn More Button -->
      <a href="login.php" 
         class="bg-green-600 text-white px-8 py-3 rounded-full font-semibold hover:bg-green-700 shadow-lg transition transform hover:scale-105 inline-block text-center mt-4">
        Learn More <i class="fas fa-arrow-right ml-2"></i>
      </a>
    </div>

 <!-- Logo Section (Right) -->
<div class="fade-in-up flex justify-center lg:justify-end w-full lg:w-1/2 relative mt-8 lg:mt-0">
  <img src="img/logo.png" alt="Main Logo" 
       class="w-64 h-64 sm:w-72 sm:h-72 md:w-80 md:h-80 lg:w-96 lg:h-96 rounded-full shadow-2xl border-4 border-green-200 object-cover bounce-slow">
  <img src="img/bee-logo.png" alt="Bee Logo" 
       class="absolute bottom-4 right-6 w-20 h-20 sm:w-24 sm:h-24 animate-bounce">
</div>
</main>


<!-- Footer -->
<footer class="bg-green-600 py-3 mt-auto w-full">
  <div class="max-w-6xl mx-auto px-5 flex flex-col items-center gap-4 text-center">
    <div class="flex flex-col items-center space-y-2">
      <div class="flex items-center space-x-3">
      </div>
      <span class="font-bold text-xl text-white">Tara sa MisOcc</span>
    </div>

    <div class="flex flex-wrap justify-center gap-4 text-white text-base">
      <a href="index.php" class="hover:text-blue-700 transition">Home</a>
      <a href="login.php" class="hover:text-blue-700 transition">Explore</a>
      <a href="login.php" class="hover:text-blue-700 transition">Packages</a>
      <a href="login.php" class="hover:text-blue-700 transition">Contact</a>
    </div>

    <div class="flex space-x-4 text-xl text-white">
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-facebook-f"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-twitter"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-instagram"></i></a>
      <a href="#" class="hover:text-blue-700 transition"><i class="fa-brands fa-youtube"></i></a>
    </div>
  </div>

  <div class="mt-4 text-center text-white text-sm">
    &copy; 2025 Tara sa MisOcc. All rights reserved.
  </div>
</footer>

  <!-- Privacy Policy Modal -->
  <div id="privacyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg max-w-3xl w-full p-6 relative overflow-y-auto max-h-[90vh]">
      <button onclick="closeModal('privacyModal')" class="absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-xl">&times;</button>
    <!-- Icon + Title -->
  <div class="flex flex-col items-center justify-center gap-3 mb-4">
    <i class="fa-solid fa-shield-halved text-green-600 text-4xl"></i>
    <h2 class="text-2xl font-bold text-center text-green-800">Privacy Policy</h2>
  </div>
      <p class="text-sm text-gray-700 leading-relaxed">
        We respect your privacy. All personal information collected during registration, booking, and payments will be securely stored and used solely for providing services through Tara sa Mis.Occ.
        <br><br>
        By using our platform, you agree to the collection and use of information as described in this policy. We do not share your data with third parties without consent except as required by law.
      </p>
    </div>
  </div>

<!-- Terms & Conditions Modal -->
<div id="termsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg max-w-3xl w-full p-6 relative overflow-y-auto max-h-[90vh]">
    
    <!-- Close Button -->
    <button onclick="closeModal('termsModal')" class="absolute top-3 right-3 text-gray-700 hover:text-gray-900 text-xl">&times;</button>
    
    <!-- Icon + Title -->
    <div class="flex flex-col items-center justify-center gap-3 mb-4">
      <i class="fa-solid fa-file-contract text-orange-600 text-4xl"></i>
      <h2 class="text-2xl font-bold text-center text-orange-800">Terms & Conditions</h2>
    </div>

    <!-- Terms List -->
    <ul class="text-sm text-orange-900 space-y-2 list-disc list-inside">
      <li><i class="fa-solid fa-circle-info"></i> Booking may only be guaranteed once payment has been made.</li>
      <li><i class="fa-solid fa-circle-info"></i> Itinerary might CHANGE without prior notice according to local conditions.</li>
      <li><i class="fa-solid fa-circle-info"></i> We have the right to CANCEL or RESCHEDULE any trip under the following circumstances:
        <ul class="list-disc list-inside ml-5 text-orange-800">
          <li>Lack of joiners in a scheduled trip</li>
          <li>In case of natural calamity or typhoon reports compromising passenger safety</li>
        </ul>
      </li>
      <li><i class="fa-solid fa-circle-info"></i> Travel organizer / Management is not responsible for accidents, lost or damaged items, or valuables left behind.</li>
      <li class="text-red-600 font-semibold"><i class="fa-solid fa-circle-info"></i> NO payment, NO reservation.</li>
      <li class="text-red-600 font-semibold"><i class="fa-solid fa-circle-info"></i> All payments are NON-REFUNDABLE.</li>
      <li><i class="fa-solid fa-circle-info"></i> By using our platform, means you agree with our Terms & Conditions. We do <strong>NOT</strong> offer refunds under any circumstances.</li>
    </ul>
  </div>
</div>


<!-- Font Awesome CDN -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<script>
  function openModal(id) {
    document.getElementById(id).classList.remove('hidden');
    document.getElementById(id).classList.add('flex');
  }
  function closeModal(id) {
    document.getElementById(id).classList.remove('flex');
    document.getElementById(id).classList.add('hidden');
  }
</script>

</body>
</html>
