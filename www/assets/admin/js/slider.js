document.addEventListener('DOMContentLoaded', () => {
    const timeSlider = document.getElementById('timeSlider');
    const timeDisplay = document.getElementById('timeDisplay');

    const startTime = document.querySelector("[name='start']");
    const stopTime = document.querySelector("[name='stop']");
    const datasets = document.querySelector("#timeSlider");
    let max = 1440;
    let min = 0;
    let start = 0;
    let stop = 0;
    let isBreak = true
    try {
        max = convertTimeToMinutes(datasets.dataset.stop);
        min = convertTimeToMinutes(datasets.dataset.start);
    } catch (error) {
        isBreak = false

    }
    if (isBreak) {
        start = convertTimeToMinutes(datasets.dataset.start);
        stop = convertTimeToMinutes(datasets.dataset.stop);
    }

    if (startTime && stopTime) {
        start = convertTimeToMinutes(startTime.value);
        stop = convertTimeToMinutes(stopTime.value);
    }

    noUiSlider.create(timeSlider, {

        start: [(start)? start : 0, (stop)? stop : 1440], // Start at 10:00 and end at 14:00 (in minutes)
        connect: true,
        range: {
            'min': (isBreak) ? min : 0, // 10:00 AM
            'max': (isBreak) ? max : 1440 // 14:00 PM
        },
        step: 5,
        format: {
            to: function (value) {
                return formatTime(Math.round(value));
            },
            from: function (value) {
                return Number(value);
            }
        }
    });

    // Function to format minutes into HH:MM format
    function formatTime(minutes) {
        let hours = Math.floor(minutes / 60);
        let mins = minutes % 60;
        return `${hours.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}`;
    }

    function convertTimeToMinutes(time) {
        let [hours, mins] = time.split(':');
        return Number(hours) * 60 + Number(mins);
    }

    timeSlider.noUiSlider.on('update', function (values) {
        startTime.value = values[0];
        stopTime.value = values[1];
        timeDisplay.textContent = `${values[0]} - ${values[1]}`;
    });
});
