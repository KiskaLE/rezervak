
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

