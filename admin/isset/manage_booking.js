// Filter table
function filterTable() {
  let status = $("#statusFilter").val().toLowerCase();
  let date = $("#dateFilter").val();
  let search = $("#searchInput").val().toLowerCase();

  $("#bookingsTable tbody tr").each(function () {
    let rowStatus = $(this).data("status").toLowerCase();
    let rowDate = $(this).data("date");
    let rowText = $(this).text().toLowerCase();

    let show = true;
    if (status && rowStatus !== status) show = false;
    if (date && rowDate !== date) show = false;
    if (search && !rowText.includes(search)) show = false;
    $(this).toggle(show);
  });
}

// AJAX Approve/Delete/Completed
function bookingAction(action, id, row) {
  let message =
    action === "approve"
      ? "Approve this booking?"
      : action === "delete"
      ? "Delete this booking?"
      : "Mark this booking as completed?";

  if (confirm(message)) {
    $.post(
      "",
      { action: action, id: id },
      function (data) {
        if (data.success) {
          if (action === "delete") row.remove();
          else if (action === "completed") {
            row
              .find(".status-badge")
              .removeClass("status-pending status-approved status-cancelled")
              .addClass("status-completed")
              .text("Completed");
            row.find(".approve-btn, .bg-green-600, .bg-yellow-500").remove();
          } else if (action === "approve") {
            row
              .find(".status-badge")
              .removeClass("status-pending")
              .addClass("status-approved")
              .text("Approved");
            row.find(".approve-btn").remove();
          }
        } else if (data.error) alert("Error: " + data.error);
      },
      "json"
    );
  }
}

// Modals
function showProof(src) {
  $("#proofModalImg").attr("src", src);
  $("#proofModal").removeClass("hidden");
}
function closeModal() {
  $("#proofModal").addClass("hidden");
}

function openRescheduleModal(id, currentDate) {
  $("#rescheduleBookingId").val(id);
  $("#rescheduleDate").val(currentDate);
  $("#rescheduleModal").show();
}
function closeRescheduleModal() {
  $("#rescheduleModal").hide();
}

$(document).ready(function () {
  $("#rescheduleForm").on("submit", function (e) {
    e.preventDefault();
    let id = $("#rescheduleBookingId").val();
    let newDate = $("#rescheduleDate").val();
    if (confirm("Change booking date to " + newDate + "?")) {
      $.post(
        "",
        { action: "reschedule", id: id, new_date: newDate },
        function (data) {
          if (data.success) {
            alert("Booking date updated!");
            $('tr[data-id="' + id + '"] td:nth-child(4)').text(
              new Date(newDate).toLocaleDateString("en-US", {
                month: "long",
                day: "numeric",
                year: "numeric",
              })
            );
            closeRescheduleModal();
          } else if (data.error) alert("Error: " + data.error);
        },
        "json"
      );
    }
  });
});

let deleteBookingId = null; // Store current booking id

function openDeleteModal(id, btn) {
  deleteBookingId = id;
  document.getElementById("deleteModal").classList.remove("hidden");
}

function closeDeleteModal() {
  deleteBookingId = null;
  document.getElementById("deleteModal").classList.add("hidden");
}

// Confirm delete
document
  .getElementById("confirmDeleteBtn")
  .addEventListener("click", function () {
    if (!deleteBookingId) return;

    $.post("", { action: "delete", id: deleteBookingId }, function (response) {
      let res = JSON.parse(response);
      if (res.success) {
        // Remove row from table
        const row = document.querySelector(`tr[data-id='${deleteBookingId}']`);
        if (row) row.remove();
        closeDeleteModal();
      } else {
        alert("Failed to delete booking.");
      }
    });
  });

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

  // AUTO HIDE SUCCESS MESSAGE
  const msg = document.getElementById("editSuccessMsg");
  if (msg)
    setTimeout(() => {
      msg.style.display = "none";
    }, 3000);

  // DELETE MODAL LOGIC
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

  // Optional: close modal by clicking outside
  deleteModal.addEventListener("click", (e) => {
    if (e.target === deleteModal) deleteModal.classList.add("hidden");
  });
});
