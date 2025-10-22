<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../config/db_connect.php';

// Ensure logged in
if (!isset($_SESSION['user']['id']) || ($_SESSION['user']['role'] ?? '') !== 'spot_owner') {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user']['id'];

// Fetch owner info
$stmt = $conn->prepare("SELECT * FROM spot_owners WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$owner = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_img = !empty($owner['profile_image'])
    ? '../uploads/' . htmlspecialchars($owner['profile_image'])
    : '../img/default_profile.png';
?>

<!-- HEADER -->
<header class="bg-green-500 text-white shadow p-6 flex justify-between items-center relative z-50">
    <h1 class="text-2xl font-bold">Tourist Spot Owner Dashboard</h1>

    <div class="relative">
        <button id="profileButton" type="button"
            class="flex items-center space-x-2 bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700 transition focus:outline-none">
            <img src="<?= $profile_img ?>" alt="Profile"
                class="w-8 h-8 rounded-full object-cover border-2 border-white">
            <span class="font-semibold"><?= htmlspecialchars($owner['fullname'] ?? 'Owner') ?></span>
            <i class="fas fa-caret-down"></i>
        </button>

        <!-- Dropdown -->
        <div id="profileDropdown"
            class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg overflow-hidden hidden transition-all duration-200">
            <button onclick="openHeaderModal('editModal')"
                class="w-full text-left px-5 py-3 font-semibold text-green-700 hover:bg-green-100 flex items-center">
                <i class="fas fa-user-edit mr-2 text-green-600"></i> Profile
            </button>
            <button onclick="openHeaderModal('messageModal')"
                class="w-full text-left px-5 py-3 font-semibold text-green-700 hover:bg-green-100 flex items-center">
                <i class="fas fa-envelope mr-2 text-green-600"></i> Send Inquiry
            </button>
            <button onclick="openHeaderModal('logoutModal')"
                class="w-full text-left px-5 py-3 font-semibold text-red-600 hover:bg-red-100 flex items-center">
                <i class="fas fa-sign-out-alt mr-2 text-red-500"></i> Logout
            </button>
        </div>
    </div>
</header>

<!-- ===================== -->
<!-- SEND INQUIRY MODAL -->
<!-- ===================== -->
<div id="messageModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-[1000] flex justify-center items-center transition-all">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-lg relative">
        <h2 class="text-xl font-semibold text-green-700 mb-4 flex items-center">
            <i class="fas fa-envelope mr-2 text-green-600"></i> Send Inquiry
        </h2>
        <form id="inquiryForm" method="POST">
            <input type="hidden" name="owner_id" value="<?= htmlspecialchars($owner['id']) ?>">
            <div class="mb-4">
                <label class="block text-left text-gray-700 font-semibold mb-1">Subject</label>
                <input type="text" name="subject" required placeholder="Enter subject..."
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>
            <div class="mb-4">
                <label class="block text-left text-gray-700 font-semibold mb-1">Message</label>
                <textarea name="message" required placeholder="Type your message..." rows="4"
                    class="w-full border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-green-400"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="closeHeaderModal('messageModal')"
                    class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400 transition">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Send</button>
            </div>
        </form>
        <button onclick="closeHeaderModal('messageModal')" class="absolute top-3 right-3 text-gray-500 hover:text-red-500">
            <i class="fas fa-times"></i>
        </button>
    </div>
</div>

<!-- ===================== -->
<!-- LOGOUT MODAL -->
<!-- ===================== -->
<div id="logoutModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-[1000] flex justify-center items-center transition-all">
    <div class="bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-md text-center">
        <h2 class="text-lg font-semibold mb-4 text-red-600">Confirm Logout</h2>
        <p class="mb-6">Are you sure you want to logout?</p>
        <div class="flex justify-center gap-4">
            <button onclick="closeHeaderModal('logoutModal')" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
            <a href="../config/logout.php"
                class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">Logout</a>
        </div>
    </div>
</div>

<script>
// Profile Dropdown Toggle
const profileButton = document.getElementById('profileButton');
const profileDropdown = document.getElementById('profileDropdown');

profileButton.addEventListener('click', (e) => {
    e.stopPropagation();
    profileDropdown.classList.toggle('hidden');
});

window.addEventListener('click', (e) => {
    if (!profileButton.contains(e.target) && !profileDropdown.contains(e.target)) {
        profileDropdown.classList.add('hidden');
    }
});

// ✅ Header-specific modal functions
function openHeaderModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('hidden');
    profileDropdown.classList.add('hidden');
}

function closeHeaderModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('hidden');
}

// ✅ AJAX Submit for Inquiry Form (No Redirect)
document.getElementById('inquiryForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('../config/send_inquiry.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.text())
    .then(data => {
        alert(data.trim()); // show success or error message
        this.reset(); // clear inputs
        closeHeaderModal('messageModal'); // close modal
    })
    .catch(err => {
        console.error(err);
        alert('Failed to send inquiry. Please try again.');
    });
});
</script>
</body>
</html>
