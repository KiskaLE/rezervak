
function deleteException(event) {
    const res = confirm('Opravdu chcete smazat tuto přestávku?');
    if (!res) {
        event.preventDefault()
    }
}

function deleteCode(event) {
    const res = confirm('Opravdu chcete smazat tento kód?');
    if (!res) {
        event.preventDefault()
    }
}

function deleteServiceCustomSchedule(event) {
    const res = confirm('Opravdu chcete smazat tento rozvrh?');
    if (!res) {
        event.preventDefault()
    }
}
function getDateRangeTimestamps(dateRange) {
    // Split the date range into start and end date strings
    let [startDateStr, endDateStr] = dateRange.split(' - ');

    // Function to parse date in "dd/mm/yyyy hh:mm" format
    function parseDate(dateStr) {
        let [datePart, timePart] = dateStr.split(' ');
        let [day, month, year] = datePart.split('/');
        let [hours, minutes] = timePart.split(':');

        // Create a new date object, adjusting month index by 1
        let date = new Date(year, month - 1, day, hours, minutes);
        return date;
    }

    // Parse the start and end dates to get Date objects
    let startDate = parseDate(startDateStr);
    let endDate = parseDate(endDateStr);

    // Get the timestamps
    let startTimestamp = startDate.getTime();
    let endTimestamp = endDate.getTime();

    return [startTimestamp, endTimestamp];
}


