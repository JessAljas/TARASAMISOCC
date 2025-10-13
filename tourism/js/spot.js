// Initialize map
var map = L.map("map").setView([8.15, 123.85], 12);

// Add OpenStreetMap tiles
L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
  attribution: "&copy; OpenStreetMap contributors",
}).addTo(map);

var marker;

// Click on map to set marker and update input fields
map.on("click", function (e) {
  var lat = e.latlng.lat.toFixed(6);
  var lng = e.latlng.lng.toFixed(6);

  document.getElementById("latitude").value = lat;
  document.getElementById("longitude").value = lng;

  if (marker) map.removeLayer(marker);
  marker = L.marker([lat, lng]).addTo(map);
});

// Add search control (requires leaflet-control-geocoder plugin)
if (L.Control.Geocoder) {
  L.Control.geocoder({ defaultMarkGeocode: false })
    .on("markgeocode", function (e) {
      var center = e.geocode.center;
      map.setView(center, 15);

      if (marker) map.removeLayer(marker);
      marker = L.marker([center.lat, center.lng]).addTo(map);

      document.getElementById("latitude").value = center.lat.toFixed(6);
      document.getElementById("longitude").value = center.lng.toFixed(6);
    })
    .addTo(map);
}

function openEditModal() {
  document.getElementById("editModal").classList.remove("hidden");
}
function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
}

// Get packages data from script tag
const packagesData = JSON.parse(
  document.getElementById("packagesData").textContent
);

let modal = document.getElementById("viewModal");

function openModal(pkg) {
  modal.classList.remove("hidden");
  document.getElementById("modal_package_id").value = pkg.id;
  document.getElementById("modal_title").innerText = pkg.title;
  document.getElementById("modal_description").innerText = pkg.description;
  document.getElementById("modal_price").innerText = parseFloat(
    pkg.price
  ).toFixed(2);
  document.getElementById("modal_locations").innerText =
    pkg.locations ?? "No location provided";
  document.getElementById("modal_destinations").innerText =
    pkg.destinations ?? "No destinations";
  document.getElementById("modal_status").innerText = pkg.status;
  document.getElementById("modal_created_at").innerText = new Date(
    pkg.created_at
  ).toLocaleString();

  const imgBase = "../uploads/";
  for (let i = 1; i <= 4; i++) {
    const img = document.getElementById(`modal_image${i}`);
    img.src = pkg[`image${i}`] ? imgBase + pkg[`image${i}`] : "placeholder.png";
  }
}

function closeModal() {
  modal.classList.add("hidden");
}
