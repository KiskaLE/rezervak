

document.addEventListener("DOMContentLoaded", () => {
    (async function () {

        const data = await getData();

        new window.Chart(
            document.getElementById('reservation'),
            {
                type: 'bar',
                options: {
                    animation: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            enabled: false
                        }
                    }
                },
                data: {
                    labels: data.map(row => row.date),
                    datasets: [
                        {
                            label: 'Rezervace',
                            data: data.map(row => row.value)
                        }
                    ]
                }
            }
        );
    })();

})


async function getData() {
    const naja = window.Naja
    const res = await naja.makeRequest("GET", "/admin", {
        replaceHistory: false,
        run: "getChartData"
    }, {
        fetch: {
            credentials: 'include',
        },
    })
    return Promise.resolve(res);
}