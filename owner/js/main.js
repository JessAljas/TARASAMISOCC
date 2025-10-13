document.addEventListener("DOMContentLoaded", () => {
  // Fade out messages
  const msgContainer = document.getElementById("modalMessageContainer");
  if (msgContainer) {
    const messages = msgContainer.querySelectorAll("p");
    if (messages.length) {
      setTimeout(() => {
        messages.forEach((msg) => {
          msg.style.transition = "opacity 0.5s";
          msg.style.opacity = "0";
          setTimeout(() => msg.remove(), 500);
        });
      }, 6000);
    }
  }

  // Profile dropdown
  const profileBtn = document.querySelector(
    'button[onclick="toggleProfileDropdown()"]'
  );
  const dropdown = document.getElementById("profileDropdown");
  window.toggleProfileDropdown = function () {
    dropdown.classList.toggle("hidden");
  };
  document.addEventListener("click", (e) => {
    if (!e.target.closest("#profileDropdown") && !e.target.closest(profileBtn))
      dropdown.classList.add("hidden");
  });

  // Add spot modal
  const addSpotBtn = document.getElementById("openAddSpotModalBtn");
  if (addSpotBtn)
    addSpotBtn.addEventListener("click", () => {
      openModal("addSpotModal");
      setTimeout(initAddSpotMap, 100);
    });

  window.openModal = (id) =>
    document.getElementById(id).classList.remove("hidden");
  window.closeModal = (id) =>
    document.getElementById(id).classList.add("hidden");

  let addSpotMap, marker;
  function initAddSpotMap() {
    if (addSpotMap) return;
    addSpotMap = L.map("addSpotMap").setView([10.0, 125.0], 6);
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: "&copy; OpenStreetMap contributors",
    }).addTo(addSpotMap);

    marker = null;
    addSpotMap.on("click", (e) => setMarker(e.latlng.lat, e.latlng.lng));

    const geocoder = L.Control.geocoder({ defaultMarkGeocode: false })
      .on("markgeocode", (e) => {
        const latlng = e.geocode.center;
        addSpotMap.setView(latlng, 15);
        setMarker(latlng.lat, latlng.lng);
      })
      .addTo(addSpotMap);

    const searchInput = document.getElementById("mapSearchInput");
    if (searchInput) {
      searchInput.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
          e.preventDefault();
          geocoder.options.geocoder.geocode(searchInput.value, (results) => {
            if (results && results.length > 0)
              geocoder._fire("markgeocode", { geocode: results[0] });
            else alert("Place not found!");
          });
        }
      });
    }
  }

  function setMarker(lat, lng) {
    document.getElementById("latitudeInput").value = lat.toFixed(6);
    document.getElementById("longitudeInput").value = lng.toFixed(6);
    if (marker) marker.setLatLng([lat, lng]);
    else marker = L.marker([lat, lng]).addTo(addSpotMap);
  }
});
