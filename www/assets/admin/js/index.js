

function deleteException(event) {
    const res = confirm('Opravdu chcete smazat tuto přestávku?');
    console.log(res)
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

