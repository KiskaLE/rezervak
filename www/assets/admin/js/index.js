
const deleteExceptions = document.querySelectorAll(".deleteException");
for (const deleteExceptionsKey in deleteExceptions) {
    deleteExceptionsKey.addEventListener("click", (event) => {
        console.log("test")
        const res = confirm('Opravdu chcete smazat tuto přestávku?');
        if (!res) {
            event.preventDefault()
        }
    });
}

function deleteException(event) {
    const res = confirm('Opravdu chcete smazat tuto přestávku?');
    if (!res) {
        event.preventDefault()
    }
}