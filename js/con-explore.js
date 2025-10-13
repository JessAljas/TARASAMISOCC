// CONATCT NAAY MAP

document.addEventListener("DOMContentLoaded", () => {
  // Initialize the map centered in Tudela, Misamis Occidental
  const map = L.map("map").setView([8.241387, 123.846906], 17);

  // Add OpenStreetMap tiles
  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    attribution: "&copy; OpenStreetMap contributors",
  }).addTo(map);

  // Custom icon for the marker
  const customIcon = L.icon({
    iconUrl: "img/logo.png",
    iconSize: [50, 50],
    iconAnchor: [25, 50],
    popupAnchor: [0, -45],
  });

  // Add marker with popup
  L.marker([8.241387, 123.846906], { icon: customIcon })
    .addTo(map)
    .bindPopup(
      "<b>Tara sa Mis.Occ Main Office</b><br>Municipal Hall, Tudela, Misamis Occidental"
    )
    .openPopup();
});


// EXPLOREEEEEE
document.addEventListener('DOMContentLoaded', () => {
    const sliders = document.querySelectorAll('.slider');
    sliders.forEach(slider => {
        const slides = slider.querySelector('.slides');
        const images = slides.querySelectorAll('img');
        let index = 0;
        const prevBtn = slider.querySelector('.prev');
        const nextBtn = slider.querySelector('.next');

        function showSlide(i) {
            if(i < 0) i = images.length - 1;
            if(i >= images.length) i = 0;
            slides.style.transform = `translateX(-${i * 100}%)`;
            index = i;
        }

        prevBtn.addEventListener('click', () => { showSlide(index - 1); resetInterval(); });
        nextBtn.addEventListener('click', () => { showSlide(index + 1); resetInterval(); });

        let autoSlide = setInterval(() => showSlide(index + 1), 4000);
        function resetInterval() {
            clearInterval(autoSlide);
            autoSlide = setInterval(() => showSlide(index + 1), 4000);
        }
    });
});

