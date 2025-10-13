// tourist_spots.js

let editModal = document.getElementById("editModal");
let editMapInstance = null;
let deletedImages = []; // Track images marked for deletion

// Open Edit Modal
function openEditModal(spot) {
  editModal.classList.remove("hidden");
  document.getElementById("edit_spot_id").value = spot.id;
  document.getElementById("edit_name").value = spot.name_of_tourist_spot;
  document.getElementById("edit_owner_name").value = spot.owner_name;
  document.getElementById("edit_location").value = spot.location;
  document.getElementById("edit_description").value = spot.description;
  document.getElementById("edit_activity").value = spot.activity;
  document.getElementById("edit_entrance_fee").value = spot.entrance_fee;
  document.getElementById("edit_latitude").value = spot.latitude;
  document.getElementById("edit_longitude").value = spot.longitude;

  // Images container
  const previewContainer = document.getElementById("edit_images_preview");
  previewContainer.innerHTML = "";
  deletedImages = [];

  for (let i = 0; i < 3; i++) {
    const imgName = spot["image" + (i + 1)];
    const wrapper = document.createElement("div");
    wrapper.className = "relative w-full h-32";

    const img = document.createElement("img");
    img.src = imgName ? "../uploads/" + imgName : "";
    img.className = "w-full h-32 object-cover rounded";
    wrapper.appendChild(img);

    if (imgName) {
      const delBtn = document.createElement("button");
      delBtn.type = "button";
      delBtn.innerHTML = "&times;";
      delBtn.className =
        "absolute top-1 right-1 bg-red-500 text-white w-6 h-6 rounded-full text-sm flex items-center justify-center hover:bg-red-600";
      delBtn.onclick = () => {
        wrapper.remove();
        deletedImages.push(imgName);

        const hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = "delete_image[]";
        hiddenInput.value = imgName;
        document.getElementById("editForm").appendChild(hiddenInput);
      };
      wrapper.appendChild(delBtn);
    }

    previewContainer.appendChild(wrapper);
  }

  // Map setup
  if (editMapInstance) editMapInstance.remove();
  const lat = spot.latitude ? parseFloat(spot.latitude) : 8.15;
  const lng = spot.longitude ? parseFloat(spot.longitude) : 123.85;

  setTimeout(() => {
    editMapInstance = L.map("edit_map", { center: [lat, lng], zoom: 12 });
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(editMapInstance);

    let marker = L.marker([lat, lng], { draggable: true }).addTo(
      editMapInstance
    );

    marker.on("dragend", function (e) {
      const pos = e.target.getLatLng();
      document.getElementById("edit_latitude").value = pos.lat.toFixed(6);
      document.getElementById("edit_longitude").value = pos.lng.toFixed(6);
    });

    editMapInstance.on("click", function (e) {
      const clickLat = e.latlng.lat.toFixed(6);
      const clickLng = e.latlng.lng.toFixed(6);
      document.getElementById("edit_latitude").value = clickLat;
      document.getElementById("edit_longitude").value = clickLng;
      marker.setLatLng([clickLat, clickLng]);
    });

    editMapInstance.invalidateSize();
    editMapInstance.setView([lat, lng], 12);
  }, 300);
}

// Close modal
function closeEditModal() {
  editModal.classList.add("hidden");
  document.getElementById("editForm").reset();
  deletedImages = [];
}

// Preview new image
function previewNewImage(input, index) {
  const file = input.files[0];
  if (!file) return;
  const previewContainer = document.getElementById("edit_images_preview");
  if (previewContainer.children[index]) {
    const imgElem = previewContainer.children[index].querySelector("img");
    imgElem.src = URL.createObjectURL(file);
  }
}

// Auto-hide flash message
window.addEventListener("DOMContentLoaded", () => {
  const messageBox = document.getElementById("messageBox");
  if (messageBox) {
    setTimeout(() => {
      messageBox.style.transition = "opacity 0.8s";
      messageBox.style.opacity = "0";
      setTimeout(() => messageBox.remove(), 500);
    }, 5000);
  }
});

// Search filter
const searchInput = document.getElementById("searchInput");
if (searchInput) {
  searchInput.addEventListener("input", function () {
    const filter = searchInput.value.toLowerCase();
    document.querySelectorAll("#spotsTable tbody tr").forEach((row) => {
      const text = row.innerText.toLowerCase();
      row.style.display = text.includes(filter) ? "" : "none";
    });
  });
}
// Attach click event to all edit buttons
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".editBtn").forEach((button) => {
    button.addEventListener("click", function () {
      const spot = JSON.parse(this.getAttribute("data-spot"));
      openEditModal(spot);
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const deleteModal = document.getElementById("deleteModal");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");
  const cancelDeleteBtn = document.getElementById("cancelDeleteBtn");
  let currentSpotId = null;

  // Open delete modal when clicking delete button
  document.querySelectorAll(".deleteBtn").forEach((button) => {
    button.addEventListener("click", function () {
      currentSpotId = this.getAttribute("data-id");
      deleteModal.classList.remove("hidden");
    });
  });

  // Cancel deletion
  cancelDeleteBtn.addEventListener("click", () => {
    currentSpotId = null;
    deleteModal.classList.add("hidden");
  });

  // Confirm deletion
  confirmDeleteBtn.addEventListener("click", () => {
    if (currentSpotId) {
      window.location.href = `?delete=${currentSpotId}`;
    }
  });
});
