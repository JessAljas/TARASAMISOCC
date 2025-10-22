// === DATA ===
let spotsData = JSON.parse(document.getElementById("spotsData").textContent);
let currentMap = null;

// === PROFILE DROPDOWN ===
function toggleProfileDropdown() {
  const dropdown = document.getElementById("profileDropdown");
  dropdown.classList.toggle("hidden");
}

window.addEventListener("click", function (e) {
  const btn = document.getElementById("profileBtn");
  const dropdown = document.getElementById("profileDropdown");
  if (!btn.contains(e.target) && !dropdown.contains(e.target)) {
    dropdown.classList.add("hidden");
  }
});

// === SPOT MODAL ===
function openModal(id) {
  const spot = spotsData.find((s) => s.id == id);
  if (!spot) return;

  document.getElementById("modalName").innerText = spot.name_of_tourist_spot;
  document.getElementById("modalLocation").innerText = spot.location;
  document.getElementById("modalPostedBy").innerText =
    spot.posted_by_name ?? "N/A";
  document.getElementById("modalDescription").innerText =
    spot.description || "N/A";
  document.getElementById("modalFee").innerText = spot.entrance_fee
    ? "₱" + spot.entrance_fee
    : "N/A";

  const modalStatus = document.getElementById("modalStatus");
  modalStatus.innerText =
    spot.status.charAt(0).toUpperCase() + spot.status.slice(1);
  modalStatus.className = "";
  if (spot.status.toLowerCase() === "pending")
    modalStatus.className = "text-yellow-600 font-semibold";
  else if (spot.status.toLowerCase() === "verified")
    modalStatus.className = "text-green-600 font-semibold";
  else if (spot.status.toLowerCase() === "rejected")
    modalStatus.className = "text-red-800 font-semibold";
  else if (spot.status.toLowerCase() === "modified")
    modalStatus.className = "text-orange-500 font-semibold";

  // IMAGES
  const imgDiv = document.getElementById("modalImages");
  imgDiv.innerHTML = "";
  spot.images.forEach((img) => {
    const i = document.createElement("img");
    i.src = img;
    i.className = "w-32 h-20 object-cover rounded shadow";
    imgDiv.appendChild(i);
  });

  // MAP
  if (currentMap) currentMap.remove();
  currentMap = L.map("modalMap").setView(
    [parseFloat(spot.latitude) || 0, parseFloat(spot.longitude) || 0],
    15
  );
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(currentMap);
  L.marker([parseFloat(spot.latitude) || 0, parseFloat(spot.longitude) || 0])
    .addTo(currentMap)
    .bindPopup(spot.name_of_tourist_spot)
    .openPopup();
  setTimeout(() => currentMap.invalidateSize(), 200);

  // BUTTONS
  const btnDiv = document.getElementById("modalButtons");
  btnDiv.innerHTML = "";
  if (["pending", "modified"].includes(spot.status.toLowerCase())) {
    const approve = document.createElement("button");
    approve.innerText = "Verify";
    approve.className =
      "px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700";
    approve.onclick = () =>
      openConfirmModal(
        "Verify Spot",
        "Are you sure you want to verify this spot?",
        () => updateSpotStatus(spot.id, "verify")
      );

    const reject = document.createElement("button");
    reject.innerText = "Reject";
    reject.className =
      "px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700";
    reject.onclick = () =>
      openConfirmModal(
        "Reject Spot",
        "Are you sure you want to reject this spot?",
        () => updateSpotStatus(spot.id, "reject")
      );

    btnDiv.appendChild(approve);
    btnDiv.appendChild(reject);
  }

  document.getElementById("spotModal").classList.remove("hidden");
}

function closeModal() {
  document.getElementById("spotModal").classList.add("hidden");
  if (currentMap) currentMap.remove();
  currentMap = null;
}

// === CONFIRMATION MODAL ===
function openConfirmModal(title, message, callback) {
  document.getElementById("confirmTitle").innerText = title;
  document.getElementById("confirmMessage").innerText = message;

  const yesBtn = document.getElementById("confirmYesBtn");
  yesBtn.onclick = function () {
    callback();
    closeConfirmModal();
  };

  document.getElementById("confirmModal").classList.remove("hidden");
}

function closeConfirmModal() {
  document.getElementById("confirmModal").classList.add("hidden");
}

// === AJAX VERIFY / REJECT ===
function updateSpotStatus(id, action) {
  const formData = new FormData();
  formData.append("id", id);
  formData.append("action", action);

  fetch(window.location.href, { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      // Update table row
      const row = document.querySelector(`tr[data-id='${id}']`);
      const statusCell = row.querySelector(".statusCell");
      statusCell.innerHTML = "";
      if (action === "verify") {
        statusCell.innerHTML =
          '<span class="px-4 py-2 rounded-full text-lg font-bold bg-green-100 text-green-700">Verified</span>';
        row.classList.remove("bg-red-50", "bg-orange-50");
      } else if (action === "reject") {
        statusCell.innerHTML =
          '<span class="px-4 py-2 rounded-full text-lg font-bold bg-red-100 text-red-700">Rejected</span>';
        row.classList.add("bg-red-50");
      }

      // Update counts
      document.getElementById("countVerified").innerText = data.verified;
      document.getElementById("countPending").innerText = data.pending;
      document.getElementById("countRejected").innerText = data.rejected;

      closeModal();
    })
    .catch((err) => console.error(err));
}

// === LOGOUT MODAL ===
function openLogoutModal() {
  document.getElementById("logoutModal").classList.remove("hidden");
}
function closeLogoutModal() {
  document.getElementById("logoutModal").classList.add("hidden");
}

// === MESSAGE MODAL ===
function openMessageModal() {
  document.getElementById("messageModal").classList.remove("hidden");
}
function closeMessageModal() {
  document.getElementById("messageModal").classList.add("hidden");
}

// Submit message
document.getElementById("messageForm").addEventListener("submit", function (e) {
  e.preventDefault();
  const formData = new FormData(this);
  const userIdEl = document.getElementById("userId");
  if (!userIdEl || !userIdEl.textContent.trim())
    return alert("User ID not found. Refresh the page.");
  formData.append("sender_id", parseInt(userIdEl.textContent));
  formData.append("sender_role", "tourism_officers");
  formData.append("receiver_role", "agency");

  fetch("tourism_dashboard.php", { method: "POST", body: formData })
    .then(async (res) => {
      const text = await res.text();
      try {
        return JSON.parse(text);
      } catch {
        throw new Error("Server returned invalid response.");
      }
    })
    .then((data) => {
      if (data.success) {
        alert("Message sent successfully!");
        closeMessageModal();
        this.reset();
      } else
        alert("Failed to send message: " + (data.error || "Unknown error"));
    })
    .catch((err) => {
      console.error(err);
      alert("Error sending message. Check console.");
    });
});

//=== LIVE SEARCH ===
document.getElementById("spotSearch").addEventListener("input", function () {
  const filter = this.value.toLowerCase();
  const rows = document.querySelectorAll("#spotsTable tbody tr");

  rows.forEach((row) => {
    const text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
