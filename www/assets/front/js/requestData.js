

function selectDay() {
    let data = document.getElementById("date").value;
    console.log(data)

    fetch("/reservation/create", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(data)
    })
        .then(response => response.json())
        .then(payload => {
            // processing the response
        });



}