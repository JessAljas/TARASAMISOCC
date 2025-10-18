$(document).ready(function () {
  // -------------------
  // Filter Table
  // -------------------
  $("#statusFilter, #dateFilter, #searchInput").on("change keyup", filterTable);

  function filterTable() {
    const status = $("#statusFilter").val().toLowerCase();
    const date = $("#dateFilter").val();
    const search = $("#searchInput").val().toLowerCase();

    $("#bookingsTable tbody tr").each(function () {
      const rowStatus = $(this).data("status").toLowerCase();
      const rowDate = $(this).data("date");
      const rowText = $(this).text().toLowerCase();

      let show = true;
      if (status && rowStatus !== status) show = false;
      if (date && rowDate !== date) show = false;
      if (search && !rowText.includes(search)) show = false;

      $(this).toggle(show);
    });
  }

  // -------------------
  // Approve/Delete/Completed
  // -------------------
  window.bookingAction = function (action, id, row) {
    const message =
      action === "approve"
        ? "Approve this booking?"
        : action === "delete"
        ? "Delete this booking?"
        : "Mark this booking as completed?";

    if (!confirm(message)) return;

    $.post(
      "",
      { action, id },
      function (data) {
        if (data.success) {
          if (action === "delete") $(row).remove();
          else if (action === "completed") {
            $(row)
              .find(".status-badge")
              .removeClass("status-pending status-approved status-cancelled")
              .addClass("status-completed")
              .text("Completed");
            $(row).find(".approve-btn, .bg-green-600, .bg-yellow-500").remove();
          } else if (action === "approve") {
            $(row)
              .find(".status-badge")
              .removeClass("status-pending")
              .addClass("status-approved")
              .text("Approved");
            $(row).find(".approve-btn").remove();
          }
        } else if (data.error) alert("Error: " + data.error);
      },
      "json"
    );
  };

  // -------------------
  // Payment Proof Modal
  // -------------------
  window.showProof = function (src) {
    $("#proofModalImg").attr("src", src);
    $("#proofModal").removeClass("hidden");
  };

  window.closeModal = function () {
    $("#proofModal").addClass("hidden");
  };

  // -------------------
  // Reschedule Modal
  // -------------------
  let rescheduleBookingId = null;

  window.openRescheduleModal = function (id, oldDate) {
    rescheduleBookingId = id;
    $("#rescheduleBookingId").val(id);
    $("#rescheduleDate").val(oldDate);
    $("#rescheduleReason").val("");
    $("#rescheduleModal").fadeIn();
  };

  $("#rescheduleForm").on("submit", function (e) {
    e.preventDefault();

    const id = $("#rescheduleBookingId").val();
    const new_date = $("#rescheduleDate").val();

    if (!new_date) {
      alert("Please enter a new date.");
      return;
    }

    $.post(
      "",
      { action: "reschedule", id, new_date },
      function (data) {
        if (data.success) {
          // Update table row without reload
          const row = $(`tr[data-id='${id}']`);
          row.data("date", new_date);
          row.find("td:nth-child(4)").text(
            new Date(new_date).toLocaleDateString("en-US", {
              month: "long",
              day: "numeric",
              year: "numeric",
            })
          );
          row
            .find(".status-badge")
            .removeClass(
              "status-pending status-approved status-completed status-cancelled"
            )
            .addClass("status-reschedule_requested")
            .text("Reschedule Requested");

          // Show success message inside modal
          const msg = $("#rescheduleSuccessMsg");
          msg.removeClass("hidden").hide().fadeIn();

          // Hide the message after 3 seconds and then close modal
          setTimeout(() => {
            msg.fadeOut();
            closeRescheduleModal();
          }, 3000);
        } else {
          alert("Error: " + data.error);
        }
      },
      "json"
    );
  });

  // -------------------
  // Delete Modal
  // -------------------
  let deleteBookingId = null;

  window.openDeleteModal = function (id) {
    deleteBookingId = id;
    $("#deleteModal").removeClass("hidden");
  };

  window.closeDeleteModal = function () {
    deleteBookingId = null;
    $("#deleteModal").addClass("hidden");
  };

  $("#confirmDeleteBtn").on("click", function () {
    if (!deleteBookingId) return;

    $.post(
      "",
      { action: "delete", id: deleteBookingId },
      function (data) {
        if (data.success) {
          $(`tr[data-id='${deleteBookingId}']`).remove();
          closeDeleteModal();
        } else {
          alert("Failed to delete booking.");
        }
      },
      "json"
    );
  });

  // -------------------
  // Edit Button Logic
  // -------------------
  $(".editBtn").on("click", function () {
    $("#edit_id").val($(this).data("id"));
    $("#edit_fullname").val($(this).data("fullname"));
    $("#edit_email").val($(this).data("email"));
    $("#edit_phone").val($(this).data("phone"));
    $("#editModal").fadeIn();
  });

  $("#editSuccessMsg").length &&
    setTimeout(() => $("#editSuccessMsg").fadeOut(), 3000);
});
