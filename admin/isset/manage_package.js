// ========== SEARCH FUNCTION + AUTO SORT ALPHABETICALLY ==========
document.addEventListener("DOMContentLoaded", () => {
  const searchInput = document.getElementById("searchInput");
  const tbody = document.getElementById("packagesTable");

  if (tbody) {
    // Convert table rows into array
    let rows = Array.from(tbody.querySelectorAll("tr"));

    // ✅ Sort alphabetically by Title (first column)
    rows.sort((a, b) => {
      const titleA = a.cells[0].innerText.toLowerCase();
      const titleB = b.cells[0].innerText.toLowerCase();
      return titleA.localeCompare(titleB);
    });

    // ✅ Re-append sorted rows to table
    rows.forEach((row) => tbody.appendChild(row));

    // ✅ Real-time search filter
    if (searchInput) {
      searchInput.addEventListener("keyup", function () {
        const filter = this.value.toLowerCase();
        rows.forEach((row) => {
          const text = row.innerText.toLowerCase();
          row.style.display = text.includes(filter) ? "" : "none";
        });
      });
    }
  }

  // ========== AUTO-REMOVE MESSAGES ==========
  const errorMsg = document.getElementById("errorMsg");
  const successMsg = document.getElementById("successMsg");

  if (errorMsg) {
    setTimeout(() => {
      errorMsg.style.transition = "opacity 0.5s ease";
      errorMsg.style.opacity = "0";
      setTimeout(() => errorMsg.remove(), 500);
    }, 5000);
  }

  if (successMsg) {
    setTimeout(() => {
      successMsg.style.transition = "opacity 0.5s ease";
      successMsg.style.opacity = "0";
      setTimeout(() => successMsg.remove(), 500);
    }, 5000);
  }
});

// ========== DELETE MODAL ==========
function openDeleteModal(id, title) {
  const modal = document.getElementById("deleteModal");
  const text = document.getElementById("deleteModalText");
  const btn = document.getElementById("confirmDeleteBtn");

  text.textContent = `Are you sure you want to delete the package "${title}"? This action cannot be undone.`;
  btn.href = `?delete_id=${id}`;
  modal.classList.remove("hidden");
}

function closeDeleteModal() {
  document.getElementById("deleteModal").classList.add("hidden");
}

document.querySelectorAll(".deleteBtn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.id;
    const title = btn.dataset.title;
    openDeleteModal(id, title);
  });
});

// ========== EDIT MODAL LOGIC ==========
function openEditModal(id) {
  const pkg = packages.find((p) => p.id == id);
  if (!pkg) return;

  document.getElementById("edit_package_id").value = pkg.id;
  document.getElementById("edit_title").value = pkg.title;
  document.getElementById("edit_price").value = pkg.price;
  document.getElementById("edit_pickup").value = pkg.pickup_location;
  document.getElementById("edit_dropoff").value = pkg.dropoff_location;
  document.getElementById("edit_description").value = pkg.description;

  for (let i = 1; i <= 4; i++) {
    document.getElementById("edit_inclusion" + i).value =
      pkg["inclusion" + i] || "";
    document.getElementById("edit_exclusion" + i).value =
      pkg["exclusion" + i] || "";
  }

  // Destinations
  const destContainer = document.getElementById("edit_dest_container");
  destContainer.innerHTML = "";
  const pkgDests = dest_map[pkg.id] || [];
  spots.forEach((s) => {
    const checked = pkgDests.includes(s.id) ? "checked" : "";
    const div = document.createElement("div");
    div.classList.add("flex", "items-center", "gap-2");
    div.innerHTML = `<input type="checkbox" name="edit_destinations[]" value="${s.id}" ${checked}>
                     <label>${s.name_of_tourist_spot}</label>`;
    destContainer.appendChild(div);
  });

  // Images
  const imagesContainer = document.getElementById("edit_images_container");
  imagesContainer.innerHTML = "";
  for (let i = 1; i <= 4; i++) {
    const imgName = pkg["image" + i];
    if (imgName) {
      const wrapper = document.createElement("div");
      wrapper.className = "relative w-24 h-24 flex-shrink-0";

      const img = document.createElement("img");
      img.src = "../uploads/" + imgName;
      img.className = "w-full h-full object-cover rounded border";
      wrapper.appendChild(img);

      const delBtn = document.createElement("button");
      delBtn.type = "button";
      delBtn.innerHTML = "&times;";
      delBtn.className =
        "absolute top-0 right-0 bg-red-500 text-white w-5 h-5 rounded-full text-xs flex items-center justify-center hover:bg-red-600";
      delBtn.onclick = () => {
        wrapper.remove();
        const hiddenInput = document.createElement("input");
        hiddenInput.type = "hidden";
        hiddenInput.name = "delete_image[]";
        hiddenInput.value = imgName;
        document.querySelector("form").appendChild(hiddenInput);
      };
      wrapper.appendChild(delBtn);
      imagesContainer.appendChild(wrapper);
    }
  }

  // Itinerary
  const itineraryContainer = document.getElementById(
    "edit_itinerary_container"
  );
  itineraryContainer.innerHTML = "";
  const pkgItinerary = itineraries[pkg.id] || itineraries[String(pkg.id)] || [];

  if (pkgItinerary.length === 0) {
    itineraryContainer.innerHTML =
      '<p class="text-gray-500 text-sm">No itinerary data found for this package.</p>';
  } else {
    pkgItinerary.forEach((item) => {
      const div = document.createElement("div");
      div.classList.add("flex", "gap-2", "items-center");
      div.innerHTML = `
        <input type="hidden" name="itinerary_id[${item.id}]" value="${item.id}">
        <input type="time" name="itinerary_time[${item.id}]" value="${
        item.time
      }" class="p-1 border rounded w-20">
        <input type="text" name="itinerary_activity[${item.id}]" value="${
        item.destination_name
      }" placeholder="Destination/Activity" class="p-1 border rounded flex-1">
        <select name="itinerary_type[${item.id}]" class="p-1 border rounded">
          <option value="travel" ${
            item.activity_type === "travel" ? "selected" : ""
          }>Travel</option>
          <option value="arrival" ${
            item.activity_type === "arrival" ? "selected" : ""
          }>Arrival</option>
          <option value="lunch" ${
            item.activity_type === "lunch" ? "selected" : ""
          }>Lunch</option>
        </select>
        <button type="button" onclick="removeRow(this)" class="text-red-500 text-sm">&times;</button>`;
      itineraryContainer.appendChild(div);
    });
  }

  document.getElementById("editModal").classList.remove("hidden");
  document.body.classList.add("overflow-hidden");
}

function closeEditModal() {
  document.getElementById("editModal").classList.add("hidden");
  document.body.classList.remove("overflow-hidden");
}

function addNewItineraryRow() {
  const container = document.getElementById("edit_itinerary_container");
  const index = "new" + Date.now();
  const div = document.createElement("div");
  div.classList.add("flex", "gap-2", "items-center");
  div.innerHTML = `
    <input type="time" name="itinerary_time[${index}]" class="p-1 border rounded w-20">
    <input type="text" name="itinerary_activity[${index}]" placeholder="Destination/Activity" class="p-1 border rounded flex-1">
    <select name="itinerary_type[${index}]" class="p-1 border rounded">
      <option value="travel">Travel</option>
      <option value="arrival" selected>Arrival</option>
      <option value="lunch">Lunch</option>
    </select>
    <button type="button" onclick="removeRow(this)" class="text-red-500 text-sm">&times;</button>`;
  container.appendChild(div);
}

function removeRow(button) {
  button.closest("div").remove();
}
