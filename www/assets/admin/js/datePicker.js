$(function () {
  const data = document.getElementById("date-start");
  const start = data.getAttribute("data-start");
  const end = data.getAttribute("data-end");

  if (start && end) {
    $("#date-start").daterangepicker(
      {
        timePicker: true,
        linkedCalendars: false,
        timePicker24Hour: true,
        autoApply: true,
        startDate: start,
        endDate: end,
        alwaysShowCalendars: true,
        minDate: getCurrentDate(),
        showISOWeekNumbers: true,
        opens: "center",
        drops: "auto",
        timePickerIncrement: 5,
        locale: {
          format: "DD/MM/YYYY HH:mm",
          separator: " - ",
          applyLabel: "Uložit",
          cancelLabel: "Zrušit",
          fromLabel: "Od",
          toLabel: "Do",
          customRangeLabel: "Custom",
          weekLabel: "T",
          daysOfWeek: ["Ne", "Po", "Ut", "St", "Čt", "Pá", "So"],
          monthNames: [
            "Leden",
            "Únor",
            "Březen",
            "Duben",
            "Květen",
            "Červen",
            "Červenec",
            "Srpen",
            "Září",
            "Ríjen",
            "Listopad",
            "Prosinec",
          ],
          firstDay: 1,
        },
      },
      function (start, end, label) {
        
      }
    );
  }

  $("#date-start").daterangepicker(
    {
      timePicker: true,
      linkedCalendars: false,
      timePicker24Hour: true,
      autoApply: true,
      alwaysShowCalendars: true,
      minDate: getCurrentDate(),
      showISOWeekNumbers: true,
      opens: "center",
      drops: "auto",
      timePickerIncrement: 5,
      locale: {
        format: "DD/MM/YYYY HH:mm",
        separator: " - ",
        applyLabel: "Uložit",
        cancelLabel: "Zrušit",
        fromLabel: "Od",
        toLabel: "Do",
        customRangeLabel: "Custom",
        weekLabel: "T",
        daysOfWeek: ["Ne", "Po", "Ut", "St", "Čt", "Pá", "So"],
        monthNames: [
          "Leden",
          "Únor",
          "Březen",
          "Duben",
          "Květen",
          "Červen",
          "Červenec",
          "Srpen",
          "Září",
          "Ríjen",
          "Listopad",
          "Prosinec",
        ],
        firstDay: 1,
      },
    },
    function (start, end, label) {
      
    }
  );

  $(".single-date").daterangepicker(
    {
      timePicker: false,
      singleDatePicker: true,
      linkedCalendars: false,
      showCustomRangeLabel: false,
      minDate: getCurrentDate(),
      opens: "center",
      drops: "auto",
      autoApply: true,
      locale: {
        format: "DD/MM/YYYY",
        separator: " - ",
        applyLabel: "Uložit",
        cancelLabel: "Zrušit",
        fromLabel: "Od",
        toLabel: "Do",
        customRangeLabel: "Custom",
        weekLabel: "T",
        daysOfWeek: ["Ne", "Po", "Ut", "St", "Čt", "Pá", "So"],
        monthNames: [
          "Leden",
          "Únor",
          "Březen",
          "Duben",
          "Květen",
          "Červen",
          "Červenec",
          "Srpen",
          "Září",
          "Ríjen",
          "Listopad",
          "Prosinec",
        ],
        firstDay: 1,
      },
    },
    function (start, end, label) {
      
    }
  );

  function getCurrentDate() {
    const today = new Date();
    const month = String(today.getMonth() + 1).padStart(2, "0"); // months are 0-indexed
    const day = String(today.getDate()).padStart(2, "0");
    const year = today.getFullYear();

    return day + "/" + month + "/" + year;
  }
});

function createRangePicker() {
  const data = document.getElementById("filter-range");
  let start;
  let end;
  let now = new Date();

  if (data.getAttribute("data-start") && data.getAttribute("data-end")) {
    start = data.getAttribute("data-start") ?? `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear()}`;
    end = data.getAttribute("data-end") ?? `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear()}`;
    $('#filter-range').val(start + ' - ' + end);
  } else {
    start = `${now.getDate()}/${now.getMonth()+1}/${now.getFullYear()}`;
    end = start;
  }

  $('#filter-range').on('apply.daterangepicker', function(ev, picker) {
    $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
});

  $("#filter-range").daterangepicker(
    {
      showWeekNumbers: true,
      showISOWeekNumbers: true,
      autoApply: true,
      autoUpdateInput: false,
      startDate: start,
      endDate: end,
      locale: {
        format: "DD/MM/YYYY",
        separator: " - ",
        applyLabel: "Potvrdit",
        cancelLabel: "Zrušit",
        fromLabel: "Od",
        toLabel: "Do",
        customRangeLabel: "Vlastní",
        weekLabel: "T",
        daysOfWeek: ["Ne", "Po", "Ut", "St", "Čt", "Pá", "So"],
        monthNames: [
          "Leden",
          "Únor",
          "Březen",
          "Duben",
          "Květen",
          "Červen",
          "Červenec",
          "Srpen",
          "Září",
          "Ríjen",
          "Listopad",
          "Prosinec",
        ],
        firstDay: 1,
      },
      alwaysShowCalendars: true,
      parentEl: "filter-range",
      opens: "left",
      buttonClasses: "btn",
      cancelClass: "btn",
    },
    function (start, end, label) {
      
    }
  );
}

createRangePicker();
