$(function () {
  $("#backup-info-button").on("click", function () {
    //open modal with form
    console.log("open modal");
    const modal = $("#backup-info-modal");
    modal.modal("show");
  });
});
