

(async function() {
    const data = await getData();

    new window.Chart(
        document.getElementById('reservation'),
        {
            type: 'line',
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
                labels: data.map(row => row.year),
                datasets: [
                    {
                        label: 'Rezervace',
                        data: data.map(row => row.count)
                    }
                ]
            }
        }
    );
})();


async function getData() {
    const naja = window.Naja
    const res = await naja.makeRequest("GET", "/admin/home", {
        run: "getChartData"
    }, {
        fetch: {
            credentials: 'include',
        },
    })
}