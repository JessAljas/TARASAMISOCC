// ================== PASSWORD TOGGLE ==================
function togglePassword() {
  const passwordField = document.getElementById("password");
  const eyeIcon = document.getElementById("eyeIcon");
  if (!passwordField) return;

  if (passwordField.type === "password") {
    passwordField.type = "text";
    if (eyeIcon) eyeIcon.classList.add("text-blue-500");
  } else {
    passwordField.type = "password";
    if (eyeIcon) eyeIcon.classList.remove("text-blue-500");
  }
}

// ================== LEAFLET MAP (Admin/Add/Edit) ==================
function initMap() {
  const mapElement = document.getElementById("map");
  if (!mapElement) return;

  // Initialize the map
  var map = L.map("map").setView([8.15, 123.85], 10);
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  var marker;
  var latInput = document.getElementById("latitude");
  var lngInput = document.getElementById("longitude");

  // Preload existing marker if present
  if (latInput?.value && lngInput?.value) {
    var preLatLng = L.latLng(latInput.value, lngInput.value);
    marker = L.marker(preLatLng).addTo(map);
    map.setView(preLatLng, 14);
  }

  // Update marker when clicking map
  map.on("click", function (e) {
    var lat = e.latlng.lat.toFixed(6);
    var lng = e.latlng.lng.toFixed(6);
    if (latInput) latInput.value = lat;
    if (lngInput) lngInput.value = lng;

    if (marker) {
      marker.setLatLng(e.latlng);
    } else {
      marker = L.marker(e.latlng).addTo(map);
    }
  });

  // ✅ Add geocoder control (explicitly use Nominatim)
  var geocoder = L.Control.geocoder({
    defaultMarkGeocode: false,
    geocoder: L.Control.Geocoder.nominatim({
      geocodingQueryParams: { countrycodes: "ph" }, // optional: limit to Philippines
    }),
  })
    .on("markgeocode", function (e) {
      var center = e.geocode.center;
      map.setView(center, 14);
      if (latInput) latInput.value = center.lat.toFixed(6);
      if (lngInput) lngInput.value = center.lng.toFixed(6);

      if (marker) {
        marker.setLatLng(center);
      } else {
        marker = L.marker(center).addTo(map);
      }
    })
    .addTo(map);
}

// ================== SPOT MAP (Spot Details Page) ==================
function initSpotMap(lat = 8.15, lng = 123.85, spotName = "Tourist Spot") {
  const mapEl = document.getElementById("map");
  if (!mapEl) return;

  const map = L.map("map").setView([lat, lng], 14);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  L.marker([lat, lng], {
    icon: L.icon({
      iconUrl: "https://maps.google.com/mapfiles/ms/icons/red-dot.png",
      iconSize: [32, 32],
      iconAnchor: [16, 32],
      popupAnchor: [0, -32],
    }),
  })
    .addTo(map)
    .bindPopup(`<b>${spotName}</b>`)
    .openPopup();
}

function toggleSidebar() {
  const sidebar = document.getElementById("sidebar");
  if (sidebar) sidebar.classList.toggle("show");
}

// Close sidebar when clicking outside
document.addEventListener("click", (e) => {
  const sidebar = document.getElementById("sidebar");
  const toggleBtn = document.getElementById("toggleButton");
  if (
    window.innerWidth <= 768 &&
    sidebar &&
    !sidebar.contains(e.target) &&
    !toggleBtn.contains(e.target)
  ) {
    sidebar.classList.remove("show");
  }
});

// ================== BOOKINGS TABLE SEARCH ==================
function filterBookingsTable() {
  let input = document.getElementById("searchInput");
  if (!input) return;

  let filter = input.value.toUpperCase();
  let table = document.getElementById("bookingsTable");
  if (!table) return;

  let tr = table.getElementsByTagName("tr");
  for (let i = 1; i < tr.length; i++) {
    let td = tr[i].getElementsByTagName("td")[2]; // Reference No.
    if (td) {
      let txtValue = td.textContent || td.innerText;
      tr[i].style.display =
        txtValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
    }
  }
}

// ================== PACKAGES MODAL ==================
let packages = [];
let spots = [];
let dest_map = {};

function initPackagesData(pkgData, spotData, destData) {
  packages = pkgData;
  spots = spotData;
  dest_map = destData;
}

function openPackageModal(id) {
  const pkg = packages.find((p) => p.id == id);
  if (!pkg) return;

  document.getElementById("edit_package_id").value = id;
  document.getElementById("edit_title").value = pkg.title;
  document.getElementById("edit_price").value = pkg.price;
  document.getElementById("edit_pickup").value = pkg.pickup_location;
  document.getElementById("edit_dropoff").value = pkg.dropoff_location;
  document.getElementById("edit_description").value = pkg.description;

  const container = document.getElementById("edit_dest_container");
  container.innerHTML = "";
  spots.forEach((s) => {
    let checked = "";
    if (dest_map[id] && dest_map[id].includes(s.id)) checked = "checked";
    container.innerHTML += `<label class="flex items-center space-x-2 p-2 border rounded hover:bg-green-50 cursor-pointer">
        <input type="checkbox" name="edit_destinations[]" value="${s.id}" ${checked} class="h-4 w-4 text-green-500"><span>${s.name_of_tourist_spot}</span></label>`;
  });

  const imgContainer = document.getElementById("edit_images_container");
  imgContainer.innerHTML = "";
  for (let i = 1; i <= 4; i++) {
    const img = pkg["image" + i];
    const div = document.createElement("div");
    div.classList.add("flex", "flex-col", "items-center", "min-w-[120px]");
    div.innerHTML = `${
      img
        ? `<img src="uploads/${img}" class="w-24 h-24 object-cover mb-1 border rounded">`
        : ""
    }
                         <input type="file" name="edit_images[${
                           i - 1
                         }]" class="w-full">`;
    imgContainer.appendChild(div);
  }

  for (let i = 1; i <= 4; i++) {
    document.getElementById("edit_inclusion" + i).value =
      pkg["inclusion" + i] || "";
    document.getElementById("edit_exclusion" + i).value =
      pkg["exclusion" + i] || "";
  }

  document.getElementById("editModal").classList.remove("hidden");
}

// ================== GENERIC MODAL HANDLING ==================
function closeModal() {
  document
    .querySelectorAll(".modal")
    .forEach((mod) => mod.classList.add("hidden"));
}

function openAddModal() {
  document.getElementById("addModal").style.display = "flex";
}
function closeAddModal() {
  document.getElementById("addModal").style.display = "none";
}

function openEditModal(spot) {
  document.getElementById("edit_spot_id").value = spot.id;
  document.getElementById("edit_name").value = spot.name_of_tourist_spot || "";
  document.getElementById("edit_description").value = spot.description || "";
  document.getElementById("edit_location").value = spot.location || "";
  document.getElementById("edit_activity").value = spot.activity || "";
  document.getElementById("edit_fee").value = spot.entrance_fee || "";
  document.getElementById("edit_status").value = spot.status || "pending";

  let container = document.getElementById("edit_images_container");
  container.innerHTML = "";
  for (let i = 1; i <= 3; i++) {
    let img = spot["image" + i];
    container.innerHTML += `
          <div class="mb-2">
              <label class="block font-medium">Image ${i}</label>
              ${
                img
                  ? `<img src="uploads/${img}" class="w-24 h-24 object-cover mb-2 border rounded">`
                  : ""
              }
              <input type="file" name="edit_images[]" accept="image/*" class="w-full border rounded p-2">
          </div>
        `;
  }

  document.getElementById("editModal").classList.remove("hidden");
}
function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}

// ================== REGISTERED TOURIST ==================
function viewSpots(ownerId) {
  fetch(
    "agency_registered_tourist_spots.php?action=fetch_spots&owner_id=" + ownerId
  )
    .then((res) => res.json())
    .then((data) => {
      const container = document.getElementById("spotsContent");
      container.innerHTML = "";
      if (data.length > 0) {
        data.forEach((spot) => {
          container.innerHTML += `
              <a href="agency_manage_tourist_spots.php?spot_id=${spot.id}" class="block p-4 border rounded shadow-sm hover:bg-gray-50">
                <h3 class="font-semibold text-gray-700">${spot.name_of_tourist_spot}</h3>
                <p class="text-sm text-gray-500">Added: ${spot.created_at}</p>
              </a>
            `;
        });
      } else {
        container.innerHTML = `<p class="text-gray-500">No spots found for this owner.</p>`;
      }
      document.getElementById("spotsModal").style.display = "flex";
    });
}
function closeSpotsModal() {
  document.getElementById("spotsModal").style.display = "none";
}

// ================== READ MORE/LESS ==================
function initReadMore() {
  document.querySelectorAll(".read-more-btn").forEach((button) => {
    button.addEventListener("click", () => {
      const p = button.closest(".description");
      const shortDesc = p.querySelector(".short-desc");
      const fullDesc = p.querySelector(".full-desc");
      if (fullDesc.classList.contains("hidden")) {
        fullDesc.classList.remove("hidden");
        shortDesc.classList.add("hidden");
        button.textContent = "Read Less";
      } else {
        fullDesc.classList.add("hidden");
        shortDesc.classList.remove("hidden");
        button.textContent = "Read More";
      }
    });
  });
}

// ================== IMAGE SLIDER ==================
function initImageSliders() {
  document.querySelectorAll(".slider").forEach((slider) => {
    const slidesContainer = slider.querySelector(".slides");
    const slides = slidesContainer.children;
    let index = 0;

    const updateSlider = () => {
      const width = slides[0]?.clientWidth || 0;
      slidesContainer.style.transform = `translateX(-${index * width}px)`;
    };

    const nextSlide = () => {
      index = (index + 1) % slides.length;
      updateSlider();
    };

    const prevSlide = () => {
      index = (index - 1 + slides.length) % slides.length;
      updateSlider();
    };

    slider.querySelector(".next").addEventListener("click", nextSlide);
    slider.querySelector(".prev").addEventListener("click", prevSlide);

    setInterval(nextSlide, 3000);

    let startX = 0;
    slidesContainer.addEventListener(
      "touchstart",
      (e) => (startX = e.touches[0].clientX)
    );
    slidesContainer.addEventListener("touchend", (e) => {
      let endX = e.changedTouches[0].clientX;
      if (startX - endX > 50) nextSlide();
      else if (endX - startX > 50) prevSlide();
    });

    window.addEventListener("resize", updateSlider);
  });
}

// ================== CARD TOGGLE (Inquiries) ==================
function toggleCard(header) {
  const body = header.nextElementSibling;
  document.querySelectorAll(".card-body").forEach((el) => {
    if (el !== body) el.classList.add("hidden");
  });
  body.classList.toggle("hidden");
}

// ================== PROFILE DROPDOWN ==================
function toggleProfileDropdown() {
  const dropdown = document.getElementById("profileDropdown");
  if (dropdown) dropdown.classList.toggle("hidden");
}
window.addEventListener("click", function (e) {
  const btn = document.getElementById("profileBtn");
  const dropdown = document.getElementById("profileDropdown");
  if (
    btn &&
    dropdown &&
    !btn.contains(e.target) &&
    !dropdown.contains(e.target)
  ) {
    dropdown.classList.add("hidden");
  }
});

// ================== CLOSE MODALS WHEN CLICKING OUTSIDE ==================
window.onclick = function (event) {
  const modals = ["addModal", "spotsModal", "editModal"];
  modals.forEach((id) => {
    const modal = document.getElementById(id);
    if (event.target === modal) {
      modal.style.display = "none";
    }
  });
};

// ================== EDIT OWNER MODAL ==================
function initEditOwnerModal() {
  document.querySelectorAll(".editBtn").forEach((btn) => {
    btn.addEventListener("click", function () {
      document.getElementById("edit_id").value = this.dataset.id;
      document.getElementById("edit_fullname").value = this.dataset.fullname;
      document.getElementById("edit_email").value = this.dataset.email;
      document.getElementById("edit_phone").value = this.dataset.phone;

      const profileImage = this.dataset.profile;
      const imgDiv = document.getElementById("current_profile_image_div");
      const imgEl = document.getElementById("current_profile_image");

      if (profileImage) {
        imgEl.src = "uploads/" + profileImage;
        imgDiv.style.display = "block";
      } else {
        imgDiv.style.display = "none";
      }

      document.getElementById("editModal").style.display = "flex";
    });
  });

  const cancelBtn = document.getElementById("cancelEditBtn");
  if (cancelBtn) {
    cancelBtn.addEventListener("click", () => {
      document.getElementById("editModal").style.display = "none";
    });
  }
}

function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}

document.addEventListener("DOMContentLoaded", () => {
  const msg = document.getElementById("editSuccessMsg");
  if (msg) {
    setTimeout(() => {
      msg.style.transition = "opacity 0.5s ease";
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 500);
    }, 2500);
  }
});

// ================== INIT ==================
document.addEventListener("DOMContentLoaded", function () {
  initMap();
  initReadMore();
  initImageSliders();
  initEditOwnerModal(); // <--- NEW

  if (
    typeof spotLatitude !== "undefined" &&
    typeof spotLongitude !== "undefined"
  ) {
    initSpotMap(spotLatitude, spotLongitude, spotName || "Tourist Spot");
  }
});
