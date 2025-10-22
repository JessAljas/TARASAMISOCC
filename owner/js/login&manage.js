function togglePassword() {
  const password = document.getElementById("password");
  const icon = document.getElementById("passIcon");
  if (password.type === "password") {
    password.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    password.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

// tourist_spots.js

let spots = [];
let modal, map, marker;

document.addEventListener("DOMContentLoaded", () => {
  modal = document.getElementById("modal");
  // Parse spots from JSON embedded in page
  const spotsData = document.getElementById("spotsData");
  if (spotsData) {
    spots = JSON.parse(spotsData.value);
  }

  // Fade out success message
  const msg = document.getElementById("successMsg");
  if (msg) {
    setTimeout(() => {
      msg.style.transition = "opacity 0.5s";
      msg.style.opacity = 0;
      setTimeout(() => msg.remove(), 500);
    }, 3000);
  }
});

function openModal(id) {
  let spot = spots.find((s) => s.id == id);
  if (!spot) return;

  document.getElementById("spot_id").value = spot.id;
  document.getElementById("spot_name").value = spot.name_of_tourist_spot || "";
  document.getElementById("activity").value = spot.activity || "";
  document.getElementById("location").value = spot.location || "";
  document.getElementById("description").value = spot.description || "";
  document.getElementById("entrance_fee").value = spot.entrance_fee || "";
  document.getElementById("latitude").value = spot.latitude || "";
  document.getElementById("longitude").value = spot.longitude || "";

  // Display current images
  let currentDiv = document.getElementById("currentImages");
  currentDiv.innerHTML = "";
  for (let i = 1; i <= 3; i++) {
    if (spot["image" + i] && spot["image" + i] !== "") {
      let wrapper = document.createElement("div");
      wrapper.className = "relative inline-block";

      let img = document.createElement("img");
      img.src = "../uploads/" + spot["image" + i];
      img.className = "h-16 w-24 object-cover border rounded";
      img.alt = `Image ${i} of ${spot.name_of_tourist_spot}`;

      let delBtn = document.createElement("button");
      delBtn.innerHTML = "&times;";
      delBtn.className =
        "absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 text-xs flex items-center justify-center hover:bg-red-600";
      delBtn.title = "Delete this image";
      delBtn.type = "button";

      delBtn.addEventListener("click", () => {
        wrapper.remove();
        let input = document.createElement("input");
        input.type = "hidden";
        input.name = "delete_images[]";
        input.value = spot["image" + i];
        document.querySelector("form").appendChild(input);
      });

      wrapper.appendChild(img);
      wrapper.appendChild(delBtn);
      currentDiv.appendChild(wrapper);
    }
  }

  modal.classList.remove("hidden");

  setTimeout(() => {
    if (map) map.remove();
    const lat = spot.latitude ? parseFloat(spot.latitude) : 8.15;
    const lng = spot.longitude ? parseFloat(spot.longitude) : 123.85;
    map = L.map("map").setView([lat, lng], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(map);
    marker = L.marker([lat, lng]).addTo(map);
    map.on("click", function (e) {
      document.getElementById("latitude").value = e.latlng.lat.toFixed(6);
      document.getElementById("longitude").value = e.latlng.lng.toFixed(6);
      marker.setLatLng(e.latlng);
    });
  }, 100);
}

function closeModal() {
  modal.classList.add("hidden");
}

const deleteModal = document.getElementById("deleteModal");
const deleteSpotName = document.getElementById("deleteSpotName");
const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

function openDeleteModal(spotId, spotName) {
  deleteSpotName.textContent = spotName;
  confirmDeleteBtn.href = "?delete=" + spotId;
  deleteModal.classList.remove("hidden");
}

function closeDeleteModal() {
  deleteModal.classList.add("hidden");
}
