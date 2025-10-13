// MODAL FUNCTIONS
function openAddModal() {
  document.getElementById("addModal").style.display = "flex";
}
function closeAddModal() {
  document.getElementById("addModal").style.display = "none";
}
function openEditModal() {
  document.getElementById("editModal").style.display = "flex";
}
function closeEditModal() {
  document.getElementById("editModal").style.display = "none";
}
function closeSpotsModal() {
  document.getElementById("spotsModal").style.display = "none";
}

// EDIT BUTTON LOGIC
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".editBtn").forEach((btn) => {
    btn.addEventListener("click", () => {
      document.getElementById("edit_id").value = btn.dataset.id;
      document.getElementById("edit_fullname").value = btn.dataset.fullname;
      document.getElementById("edit_email").value = btn.dataset.email;
      document.getElementById("edit_phone").value = btn.dataset.phone;
      openEditModal();
    });
  });

  // DELETE BUTTON LOGIC
  const deleteModal = document.getElementById("deleteModal");
  const confirmBtn = document.getElementById("confirmDelete");
  const cancelBtn = document.getElementById("cancelDelete");

  document.querySelectorAll(".delete-btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      const url = btn.getAttribute("data-href");
      confirmBtn.setAttribute("href", url);
      deleteModal.classList.remove("hidden");
    });
  });

  cancelBtn.addEventListener("click", () => {
    deleteModal.classList.add("hidden");
  });

  deleteModal.addEventListener("click", (e) => {
    if (e.target === deleteModal) deleteModal.classList.add("hidden");
  });

  // AUTO HIDE SUCCESS MESSAGE
  const msg = document.getElementById("editSuccessMsg");
  if (msg) {
    setTimeout(() => {
      msg.style.display = "none";
    }, 3000);
  }
});

// VIEW SPOTS FUNCTION
function viewSpots(ownerId) {
  const modal = document.getElementById("spotsModal");
  const content = document.getElementById("spotsContent");
  modal.style.display = "flex";
  content.innerHTML = '<p class="text-gray-500">Loading...</p>';

  fetch(
    "agency_registered_tourist_spots.php?action=fetch_spots&owner_id=" + ownerId
  )
    .then((res) => res.json())
    .then((data) => {
      if (data.length === 0) {
        content.innerHTML =
          '<p class="text-gray-500">No tourist spots found.</p>';
        return;
      }
      let html = "";
      data.forEach((spot) => {
        html += `
                <a href="agency_manage_tourist_spots.php?id=${spot.id}" 
                   class="block p-3 border rounded shadow bg-gray-50 mb-2 hover:bg-green-50 transition">
                    <h3 class="font-semibold text-lg text-green-700">${spot.name_of_tourist_spot}</h3>
                    <p class="text-sm text-gray-500">Posted by: ${spot.posted_by_type}</p>
                    <p class="text-sm text-gray-400">Created at: ${spot.created_at}</p>
                </a>`;
      });
      content.innerHTML = html;
    })
    .catch((err) => {
      content.innerHTML = '<p class="text-red-500">Error loading spots.</p>';
      console.error(err);
    });
}

// ===== MODAL =====
function closeModal() {
  document.getElementById("modal").classList.add("hidden");
}
function openModal(isEdit = false, data = {}) {
  const modal = document.getElementById("modal");
  const title = document.getElementById("modal-title");
  const submitBtn = document.getElementById("modal-submit");
  const form = modal.querySelector("form");
  form.reset();
  document.getElementById("update_id").value = "";

  if (isEdit) {
    title.textContent = "Edit Tourist";
    submitBtn.textContent = "Update";
    document.getElementById("update_id").value = data.id;
    document.getElementById("fullname").value = data.fullname;
    document.getElementById("email").value = data.email;
    document.getElementById("phone_number").value = data.phone;
    document.getElementById("address").value = data.address;
  } else {
    title.textContent = "Add Tourist";
    submitBtn.textContent = "Save";
  }
  modal.classList.remove("hidden");
}

// ===== PASSWORD TOGGLE =====
function togglePassword() {
  const field = document.getElementById("password");
  const icon = document.getElementById("toggleIcon");
  if (field.type === "password") {
    field.type = "text";
    icon.classList.add("text-blue-500");
  } else {
    field.type = "password";
    icon.classList.remove("text-blue-500");
  }
}

// ===== EDIT BUTTON HANDLER & AJAX FORM =====
document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll(".edit-btn").forEach((btn) => {
    btn.addEventListener("click", () =>
      openModal(true, {
        id: btn.dataset.id,
        fullname: btn.dataset.fullname,
        email: btn.dataset.email,
        phone: btn.dataset.phone,
        address: btn.dataset.address,
      })
    );
  });

  const form = document.querySelector("#modal form");
  form.addEventListener("submit", function (e) {
    e.preventDefault();
    const formData = new FormData(form);
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "agency_registered_tourist.php", true);
    xhr.onload = function () {
      if (xhr.status === 200) {
        const res = JSON.parse(xhr.responseText);
        if (res.status === "success") {
          // Show success message
          const msgDiv = document.getElementById("successMessage");
          msgDiv.textContent = formData.get("update_id")
            ? "Tourist updated successfully!"
            : "Tourist added successfully!";
          msgDiv.classList.remove("hidden");

          // Hide message after 5 seconds
          setTimeout(() => {
            msgDiv.classList.add("hidden");
            msgDiv.textContent = "";
          }, 5000);

          closeModal(); // close the modal
        } else {
          alert(res.message || "Error saving tourist!");
        }
      } else alert("AJAX request failed!");
    };
    xhr.send(formData);
  });
});
function openDeleteModal(id, name) {
  const modal = document.getElementById("deleteModal");
  const text = document.getElementById("deleteModalText");
  const confirmBtn = document.getElementById("confirmDeleteBtn");

  text.textContent = `Are you sure you want to delete "${name}"? This action cannot be undone.`;
  confirmBtn.href = `?delete=${id}`;
  modal.classList.remove("hidden");
}

function closeDeleteModal() {
  document.getElementById("deleteModal").classList.add("hidden");
}

// Attach event listeners to all delete buttons
document.querySelectorAll(".deleteBtn").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.dataset.id;
    const name = btn.dataset.name;
    openDeleteModal(id, name);
  });
});
