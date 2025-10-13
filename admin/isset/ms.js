// ==================== Toggle card body ====================
function toggleCard(header) {
  const body = header.nextElementSibling;
  if (body) body.classList.toggle("hidden");
}

// ==================== Delete modal logic ====================
const deleteModal = document.getElementById("deleteModal");
const confirmDelete = document.getElementById("confirmDelete");
const cancelDelete = document.getElementById("cancelDelete");

let currentDeleteId = null;

// Open modal when clicking any delete button
document.querySelectorAll(".deleteBtn").forEach((btn) => {
  btn.addEventListener("click", () => {
    currentDeleteId = btn.dataset.id;
    deleteModal.classList.remove("hidden");
  });
});

// Confirm deletion
confirmDelete.addEventListener("click", () => {
  if (currentDeleteId) {
    window.location.href =
      window.location.pathname + "?delete_id=" + currentDeleteId;
  }
});

// Cancel modal
cancelDelete.addEventListener("click", () => {
  deleteModal.classList.add("hidden");
});

// Close modal if clicking outside
deleteModal.addEventListener("click", (e) => {
  if (e.target === deleteModal) deleteModal.classList.add("hidden");
});

// ==================== Auto-remove temporary messages ====================
document.querySelectorAll(".auto-remove-msg").forEach((msg) => {
  setTimeout(() => {
    if (msg) {
      msg.style.transition = "opacity 0.5s";
      msg.style.opacity = "0";
      setTimeout(() => msg.remove(), 500);
    }
  }, 5000);
});

