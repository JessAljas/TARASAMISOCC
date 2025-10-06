<?php
session_start();
include 'db_connect.php';

// mo direct dri if wala  ka login as agency
if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','agency'])) {
    header("Location: login.php");
    exit;
}

// ================== HANDLE DELETE CODE BACKEND ==================
if (isset($_GET['delete'])) {
    $delete_id = intval($_GET['delete']);

    $stmt = $conn->prepare("SELECT profile_image FROM tourists WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    $stmt->execute();
    $img_res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($img_res) {
        if (!empty($img_res['profile_image']) && file_exists($img_res['profile_image'])) {
            unlink($img_res['profile_image']);
        }

        $stmt = $conn->prepare("DELETE FROM tourists WHERE id = ?");
        $stmt->bind_param("i", $delete_id);
        $stmt->execute();
        $stmt->close();

        header("Location: agency_registered_tourist.php");
        exit;
    }
}

// ================== HANDLE ADD/EDIT CODE BACKEND ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = isset($_POST['update_id']) && !empty($_POST['update_id']) ? intval($_POST['update_id']) : null;
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $password = trim($_POST['password']); // if mag chnage password mao ni fiel new.

    $profile_image = null;
    if (!empty($_FILES['profile_image']['name'])) {
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $profile_image = 'uploads/profile_' . time() . '.' . $ext;
        move_uploaded_file($_FILES['profile_image']['tmp_name'], $profile_image);

        if ($id) {
            $stmt = $conn->prepare("SELECT profile_image FROM tourists WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $old = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if ($old && !empty($old['profile_image']) && file_exists($old['profile_image'])) {
                unlink($old['profile_image']);
            }
        }
    }

    if ($id) {
        // UPDATE sa existing tourist info
        if (!empty($password)) {
            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            if ($profile_image) {
                $stmt = $conn->prepare("UPDATE tourists SET fullname=?, email=?, phone_number=?, address=?, profile_image=?, password=? WHERE id=?");
                $stmt->bind_param("ssssssi", $fullname, $email, $phone, $address, $profile_image, $hashed_pass, $id);
            } else {
                $stmt = $conn->prepare("UPDATE tourists SET fullname=?, email=?, phone_number=?, address=?, password=? WHERE id=?");
                $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $hashed_pass, $id);
            }
        } else {
            if ($profile_image) {
                $stmt = $conn->prepare("UPDATE tourists SET fullname=?, email=?, phone_number=?, address=?, profile_image=? WHERE id=?");
                $stmt->bind_param("sssssi", $fullname, $email, $phone, $address, $profile_image, $id);
            } else {
                $stmt = $conn->prepare("UPDATE tourists SET fullname=?, email=?, phone_number=?, address=? WHERE id=?");
                $stmt->bind_param("ssssi", $fullname, $email, $phone, $address, $id);
            }
        }
        $stmt->execute();
        $stmt->close();
    } else {
        // INSERT uG new tourist sa database
        if (empty($password)) {
            die("Password is required for new tourist!");
        }
        $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO tourists (fullname, email, phone_number, address, profile_image, password, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssss", $fullname, $email, $phone, $address, $profile_image, $hashed_pass);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: agency_registered_tourist.php");
    exit;
}

// ================== PAGINATION + SEARCH NGA CODE ==================
$limit = 8;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$where = '';
$params = [];
$param_types = '';

if (!empty($search)) {
    // search sa fullname, email, address
    $where = "WHERE fullname LIKE ? OR email LIKE ? OR address LIKE ?";
    $search_term = "%" . $search . "%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $param_types .= "sss";
}

// Count total NGA CODE
if ($where) {
    $count_sql = "SELECT COUNT(*) AS total FROM tourists $where";
    $stmt = $conn->prepare($count_sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $total_tourists = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
} else {
    $total_tourists = $conn->query("SELECT COUNT(*) AS total FROM tourists")->fetch_assoc()['total'] ?? 0;
}

// Fetch rows sa mga tourists
if ($where) {
    $query = "SELECT * FROM tourists $where ORDER BY created_at DESC LIMIT ?, ?";
    $stmt = $conn->prepare($query);

    $params_with_pagination = $params;
    $param_types_with_pagination = $param_types . "ii";
    $params_with_pagination[] = $offset;
    $params_with_pagination[] = $limit;

    $stmt->bind_param($param_types_with_pagination, ...$params_with_pagination);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
} else {
    $stmt = $conn->prepare("SELECT * FROM tourists ORDER BY created_at DESC LIMIT ?, ?");
    $stmt->bind_param("ii", $offset, $limit);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();
}

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
.modal { 
    position: fixed; 
    top: 0; left: 0; 
    width: 100%; height: 100%; 
    background: rgba(0,0,0,0.5); 
    display: flex; 
    justify-content: center; 
    align-items: center; 
    z-index: 50; 
}
.modal-content { 
    background: #fff; 
    padding: 20px; 
    border-radius: 8px; 
    max-width: 500px; 
    width: 100%; 
    box-shadow: 0 10px 25px rgba(0,0,0,0.3);
}

</style>
</head>
<body class="flex min-h-screen bg-gray-100 font-[Poppins]">

<?php include 'sidebar.php'; ?>

<div class="flex-1 p-6 md:ml-64">

<!-- Stats Card with Total -->
<div class="max-w-sm mx-auto bg-gradient-to-r from-yellow-300 to-green-400 shadow rounded-lg p-3 text-center mb-6 text-white">
  <div class="flex justify-center mb-3">
    <i class="fas fa-users text-4xl"></i>
  </div>
  <h2 class="text-lg font-semibold">Total Registered Tourists</h2>
  <p class="text-3xl font-bold mt-2"><?= $total_tourists ?></p>
</div>


<div class="flex items-center justify-between mb-6">
  <form method="GET" class="flex space-x-2">
    <button type="submit" class="px-4 py-2 text-black rounded">Search:</button> 
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="  🔍Search tourists..." class="p-2 border rounded">
  </form>

  <button onclick="openModal()" 
    class="bg-gradient-to-r from-yellow-400 to-green-400 text-white px-4 py-2 rounded hover:from-orange-500 hover:to-green-500">
    + Add Tourist
  </button>
</div>

  <!-- Table sa Registered nga Tourists -->
  <div class="overflow-x-auto bg-white shadow rounded-lg">
    <table class="min-w-full divide-y divide-gray-200">
      <thead class="bg-gray-200">
        <tr>
          <th class="px-6 py-3">Profile</th>
          <th class="px-6 py-3">Full Name</th>
          <th class="px-6 py-3">Email</th>
          <th class="px-6 py-3">Phone</th>
          <th class="px-6 py-3">Address</th>
          <th class="px-6 py-3">Registered</th>
          <th class="px-6 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="bg-white divide-y divide-gray-200">
        <?php while ($row = $res->fetch_assoc()): 
          $profile_img_path = (!empty($row['profile_image']) && file_exists($row['profile_image'])) 
                                ? $row['profile_image'] 
                                : "uploads/default.png";
        ?>
        <tr>
          <td class="px-6 py-4"><img src="<?= $profile_img_path ?>" class="w-12 h-12 rounded-full object-cover border"></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['fullname']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['email']) ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['phone_number']) ?></td>
          <td class="px-6 py-4"><?= !empty($row['address']) ? htmlspecialchars($row['address']) : 'N/A' ?></td>
          <td class="px-6 py-4"><?= date('M d, Y', strtotime($row['created_at'])) ?></td>
          <td class="px-6 py-4 space-x-2">
            <button 
              class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 text-sm edit-btn"
              data-id="<?= $row['id'] ?>"
              data-fullname="<?= htmlspecialchars($row['fullname'], ENT_QUOTES) ?>"
              data-email="<?= htmlspecialchars($row['email'], ENT_QUOTES) ?>"
              data-phone="<?= htmlspecialchars($row['phone_number'], ENT_QUOTES) ?>"
              data-address="<?= htmlspecialchars($row['address'], ENT_QUOTES) ?>"
              data-profile="<?= !empty($row['profile_image']) ? htmlspecialchars($row['profile_image'], ENT_QUOTES) : '' ?>"
            >Edit</button>

            <a href="agency_registered_tourist.php?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this tourist?');" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 text-sm">Delete</a>
          </td>
        </tr>
        <?php endwhile; ?>
      </tbody>
    </table>
  </div>

  <!-- Pagination code -->
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
<div id="modal" class="modal hidden">
  <div class="modal-content">
    <h2 id="modal-title" class="text-lg font-semibold mb-4">Add Tourist</h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-4">
      <input type="hidden" name="update_id" id="update_id">
      <div><label>Full Name</label><input type="text" name="fullname" id="fullname" class="w-full p-2 border rounded" required></div>
      <div><label>Email</label><input type="email" name="email" id="email" class="w-full p-2 border rounded" required></div>
      <div><label>Phone</label><input type="text" name="phone_number" id="phone_number" class="w-full p-2 border rounded"></div>
      <div><label>Address</label><textarea name="address" id="address" class="w-full p-2 border rounded"></textarea></div>
      
      <!--THE NEW PASSWORD FIELD WITH TOGGLE NGA CODE -->
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

      <div>
        <label>Profile Image</label>
        <div class="mb-2"><img id="current_profile_image" src="uploads/default.png" alt="Profile" class="w-20 h-20 rounded-full object-cover border"></div>
        <input type="file" name="profile_image" accept="image/*">
      </div>
      <div class="flex justify-end space-x-2">
        <button type="button" onclick="closeModal()" class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded bg-green-500 text-white hover:bg-green-600" id="modal-submit">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
// ====== CLOSE MODAL CODE ======
function closeModal() {
    const modal = document.getElementById("modal");
    modal.classList.add("hidden");
}

// ====== OPEN MODAL FUNCTION CODE======
function openModal(isEdit = false, data = {}) {
    const modal = document.getElementById("modal");
    const title = document.getElementById("modal-title");
    const submitBtn = document.getElementById("modal-submit");

    // Reset form
    const form = modal.querySelector("form");
    form.reset();
    document.getElementById("update_id").value = "";
    document.getElementById("current_profile_image").src = "uploads/default.png";

    if (isEdit) {
        title.textContent = "Edit Tourist";
        submitBtn.textContent = "Update";

        document.getElementById("update_id").value = data.id;
        document.getElementById("fullname").value = data.fullname;
        document.getElementById("email").value = data.email;
        document.getElementById("phone_number").value = data.phone;
        document.getElementById("address").value = data.address;

        if (data.profile) {
            document.getElementById("current_profile_image").src = data.profile;
        }
    } else {
        title.textContent = "Add Tourist";
        submitBtn.textContent = "Save";
    }

    modal.classList.remove("hidden");
}

// ====== PASSWORD TOGGLE ======
function togglePassword() {
    const field = document.getElementById("password");
    const icon = document.getElementById("toggleIcon");
    if (!field) return;

    if (field.type === "password") {
        field.type = "text";
        icon.classList.add("text-blue-500");
    } else {
        field.type = "password";
        icon.classList.remove("text-blue-500");
    }
}

// ====== EDIT BUTTON HANDLER NGA CODE ======
document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll(".edit-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            openModal(true, {
                id: btn.dataset.id,
                fullname: btn.dataset.fullname,
                email: btn.dataset.email,
                phone: btn.dataset.phone,
                address: btn.dataset.address,
                profile: btn.dataset.profile ? btn.dataset.profile : "uploads/default.png"
            });
        });
    });
});
</script>

</body>
</html>
