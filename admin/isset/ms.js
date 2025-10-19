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
    if (deleteModal) deleteModal.classList.remove("hidden");
  });
});

// Confirm deletion
if (confirmDelete) {
  confirmDelete.addEventListener("click", () => {
    if (currentDeleteId) {
      window.location.href =
        window.location.pathname + "?delete_id=" + currentDeleteId;
    }
  });
}

// Cancel modal
if (cancelDelete) {
  cancelDelete.addEventListener("click", () => {
    deleteModal.classList.add("hidden");
  });
}

// Close modal if clicking outside
if (deleteModal) {
  deleteModal.addEventListener("click", (e) => {
    if (e.target === deleteModal) deleteModal.classList.add("hidden");
  });
}

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

// ==================== Messenger-style chat logic ====================
function openChat(id, name, email, message, subject, date, status) {
  const chatHeader = document.getElementById("chatHeader");
  const chatBody = document.getElementById("chatBody");
  const chatFooter = document.getElementById("chatFooter");

  if (!chatHeader || !chatBody || !chatFooter) return; // skip if not in Messenger layout

  chatHeader.classList.remove("hidden");
  chatBody.classList.remove("hidden");
  chatFooter.classList.remove("hidden");

  document.getElementById("chatName").innerText = name;
  document.getElementById("chatEmail").innerText = email;
  document.getElementById("chatSubject").innerText =
    "Subject: " + (subject || "No subject");
  document.getElementById("chatDate").innerText = "Sent: " + date;
  document.getElementById("chatMessage").innerText = message;

  const markReadBtn = document.getElementById("markReadBtn");
  const deleteBtn = document.getElementById("deleteBtn");

  if (markReadBtn) markReadBtn.href = "?read_id=" + id;
  if (deleteBtn) {
    deleteBtn.onclick = function () {
      if (confirm("Are you sure you want to delete this message?")) {
        window.location.href = "?delete_id=" + id;
      }
    };
  }
}
