// Tab Switching
const tabCurrent = document.getElementById("tab-current");
const tabHistory = document.getElementById("tab-history");
const currentSection = document.getElementById("current-section");
const historySection = document.getElementById("history-section");

if (tabCurrent && tabHistory) {
  tabCurrent.addEventListener("click", () => {
    currentSection.classList.remove("hidden");
    historySection.classList.add("hidden");
    tabCurrent.classList.add("bg-green-600", "text-white");
    tabCurrent.classList.remove("bg-gray-300", "text-gray-700");
    tabHistory.classList.remove("bg-green-600", "text-white");
    tabHistory.classList.add("bg-gray-300", "text-gray-700");
  });

  tabHistory.addEventListener("click", () => {
    historySection.classList.remove("hidden");
    currentSection.classList.add("hidden");
    tabHistory.classList.add("bg-green-600", "text-white");
    tabHistory.classList.remove("bg-gray-300", "text-gray-700");
    tabCurrent.classList.remove("bg-green-600", "text-white");
    tabCurrent.classList.add("bg-gray-300", "text-gray-700");
  });
}

// Edit Modal
function openEditModal() {
  const modal = document.getElementById("editModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeEditModal() {
  const modal = document.getElementById("editModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
}

// Proof Modal
function showProof(src) {
  document.getElementById("proofImg").src = src;
  const modal = document.getElementById("proofModal");
  modal.classList.remove("hidden");
  modal.classList.add("flex");
}

function closeProof() {
  const modal = document.getElementById("proofModal");
  modal.classList.add("hidden");
  modal.classList.remove("flex");
  document.getElementById("proofImg").src = "";
}

// Profile Image Preview
const profileInput = document.getElementById("profileInput");
if (profileInput) {
  profileInput.addEventListener("change", function () {
    const file = this.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function (ev) {
      document.getElementById("profileDisplay").src = ev.target.result;
    };
    reader.readAsDataURL(file);
  });
}

// Tourist Spot slider SEARCH-----------------
document.querySelectorAll(".slider").forEach((slider) => {
  const slides = slider.querySelector(".slides");
  const images = slides.querySelectorAll("img");
  let index = 0;
  const prevBtn = slider.querySelector(".prev");
  const nextBtn = slider.querySelector(".next");
  function showSlide(i) {
    if (i < 0) i = images.length - 1;
    if (i >= images.length) i = 0;
    slides.style.transform = `translateX(-${i * 100}%)`;
    index = i;
  }
  prevBtn.addEventListener("click", () => showSlide(index - 1));
  nextBtn.addEventListener("click", () => showSlide(index + 1));
  let autoSlide = setInterval(() => showSlide(index + 1), 4000);
  function resetInterval() {
    clearInterval(autoSlide);
    autoSlide = setInterval(() => showSlide(index + 1), 4000);
  }
});
