document.addEventListener('DOMContentLoaded', function () {
    const calendarEl = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(calendarEl, {
        locale: 'cs',
        eventOverlap: false,
        aspectRatio: 2,
        selectable: true,
        editable: false,
        initialView: 'timeGridFourDay',
        views: {
            timeGridFourDay: {
                type: 'timeGrid',
                duration: {days: 7}
            }
        },
        slotDuration: "00:30:00",
        nowIndicator: true,
        events: [
            {
                start: '1970-01-01T01:00:00',
                end: '2023-11-10T16:00:00',
                display: 'background'
            },
            {
                start: '2024-01-10T00:00:00',
                end:  '2100-01-10T10:00:00',
                display: 'background'
            }

        ],
        select: function (selectionInfo) {
            // Clear any previous selections
            calendar.unselect();
            let eventDuration = 60; // Set your event duration here

            // Start time of the first event
            let startTime = new Date(selectionInfo.start);
            let endTime = new Date(selectionInfo.end);

            // Loop to create back-to-back events
            let count = 0;
            while (startTime < endTime) {
                let eventEndTime = new Date(startTime.getTime() + eventDuration * 60000);

                // If the calculated end time exceeds the selected end time, break the loop
                if (eventEndTime > endTime) {
                    if (count !== 0) {
                        break;
                    }
                }

                // Function to check for event conflicts
                function isEventConflict(eventStart, eventEnd) {
                    let events = calendar.getEvents();
                    return events.some(function (existingEvent) {
                        return (
                            eventStart < existingEvent.end && eventEnd > existingEvent.start
                        );
                    });
                }

                // Check for conflicts
                if (!isEventConflict(startTime, eventEndTime)) {
                    // If no conflict, add the event
                    calendar.addEvent({
                        title: 'The Title',
                        start: startTime,
                        end: eventEndTime
                    });
                }

                // Set start time for the next event to be the end time of the current event
                startTime = eventEndTime;
                count++
            }

            // Clear the selection
            calendar.unselect();
        },
        eventClick: function (info) {
            info.event.remove();
        }
    });
    calendar.render();
});