$(function() {
    const data = document.getElementById("date-start")
    const start = data.getAttribute('data-start')
    const end = data.getAttribute('data-end')

    if (start && end) {
        $("#date-start").daterangepicker({
            "timePicker": true,
            "linkedCalendars": false,
            "timePicker24Hour": true,
            "autoApply": true,
            "startDate": start,
            "endDate": end,
            "alwaysShowCalendars": true,
            "minDate": getCurrentDate(),
            "showISOWeekNumbers": true,
            "opens": "center",
            "drops": "auto",
            "timePickerIncrement": 5,
            "locale": {
                "format": "DD/MM/YYYY HH:mm",
                "separator": " - ",
                "applyLabel": "Uložit",
                "cancelLabel": "Zrušit",
                "fromLabel": "Od",
                "toLabel": "Do",
                "customRangeLabel": "Custom",
                "weekLabel": "T",
                "daysOfWeek": [
                    "Ne",
                    "Po",
                    "Ut",
                    "St",
                    "Čt",
                    "Pá",
                    "So"
                ],
                "monthNames": [
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
                    "Prosinec"
                ],
                "firstDay": 1
            },
        }, function(start, end, label) {
            console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
        });
    }

    $("#date-start").daterangepicker({
        "timePicker": true,
        "linkedCalendars": false,
        "timePicker24Hour": true,
        "autoApply": true,
        "alwaysShowCalendars": true,
        "minDate": getCurrentDate(),
        "showISOWeekNumbers": true,
        "opens": "center",
        "drops": "auto",
        "timePickerIncrement": 5,
        "locale": {
            "format": "DD/MM/YYYY HH:mm",
            "separator": " - ",
            "applyLabel": "Uložit",
            "cancelLabel": "Zrušit",
            "fromLabel": "Od",
            "toLabel": "Do",
            "customRangeLabel": "Custom",
            "weekLabel": "T",
            "daysOfWeek": [
                "Ne",
                "Po",
                "Ut",
                "St",
                "Čt",
                "Pá",
                "So"
            ],
            "monthNames": [
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
                "Prosinec"
            ],
            "firstDay": 1
        },
    }, function(start, end, label) {
        console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
    });


    $(".single-date").daterangepicker({
        "timePicker": false,
        "singleDatePicker": true,
        "linkedCalendars": false,
        "showCustomRangeLabel": false,
        "minDate": getCurrentDate(),
        "opens": "center",
        "drops": "auto",
        "autoApply": true,
        "locale": {
            "format": "DD/MM/YYYY",
            "separator": " - ",
            "applyLabel": "Uložit",
            "cancelLabel": "Zrušit",
            "fromLabel": "Od",
            "toLabel": "Do",
            "customRangeLabel": "Custom",
            "weekLabel": "T",
            "daysOfWeek": [
                "Ne",
                "Po",
                "Ut",
                "St",
                "Čt",
                "Pá",
                "So"
            ],
            "monthNames": [
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
                "Prosinec"
            ],
            "firstDay": 1
        },
    }, function(start, end, label) {
        console.log('New date range selected: ' + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD') + ' (predefined range: ' + label + ')');
    });





    function getCurrentDate() {
        const today = new Date();
        const month = String(today.getMonth() + 1).padStart(2, '0'); // months are 0-indexed
        const day = String(today.getDate()).padStart(2, '0');
        const year = today.getFullYear();


        return day + '/' + month + '/' + year;
    }
});