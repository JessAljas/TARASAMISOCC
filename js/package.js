// ==================== Swiper Image Slider ====================
document.addEventListener("DOMContentLoaded", () => {
  if (typeof Swiper !== "undefined") {
    new Swiper(".swiper-container", {
      slidesPerView: 1,
      spaceBetween: 10,
      loop: true,
      pagination: { el: ".swiper-pagination", clickable: true },
      navigation: {
        nextEl: ".swiper-button-next",
        prevEl: ".swiper-button-prev",
      },
      autoplay: {
        delay: 3000,
        disableOnInteraction: false,
      },
      effect: "slide",
      speed: 800,
    });
  }

  // ==================== Description Toggle ====================
  window.toggleDesc = function (index) {
    const container = document.getElementById("desc-container-" + index);
    const btn = document.getElementById("desc-btn-" + index);

    if (!container || !btn) return;

    if (container.style.maxHeight && container.style.maxHeight !== "6rem") {
      container.style.maxHeight = "6rem"; // collapse
      btn.textContent = "Read More";
    } else {
      container.style.maxHeight = container.scrollHeight + "px"; // expand
      btn.textContent = "Read Less";
    }
  };
});

// ==================== Leaflet Map ====================
function initMap(destinations = [], defaultLat = 8.45, defaultLng = 123.84) {
  if (!document.getElementById("map")) return;

  const map = L.map("map").setView(
    [
      destinations[0]?.latitude || defaultLat,
      destinations[0]?.longitude || defaultLng,
    ],
    10
  );

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "Map data &copy; OpenStreetMap contributors",
  }).addTo(map);

  const routePoints = [];

  // Helper for numbered markers
  const createNumberedMarker = (lat, lng, number, popupText) => {
    const icon = L.divIcon({
      html: `<div style="background-color:red;color:white;border-radius:50%;width:25px;height:25px;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:12px;">${number}</div>`,
      className: "",
      iconSize: [25, 25],
      iconAnchor: [12, 12],
      popupAnchor: [0, -10],
    });
    L.marker([lat, lng], { icon }).addTo(map).bindPopup(popupText);
  };

  // Loop destinations
  destinations.forEach((dest, index) => {
    if (dest.latitude && dest.longitude) {
      createNumberedMarker(
        dest.latitude,
        dest.longitude,
        index + 1,
        `<b>${dest.name}</b><br>${dest.location}`
      );
      routePoints.push([dest.latitude, dest.longitude]);
    }
  });

  // Draw polyline
  if (routePoints.length > 1) {
    const polyline = L.polyline(routePoints, {
      color: "red",
      weight: 4,
      opacity: 0.7,
    }).addTo(map);
    map.fitBounds(polyline.getBounds());
  } else if (routePoints.length === 1) {
    map.setView(routePoints[0], 13);
  }
}

// ==================== Pax Selection & Price ====================
function initBooking(pricePerPax) {
  const paxSelect = document.getElementById("pax");
  const totalEl = document.getElementById("total");
  const totalInput = document.getElementById("total_input");

  if (!paxSelect || !totalEl || !totalInput) return;

  const updateTotal = () => {
    const pax = parseInt(paxSelect.value) || 1;
    const total = pricePerPax * pax;
    totalEl.textContent = "₱" + total.toLocaleString();
    totalInput.value = total;
  };

  updateTotal();
  paxSelect.addEventListener("change", updateTotal);
}

// ==================== Flatpickr ====================
function initDatePicker() {
  if (typeof flatpickr !== "undefined") {
    flatpickr("#booking_date", {
      minDate: "today",
      dateFormat: "Y-m-d",
    });
  }
}

//============PREVIEW BOOKING=======================
// Copy form values into hidden fields before submitting sa payment forms
function copyBookingInfo(form) {
  const bookingForm = document.getElementById("bookingInfo");
  const data = new FormData(bookingForm);
  form.fullname.value = data.get("fullname");
  form.address.value = data.get("address");
  form.email.value = data.get("email");
  form.phone.value = data.get("phone");

  if (
    !form.fullname.value ||
    !form.address.value ||
    !form.email.value ||
    !form.phone.value
  ) {
    alert("Please fill out all fields before proceeding to payment.");
    return false;
  }
  return true;
}

//=======SERVICES=======

const swiper = new Swiper(".mySwiper", {
  slidesPerView: 1,
  spaceBetween: 20,
  loop: true,
  autoplay: { delay: 4000 },
  pagination: { el: ".swiper-pagination", clickable: true },
  breakpoints: {
    640: { slidesPerView: 1 },
    768: { slidesPerView: 2 },
    1024: { slidesPerView: 3 },
  },
});
