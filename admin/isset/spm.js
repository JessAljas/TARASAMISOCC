// agency_manage_tourist_spots.js

let editMap, editMarker;

// ===== OPEN EDIT MODAL =====
function openEditModal(button) {
  const spot = JSON.parse(button.getAttribute("data-spot"));
  document.getElementById("edit_spot_id").value = spot.id;
  document.getElementById("edit_name").value = spot.name_of_tourist_spot;
  document.getElementById("edit_description").value = spot.description;
  document.getElementById("edit_location").value = spot.location;
  document.getElementById("edit_activity").value = spot.activity;
  document.getElementById("edit_fee").value = spot.entrance_fee;
  document.getElementById("edit_status").textContent =
    spot.status.charAt(0).toUpperCase() + spot.status.slice(1);

  document.getElementById("editModal").classList.remove("hidden");

  // ===== SHOW EXISTING IMAGES =====
  const container = document.getElementById("edit_images_container");
  container.innerHTML = "";
  for (let i = 1; i <= 3; i++) {
    const imgSrc = spot["image" + i];
    const div = document.createElement("div");
    div.className = "relative w-24 h-24 flex-shrink-0";

    const img = document.createElement("img");
    img.id = `edit_image${i}_preview`;
    img.className = "w-full h-full object-cover rounded border";
    img.alt = `Image ${i}`;
    img.src = imgSrc ? "../uploads/" + imgSrc : "";
    div.appendChild(img);

    const deleteBtn = document.createElement("button");
    deleteBtn.type = "button";
    deleteBtn.className =
      "absolute top-1 right-1 bg-red-500 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs hover:bg-red-600";
    deleteBtn.innerHTML = "&times;";
    deleteBtn.onclick = () => removeEditImage(i);
    div.appendChild(deleteBtn);

    const fileInput = document.createElement("input");
    fileInput.type = "file";
    fileInput.name = "edit_images[]";
    fileInput.className = "mt-1 text-xs w-full";
    fileInput.onchange = (event) => previewEditImage(event, i);
    div.appendChild(fileInput);

    const hiddenDelete = document.createElement("input");
    hiddenDelete.type = "hidden";
    hiddenDelete.name = `delete_image${i}`;
    hiddenDelete.id = `delete_image${i}`;
    hiddenDelete.value = "0";
    div.appendChild(hiddenDelete);

    container.appendChild(div);
  }

  // ====== LEAFLET MAP INITIALIZATION =====
  setTimeout(() => {
    const lat = parseFloat(spot.latitude) || 8.15;
    const lng = parseFloat(spot.longitude) || 123.85;

    if (editMap) {
      editMap.remove();
    }

    editMap = L.map("map").setView([lat, lng], 12);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(editMap);

    editMarker = L.marker([lat, lng]).addTo(editMap);

    editMap.on("click", function (e) {
      document.getElementById("latitude").value = e.latlng.lat.toFixed(6);
      document.getElementById("longitude").value = e.latlng.lng.toFixed(6);
      editMarker.setLatLng(e.latlng);
    });

    setTimeout(() => {
      editMap.invalidateSize();
    }, 200);
  }, 300);
}

// ===== REMOVE IMAGE =====
function removeEditImage(i) {
  document.getElementById(`edit_image${i}_preview`).src = "";
  document.getElementById(`delete_image${i}`).value = "1";
}

// ===== CLOSE EDIT MODAL =====
function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}

// ===== DELETE MODAL =====
function openDeleteModal(id, name) {
  document.getElementById("deleteModal").classList.remove("hidden");
  document.getElementById(
    "deleteModalText"
  ).innerHTML = `Are you sure you want to delete <strong>${name}</strong>?`;
  document.getElementById("confirmDeleteBtn").href = "?delete_id=" + id;
}

function closeDeleteModal() {
  document.getElementById("deleteModal").classList.add("hidden");
}

// ===== CONNECT BUTTONS =====
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".editBtn").forEach((btn) => {
    btn.addEventListener("click", () => openEditModal(btn));
  });
  document.querySelectorAll(".deleteBtn").forEach((btn) => {
    btn.addEventListener("click", () =>
      openDeleteModal(btn.dataset.id, btn.dataset.name)
    );
  });
});

// Optional: Preview new uploaded image
function previewEditImage(event, i) {
  const file = event.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (e) => {
      document.getElementById(`edit_image${i}_preview`).src = e.target.result;
      document.getElementById(`delete_image${i}`).value = "0";
    };
    reader.readAsDataURL(file);
  }
}
