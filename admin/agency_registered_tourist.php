  <?php
  session_start();
  include '../config/db_connect.php';

  // Redirect if not logged in as admin/agency
  if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
      header("Location: login.php");
      exit;
  }

  // ================== DELETE TOURIST ==================
  if (isset($_GET['delete'])) {
      $delete_id = intval($_GET['delete']);
      $stmt = $conn->prepare("DELETE FROM tourists WHERE id = ?");
      $stmt->bind_param("i", $delete_id);
      $stmt->execute();
      $stmt->close();
      header("Location: agency_registered_tourist.php");
      exit;
  }

  // ================== ADD/EDIT TOURIST ==================
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $id = !empty($_POST['update_id']) ? intval($_POST['update_id']) : null;
      $fullname = trim($_POST['fullname']);
      $email = trim($_POST['email']);
      $phone = trim($_POST['phone_number']);
      $address = trim($_POST['address']);
      $password = trim($_POST['password']);

      if ($id) {
          $updateFields = "fullname=?, email=?, phone_number=?, address=?";
          $types = "ssss";
          $params = [$fullname, $email, $phone, $address];

          if (!empty($password)) {
              $updateFields .= ", password=?";
              $types .= "s";
              $params[] = password_hash($password, PASSWORD_DEFAULT);
          }

          $types .= "i";
          $params[] = $id;

          $stmt = $conn->prepare("UPDATE tourists SET $updateFields WHERE id=?");
          $stmt->bind_param($types, ...$params);
          $stmt->execute();
          $stmt->close();
      } else {
          if (empty($password)) die(json_encode(['status' => 'error', 'message' => 'Password required']));
          $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
          $stmt = $conn->prepare("INSERT INTO tourists (fullname, email, phone_number, address, password, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
          $stmt->bind_param("sssss", $fullname, $email, $phone, $address, $hashed_pass);
          $stmt->execute();
          $id = $stmt->insert_id;
          $stmt->close();
      }

      echo json_encode([
          'status' => 'success',
          'id' => $id,
          'fullname' => $fullname,
          'email' => $email,
          'phone' => $phone,
          'address' => $address
      ]);
      exit;
  }

  // ================== PAGINATION + SEARCH ==================
  $limit = 8;
  $page = max(1, intval($_GET['page'] ?? 1));
  $offset = ($page - 1) * $limit;

  $search = trim($_GET['search'] ?? '');
  $where = '';
  $params = [];
  $param_types = '';

  if (!empty($search)) {
      $where = "WHERE fullname LIKE ? OR email LIKE ? OR address LIKE ?";
      $searchTerm = "%$search%";
      $params = [$searchTerm, $searchTerm, $searchTerm];
      $param_types = "sss";
  }

  // Total tourists
  if ($where) {
      $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM tourists $where");
      $stmt->bind_param($param_types, ...$params);
      $stmt->execute();
      $total_tourists = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
      $stmt->close();
  } else {
      $total_tourists = $conn->query("SELECT COUNT(*) AS total FROM tourists")->fetch_assoc()['total'] ?? 0;
  }

  // Fetch tourists
  if ($where) {
      $query = "SELECT * FROM tourists $where ORDER BY created_at DESC LIMIT ?, ?";
      $stmt = $conn->prepare($query);
      $stmt->bind_param($param_types . "ii", ...array_merge($params, [$offset, $limit]));
  } else {
      $stmt = $conn->prepare("SELECT * FROM tourists ORDER BY created_at DESC LIMIT ?, ?");
      $stmt->bind_param("ii", $offset, $limit);
  }
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();

  $total_pages = ceil($total_tourists / $limit);
  ?>

  <!DOCTYPE html>
  <html lang="en">
  <head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Registered Tourists | Tara sa Mis.Occ</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"/>
  <style>
  .modal { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 50; }
  .modal-content { background: #fff; padding: 20px; border-radius: 8px; max-width: 500px; width: 100%; box-shadow: 0 10px 25px rgba(0,0,0,0.3);}
  </style>
  </head>
  <body class="flex min-h-screen bg-gray-100 font-[Poppins]">

  <?php include 'sidebar.php'; ?>

  <div class="flex-1 p-6 md:ml-64">

  <!-- Stats Card -->
  <div class="max-w-sm mx-auto bg-gradient-to-r from-yellow-300 to-green-400 shadow rounded-lg p-3 text-center mb-6 text-white">
    <div class="flex justify-center mb-3"><i class="fas fa-users text-4xl"></i></div>
    <h2 class="text-lg font-semibold">Total Registered Tourists</h2>
    <p class="text-3xl font-bold mt-2"><?= $total_tourists ?></p>
  </div>

  <div class="flex items-center justify-between mb-6">
    <form method="GET" class="flex space-x-2">
      <button type="submit" class="px-4 py-2 text-black rounded">Search:</button> 
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search tourists..." class="p-2 border rounded">
    </form>

    <button onclick="openModal()" class="bg-gradient-to-r from-yellow-400 to-green-400 text-white px-4 py-2 rounded hover:from-orange-500 hover:to-green-500">+ Add Tourist</button>
  </div>

  <div id="successMessage" class="hidden text-green-700 text-center mb-4 font-semibold"></div>

  <!-- Table -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
  <table class="min-w-full divide-y divide-gray-200 text-sm">
    <thead class="bg-gray-200">
      <tr>
        <th class="px-3 py-2 text-left">Full Name</th>
        <th class="px-3 py-2 text-left">Email</th>
        <th class="px-3 py-2 text-left">Phone</th>
        <th class="px-3 py-2 text-left">Address</th>
        <th class="px-3 py-2 text-left">Registered</th>
        <th class="px-3 py-2 text-left">Actions</th>
      </tr>
    </thead>
    <tbody class="bg-white divide-y divide-gray-200">
      <?php while ($row = $res->fetch_assoc()): ?>
        <tr>
          <td class="px-3 py-2 max-w-xs truncate" title="<?= htmlspecialchars($row['fullname']) ?>">
            <?= htmlspecialchars($row['fullname']) ?>
          </td>
          <td class="px-3 py-2 max-w-xs truncate" title="<?= htmlspecialchars($row['email']) ?>">
            <?= htmlspecialchars($row['email']) ?>
          </td>
          <td class="px-3 py-2 max-w-xs truncate" title="<?= htmlspecialchars($row['phone_number']) ?>">
            <?= htmlspecialchars($row['phone_number']) ?>
          </td>
          <td class="px-3 py-2 max-w-xs truncate" title="<?= !empty($row['address']) ? htmlspecialchars($row['address']) : 'N/A' ?>">
            <?= !empty($row['address']) ? htmlspecialchars($row['address']) : 'N/A' ?>
          </td>
          <td class="px-3 py-2">
            <?= date('M d, Y', strtotime($row['created_at'])) ?>
          </td>
          <td class="px-3 py-2">
            <div class="flex space-x-2">

    <!-- Edit Button -->
    <button class="flex items-center bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-sm edit-btn"
      data-id="<?= $row['id'] ?>"
      data-fullname="<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>"
      data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>"
      data-phone="<?= htmlspecialchars($row['phone_number'], ENT_QUOTES) ?>"
      data-address="<?= htmlspecialchars($row['address'], ENT_QUOTES) ?>">
      <i class="fas fa-edit mr-1"></i> Edit
    </button>

    <!-- Delete Button -->
<button 
  class="deleteBtn flex items-center bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm"
  data-id="<?= $row['id'] ?>"
  data-name="<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>">
  <i class="fas fa-trash mr-1"></i> Delete
</button>
    </div>
        </td>
      </tr>
    <?php endwhile; ?>
  </tbody>
  </table>
  </div>
  <div id="deleteModal" class="modal hidden">
  <div class="modal-content">
    <h3 class="text-lg font-semibold mb-4">Confirm Delete</h3>
    <p id="deleteModalText">Are you sure you want to delete this tourist?</p>
    <div class="flex justify-end mt-4 gap-2">
      <button onclick="closeDeleteModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
      <a href="#" id="confirmDeleteBtn" class="px-4 py-2 rounded bg-red-500 text-white hover:bg-red-600">Delete</a>
    </div>
  </div>
</div>

  <!-- Pagination -->
  <div class="flex justify-center mt-6 space-x-2">
  <?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded bg-gray-100">Previous</a>
  <?php endif; ?>
  <?php if ($page < $total_pages): ?>
    <a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded bg-gray-100">Next</a>
  <?php endif; ?>
  </div>

  </div>

  <!-- Add/Edit Modal -->
  <div id="modal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50 hidden">
    <div class="bg-white p-5 rounded-lg max-w-md w-full max-h-[80vh] overflow-y-auto shadow-lg">
      <h2 id="modal-title" class="text-lg font-semibold mb-4">Add Tourist</h2>
      <form method="POST" class="space-y-4">
        <input type="hidden" name="update_id" id="update_id">
        <div><label>Full Name</label><input type="text" name="fullname" id="fullname" class="w-full p-2 border rounded" required></div>
        <div><label>Email</label><input type="email" name="email" id="email" class="w-full p-2 border rounded" required></div>
        <div><label>Phone</label><input type="text" name="phone_number" id="phone_number" class="w-full p-2 border rounded"></div>
        <div><label>Address</label><textarea name="address" id="address" class="w-full p-2 border rounded"></textarea></div>
        <div>
          <label>Password</label>
          <div class="flex items-center">
            <input type="password" name="password" id="password" class="w-full p-2 border rounded" placeholder="Enter password">
            <button type="button" onclick="togglePassword()" class="ml-2 px-2 py-1 border rounded">
              <i id="toggleIcon" class="fa fa-eye"></i>
            </button>
          </div>
          <p class="text-xs text-gray-500">Password</p>
        </div>
        <div class="flex justify-end space-x-2 mt-4">
          <button type="button" onclick="closeModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
          <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600" id="modal-submit">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script src=isset/regtourist_spot.js></script>
  </body>
  </html>
