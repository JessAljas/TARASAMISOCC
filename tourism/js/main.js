// tourism_dashboard.js

// spotsData from PHP
let spotsData = JSON.parse(document.getElementById("spotsData").textContent);
let currentMap = null;

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

function openModal(id) {
  const spot = spotsData.find((s) => s.id == id);
  if (!spot) return;

  document.getElementById("modalName").innerText = spot.name_of_tourist_spot;
  document.getElementById("modalLocation").innerText = spot.location;
  document.getElementById("modalPostedBy").innerText =
    spot.posted_by_name ?? "N/A";
  document.getElementById("modalDescription").innerText = spot.description;
  document.getElementById("modalFee").innerText = spot.entrance_fee
    ? "₱" + spot.entrance_fee
    : "N/A";

  const modalStatus = document.getElementById("modalStatus");
  modalStatus.innerText = spot.status;
  modalStatus.className = "";
  if (spot.status.toLowerCase() === "pending") {
    modalStatus.className = "text-yellow-600 font-semibold";
  } else if (spot.status.toLowerCase() === "verified") {
    modalStatus.className = "text-green-600 font-semibold";
  } else if (spot.status.toLowerCase() === "rejected") {
    modalStatus.className = "text-red-800 font-semibold";
  } else if (spot.status.toLowerCase() === "modified") {
    modalStatus.className = "text-orange-500 font-semibold";
  }

  const imgDiv = document.getElementById("modalImages");
  imgDiv.innerHTML = "";
  spot.images.forEach((img) => {
    const i = document.createElement("img");
    i.src = img;
    i.className = "w-32 h-20 object-cover rounded shadow";
    imgDiv.appendChild(i);
  });

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

  const btnDiv = document.getElementById("modalButtons");
  btnDiv.innerHTML = "";

  if (["pending", "modified"].includes(spot.status.toLowerCase())) {
    const approve = document.createElement("button");
    approve.innerText = "Verify";
    approve.className =
      "px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700";
    approve.onclick = () => updateSpotStatus(spot.id, "verify");

    const reject = document.createElement("button");
    reject.innerText = "Reject";
    reject.className =
      "px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700";
    reject.onclick = () => updateSpotStatus(spot.id, "reject");

    btnDiv.appendChild(approve);
    btnDiv.appendChild(reject);
  }

  document.getElementById("spotModal").classList.remove("hidden");
}

function updateSpotStatus(id, action) {
  fetch("tourism_dashboard.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({ action: action, id: id }),
  })
    .then((r) => r.json())
    .then((data) => {
      const row = document.querySelector('tr[data-id="' + id + '"]');
      const cell = row.querySelector(".statusCell span");
      if (action === "verify") {
        cell.innerText = "Verified";
        cell.className = "text-green-600 font-semibold";
        row.classList.remove("bg-red-50", "bg-orange-50");
      } else if (action === "reject") {
        cell.innerText = "Rejected";
        cell.className = "text-red-800 font-semibold";
        row.classList.add("bg-red-50");
      }

      document.getElementById("countVerified").innerText = data.verified;
      document.getElementById("countPending").innerText = data.pending;
      document.getElementById("countRejected").innerText = data.rejected;

      closeModal();
    })
    .catch((err) => console.error(err));
}

function closeModal() {
  document.getElementById("spotModal").classList.add("hidden");
  if (currentMap) currentMap.remove();
  currentMap = null;
}

function openLogoutModal() {
  document.getElementById("logoutModal").classList.remove("hidden");
}
function closeLogoutModal() {
  document.getElementById("logoutModal").classList.add("hidden");
}

function openMessageModal() {
  document.getElementById("messageModal").classList.remove("hidden");
}
function closeMessageModal() {
  document.getElementById("messageModal").classList.add("hidden");
}

// Submit message form
document.getElementById("messageForm").addEventListener("submit", function (e) {
  e.preventDefault();

  const formData = new FormData(this);
  formData.append(
    "sender_id",
    parseInt(document.getElementById("userId").textContent)
  );
  formData.append("sender_role", "tourism_officers");
  formData.append("receiver_role", "agency");

  fetch("tourism_dashboard.php", { method: "POST", body: formData })
    .then((res) => res.json())
    .then((data) => {
      if (data.success) {
        alert("Message sent successfully!");
        closeMessageModal();
        this.reset();
      } else {
        alert("Failed to send message.");
      }
    })
    .catch((err) => {
      console.error(err);
      alert("Error sending message.");
    });
});
