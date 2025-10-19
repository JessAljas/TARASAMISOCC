    <?php
    session_start();
    include '../config/db_connect.php';

    // Redirect if not logged in as admin/agency
    if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'agency'])) {
        header("Location: login.php");
        exit;
    }

    $error = '';
    $success = '';

    // ==================== FETCH TOURIST SPOTS ==================== //
    $spots = [];
    $res = $conn->query("SELECT id, name_of_tourist_spot, location, latitude, longitude FROM tourist_spots ORDER BY name_of_tourist_spot");
    while ($row = $res->fetch_assoc()) {
        $spots[] = $row;
    }

    // ==================== HANDLE PACKAGE SUBMISSION ==================== //
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['title']);
        $price = $_POST['price'];
        $description = trim($_POST['description']);
        $pickup = trim($_POST['pickup']);
        $dropoff = trim($_POST['dropoff']);
      

        // Decode selected destinations
        $destinations = !empty($_POST['selected_destinations']) ? json_decode($_POST['selected_destinations'], true) : [];

        // Inclusions and Exclusions
        $inclusions = [];
        $exclusions = [];
        for ($i = 1; $i <= 4; $i++) {
            $inclusions[] = trim($_POST["inclusion$i"] ?? '');
            $exclusions[] = trim($_POST["exclusion$i"] ?? '');
        }

        if (count($destinations) > 4) {
            $error = "Maximum of 4 destinations only.";
        } else {
            // ==================== UPLOAD IMAGES ==================== //
            $uploadedImages = [];
            $targetDir = "../uploads/";
            for ($i = 0; $i < 4; $i++) {
                if (!empty($_FILES['images']['name'][$i])) {
                    $fileName = time() . "_" . basename($_FILES['images']['name'][$i]);
                    $targetFilePath = $targetDir . $fileName;
                    $uploadedImages[] = move_uploaded_file($_FILES['images']['tmp_name'][$i], $targetFilePath) ? $fileName : null;
                } else {
                    $uploadedImages[] = null;
                }
            }
            while (count($uploadedImages) < 4) {
                $uploadedImages[] = null;
            }

            // Assign variables for bind_param
            $pickup_location = $pickup;
            $dropoff_location = $dropoff;
            $image1 = $uploadedImages[0];
            $image2 = $uploadedImages[1];
            $image3 = $uploadedImages[2];
            $image4 = $uploadedImages[3];
            $inclusion1 = $inclusions[0];
            $inclusion2 = $inclusions[1];
            $inclusion3 = $inclusions[2];
            $inclusion4 = $inclusions[3];
            $exclusion1 = $exclusions[0];
            $exclusion2 = $exclusions[1];
            $exclusion3 = $exclusions[2];
            $exclusion4 = $exclusions[3];

            $posted_by_type = $_SESSION['user']['role'];
            $created_at = date("Y-m-d H:i:s");
            $status = 'pending';

            // ==================== INSERT PACKAGE (with lunch_location) ==================== //
            $stmt = $conn->prepare("INSERT INTO packages (
                title, price, pickup_location, dropoff_location, description,
                image1, image2, image3, image4,
                inclusion1, inclusion2, inclusion3, inclusion4,
                exclusion1, exclusion2, exclusion3, exclusion4,
                posted_by_type, created_at, status
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"); 

            if ($stmt) {
                $stmt->bind_param(
                    "sdssssssssssssssssss",
                    $title, $price, $pickup_location, $dropoff_location, $description,
                    $image1, $image2, $image3, $image4,
                    $inclusion1, $inclusion2, $inclusion3, $inclusion4,
                    $exclusion1, $exclusion2, $exclusion3, $exclusion4,
                    $posted_by_type, $created_at, $status
                );

                if ($stmt->execute()) {
                    $package_id = $stmt->insert_id;
                    $stmt->close();

                    // ==================== INSERT DESTINATIONS ==================== //
                    $destinationsData = [];
                    foreach ($destinations as $order => $spot_id) {
                        $stmtDest = $conn->prepare("INSERT INTO package_destinations (package_id, tourist_spot_id, stop_order) VALUES (?, ?, ?)");
                        $stop_order = $order + 1;
                        $stmtDest->bind_param("iii", $package_id, $spot_id, $stop_order);
                        $stmtDest->execute();
                        $stmtDest->close();

                        $spotRes = $conn->prepare("SELECT name_of_tourist_spot FROM tourist_spots WHERE id=?");
                        $spotRes->bind_param("i", $spot_id);
                        $spotRes->execute();
                        $spotRow = $spotRes->get_result()->fetch_assoc();
                        $destinationsData[] = ['name' => $spotRow['name_of_tourist_spot']];
                        $spotRes->close();
                    }

    // ==================== AUTO-GENERATE ITINERARY ==================== //
    // Set default values if not provided
    $pickup  = !empty($pickup) ? $pickup : 'Municipality of Plaridel';
    $dropoff = !empty($dropoff) ? $dropoff : 'Municipality of Plaridel';
    $lunch   = !empty($_POST['lunch']) ? trim($_POST['lunch']) : 'Lunch Break Location';

    $time         = strtotime("08:00 AM");
    $travelFirst  = 1800; // 30 mins
    $travelNext   = 1200; // 20 mins
    $visitTime    = 3600; // 1 hr
    $lunchTime    = 3600; // 1 hr
    $endTime      = strtotime("05:00 PM");

    $half = ceil(count($destinationsData) / 2);
    $morningDest = array_slice($destinationsData, 0, $half);
    $afternoonDest = array_slice($destinationsData, $half);

    $autoItinerary = [];

    // Pickup
    $autoItinerary[] = [
        'time' => date("h:i A", $time),
        'activity_type' => 'pickup',
        'destination_name' => "Pickup at $pickup"
    ];

    // Morning destinations
    foreach ($morningDest as $i => $dest) {
        $destinationName = $dest['name'] ?? 'Unknown Destination';
        $time += ($i === 0) ? $travelFirst : $travelNext;
        $autoItinerary[] = ['time' => date("h:i A", $time), 'activity_type' => 'travel', 'destination_name' => "Travel to $destinationName"];
        $time += $visitTime;
        $autoItinerary[] = ['time' => date("h:i A", $time), 'activity_type' => 'arrival', 'destination_name' => " $destinationName"];
    }

    // Lunch
    $time = strtotime("12:00 PM");
    $autoItinerary[] = ['time' => date("h:i A", $time), 'activity_type' => 'lunch', 'destination_name' => "Lunch at $lunch"];
    $time += $lunchTime;

    // Afternoon destinations
    foreach ($afternoonDest as $dest) {
        $destinationName = $dest['name'] ?? 'Unknown Destination';
        $time += $travelNext;
        $autoItinerary[] = ['time' => date("h:i A", $time), 'activity_type' => 'travel', 'destination_name' => "Travel to $destinationName"];
        $time += $visitTime;
        $autoItinerary[] = ['time' => date("h:i A", $time), 'activity_type' => 'arrival', 'destination_name' => "Visit $destinationName"];
    }

    // Drop-off
    $autoItinerary[] = ['time' => date("h:i A", $endTime), 'activity_type' => 'dropoff', 'destination_name' => "Drop-off at $dropoff"];


                    // ==================== SAVE AUTO ITINERARY ==================== //
                    if (!empty($autoItinerary)) {
                        $stmtItin = $conn->prepare("INSERT INTO itinerary (package_id, destination_name, time, activity_type, created_at) VALUES (?, ?, ?, ?, NOW())");
                        foreach ($autoItinerary as $item) {
                            $time_formatted = date("H:i:s", strtotime($item['time']));
                            $destination_name = $item['destination_name'];
                            $activity_type = $item['activity_type'];
                            $stmtItin->bind_param("isss", $package_id, $destination_name, $time_formatted, $activity_type);
                            $stmtItin->execute();
                        }
                        $stmtItin->close();
                    }

                    $success = "✅ Package and itinerary posted successfully!";
                } else {
                    $error = "❌ Error posting package: " . $stmt->error;
                }
            } else {
                $error = "❌ Prepare statement failed: " . $conn->error;
            }
        }
    }
    ?>




    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8">
    <title>Add Tour Package</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
   
    <script>
    window.onload = function(){
        const max = 4;
        const checkboxes = document.querySelectorAll('input[name="destinations[]"]');
        const hiddenField = document.getElementById('selectedDestinations');
        let selected = [];

        checkboxes.forEach(cb => cb.addEventListener('change', function(){
            if(this.checked){
                if(selected.length >= max){
                    alert("Maximum 4 destinations");
                    this.checked=false;
                    return;
                }
                selected.push(this.value);
            } else {
                selected = selected.filter(v => v !== this.value);
            }
            hiddenField.value = JSON.stringify(selected);
        }));
    };
    </script>
    </head>
    <body class="bg-gray-100 flex font-[Poppins]">
  
    <?php include 'sidebar.php'; ?>

    <div id="mainContent" class="flex-1">
    <main class="max-w-5xl mx-auto mt-1">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-gray-800 flex items-center space-x-3">
        <span>Add New Tour Packages</span>
      </h1>
    <a href="agency_manage_packages.php" class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-md shadow flex items-center gap-2">
        <i class="fas fa-folder"></i> Manage Packages
    </a>
    </div>

    <div class="card">
    <?php if($error): ?>
    <p id="message" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-red-600  text-center">
        <?= htmlspecialchars($error) ?>
    </p>
    <?php endif; ?>

    <?php if($success): ?>
    <p id="message" class="fixed top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-green-600 text-center">
        <?= htmlspecialchars($success) ?>
    </p>
    <?php endif; ?>

    <script>
        // Remove message after sa 6 seconds
        setTimeout(() => {
            const msg = document.getElementById('message');
            if(msg) {
                msg.remove();
            }
        }, 6000);
    </script>
    <form method="POST" enctype="multipart/form-data" class="space-y-6">

      <!-- Package Title -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Package Title</label>
        <input type="text" name="title" required 
              class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
      </div>

      <!-- Price -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Price (₱)</label>
        <input type="number" step="0.01" name="price" required 
              class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
      </div>

      <!-- Pickup, Dropoff, Description -->
        <div>
          <label class="block mb-2 font-semibold text-gray-700">Package Description</label>
          <textarea name="description" rows="4" required 
                    class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400"
                    placeholder="Write a short description about this package..."></textarea>
        </div>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <label class="block mb-2 font-semibold text-gray-700">Pickup Location</label>
          <input type="text" name="pickup" required placeholder="Enter pickup location" 
                class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>

        <div>
          <label class="block mb-2 font-semibold text-gray-700">Dropoff Location</label>
          <input type="text" name="dropoff" required placeholder="Enter dropoff location" 
                class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
        </div>
      </div>

      <!-- Inclusions -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Inclusions (max 4)</label>
        <div class="grid grid-cols-2 gap-4">
          <?php for($i=1; $i<=4; $i++): ?>
            <input type="text" name="inclusion<?= $i ?>" placeholder="Inclusion <?= $i ?>" 
                  class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
          <?php endfor; ?>
        </div>
      </div>

      <!-- Exclusions -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Exclusions (max 4)</label>
        <div class="grid grid-cols-2 gap-4">
          <?php for($i=1; $i<=4; $i++): ?>
            <input type="text" name="exclusion<?= $i ?>" placeholder="Exclusion <?= $i ?>" 
                  class="w-full border border-gray-300 rounded px-4 py-2 focus:outline-none focus:ring-2 focus:ring-green-400">
          <?php endfor; ?>
        </div>
      </div>

      <!-- Destinations -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Select Destinations (max 4)</label>
        <div class="checkbox-grid grid grid-cols-2 gap-2">
          <?php foreach($spots as $spot): ?>
            <label class="flex items-center space-x-2 p-2 border border-gray-200 rounded hover:bg-green-50 cursor-pointer">
              <input type="checkbox" name="destinations[]" value="<?= $spot['id'] ?>" class="h-4 w-4 text-green-500">
              <span class="text-gray-700"><?= htmlspecialchars($spot['name_of_tourist_spot']) ?></span>
            </label>
          <?php endforeach; ?>
        </div>
        <input type="hidden" name="selected_destinations" id="selectedDestinations">
      </div>

      <!-- Images -->
      <div>
        <label class="block mb-2 font-semibold text-gray-700">Upload Images (Max 4)</label>
        <div class="grid grid-cols-2 gap-4">
          <?php for($i=0; $i<4; $i++): ?>
            <input type="file" name="images[<?= $i ?>]" accept="image/*" class="w-full">
          <?php endfor; ?>
        </div>
        <small class="text-gray-500">You can upload up to 4 images.</small>
      </div>

      <!-- Submit Button -->
      <button type="submit" 
              class="bg-green-500 hover:bg-green-600 text-white px-6 py-3 rounded-md shadow font-semibold w-full">
        Post Package
      </button>

    </form>
    </form>
    </div>
    </main>
    </div>
    </body>
    </html>
