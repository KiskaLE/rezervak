// import Naja from "naja";

// const naja = new Naja();

// naja.initialize();

//modals
document.addEventListener("DOMContentLoaded", function () {
  $(function () {
    $("#backup-info").on("click", function () {
      //open modal with form
      console.log("open modal");
      const modal = $("#backup-info-modal");
      modal.modal("show");
    });
  });
});
