// manage_packages.js

document.addEventListener("DOMContentLoaded", () => {
  const modal = document.getElementById("viewModal");

  window.openModal = function (pkg) {
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
    document.getElementById("modal_image1").src = pkg.image1
      ? imgBase + pkg.image1
      : "placeholder.png";
    document.getElementById("modal_image2").src = pkg.image2
      ? imgBase + pkg.image2
      : "placeholder.png";
    document.getElementById("modal_image3").src = pkg.image3
      ? imgBase + pkg.image3
      : "placeholder.png";
    document.getElementById("modal_image4").src = pkg.image4
      ? imgBase + pkg.image4
      : "placeholder.png";
  };

  window.closeModal = function () {
    modal.classList.add("hidden");
  };
});
