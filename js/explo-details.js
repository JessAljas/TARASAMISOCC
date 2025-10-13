// ==========================================================
// ============ LOGOUT MODAL FUNCTIONS (with fade) ===========
// ==========================================================
function openLogoutModal() {
  const modal = document.getElementById("logoutModal");
  if (!modal) return;

  modal.classList.remove("hidden");
  setTimeout(() => modal.classList.add("opacity-100"), 10); // smooth fade-in
}

function closeLogoutModal() {
  const modal = document.getElementById("logoutModal");
  if (!modal) return;

  modal.classList.remove("opacity-100");
  setTimeout(() => modal.classList.add("hidden"), 300); // smooth fade-out
}

// ==========================================================
// ============ PASSWORD TOGGLE FUNCTION ====================
// ==========================================================
function togglePassword() {
  const passwordField = document.getElementById("password");
  const eyeIcon = document.getElementById("eyeIcon");

  if (!passwordField || !eyeIcon) return;

  if (passwordField.type === "password") {
    passwordField.type = "text";
    eyeIcon.classList.add("text-blue-500");
  } else {
    passwordField.type = "password";
    eyeIcon.classList.remove("text-blue-500");
  }
}

// ==========================================================
// ============ PROVINCE MAP (Main MisOcc View) ==============
// ==========================================================
function initProvinceMap(touristSpots = []) {
  const mapContainer = document.getElementById("map");
  if (!mapContainer) return;

  const map = L.map("map").setView([8.36, 123.75], 9);

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  const misOccPolygon = [
    [8.56, 123.45],
    [8.7, 123.55],
    [8.8, 123.7],
    [8.78, 123.95],
    [8.65, 124.05],
    [8.4, 124.05],
    [8.2, 123.95],
    [8.05, 123.7],
    [8.15, 123.5],
    [8.36, 123.45],
  ];

  L.polygon(misOccPolygon, {
    color: "blue",
    weight: 2,
    fillColor: "#60a5fa",
    fillOpacity: 0.3,
  })
    .addTo(map)
    .bindPopup("Province of Misamis Occidental");

  touristSpots.forEach((spot) => {
    L.marker(spot.location)
      .addTo(map)
      .bindPopup(`<strong>${spot.name}</strong><br>${spot.desc}`);
  });
}

// ==========================================================
// ============ EXPLORE DETAILS MAP (Single Spot) ============
// ==========================================================
function initMap(lat, lng, spotName) {
  const mapContainer = document.getElementById("map");
  if (!mapContainer) return;

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
    .bindTooltip(spotName, {
      permanent: true,
      direction: "top",
      offset: [0, -10],
    })
    .openTooltip();
}
