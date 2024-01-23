try {
    let calendar
    
} catch (error) {
    
}
/**
 * Create a full calendar with the specified slot duration, calendar period, service duration, service name, and parent ID.
 *
 * @param {number} slotDuration - The duration of each time slot in minutes.
 * @param {number} calendarPeriod - The duration of each calendar period in minutes.
 * @param {number} serviceDuration - The duration of each service in minutes.
 * @param {string} serviceName - The name of the service.
 * @param {string} parentId - The ID of the parent element where the calendar will be rendered.
 */

function createFullCalendar(slotDuration ,calendarPeriod, serviceDuration, serviceName, parentId) {   
    calendar = new FullCalendar.Calendar(document.getElementById(parentId), {
            locale: 'cs',
            eventOverlap: false,
            allDaySlot: false,
            aspectRatio: 1,
            selectable: true,
            editable: true,
            themeSystem: "standard",
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: "dayGridMonth timeGridWeek timeGridDay",
                center: "title",
                right: "today prevYear,prev,next,nextYear"
            },
            firstDay: 1,
            timeZone: 'utc',
            views: {
                timeGridSevenDay: {
                    type: 'timeGrid',
                    duration: {
                        days: 7},
                }
            },
            buttonText: {
                today:    'Dnes',
                month:    'Měsíc',
                week:     'Týden',
                day:      'Den',
                list:     'List',
                timeGridSevenDay: "Den"
            },
            titleFormat: { year: 'numeric', month: 'long', day: 'numeric' },
            slotDuration: slotDuration,
            nowIndicator: true,
            select: function (selectionInfo) {
                const currentView = calendar.view;
                if (currentView.type === 'timeGridDay' || currentView.type === "timeGridWeek") {
                        // Clear any previous selections
                    calendar.unselect(); // Set your event duration here

                    const numberOfPeriods = Math.ceil(serviceDuration/calendarPeriod);

                    // Start time of the first event
                    let startTime = window.Moment(selectionInfo.start);
                    let endTime = window.Moment(selectionInfo.end);

                    // Loop to create back-to-back events
                    let count = 0;
                    while (startTime < endTime) {
                        let eventEndTime = startTime.clone().add(serviceDuration, 'minutes');

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
                                title: serviceName,
                                start: startTime.toDate(),
                                end: eventEndTime.toDate()
                            });
                        }

                        // Set start time for the next event to be the end time of the current event
                        startTime.add(serviceDuration, 'minutes');
                        count++
                    }
                    // Clear the selection
                    calendar.unselect();
                }
                
            },
            eventClick: function (info) {
                const currentView = calendar.view;
                if (currentView.type === 'timeGridDay' || currentView.type === "timeGridWeek") {
                    info.event.remove();
                }
            },
            dateClick: function (info) {
                const currentView = calendar.view;
                if (currentView.type === 'dayGridMonth') {
                    calendar.changeView('timeGridDay', info.dateStr); 
                }
            }
        });
        calendar.render();
        
    
}

function getAndProcessCalendarEvents(input) {
    document.getElementById(input).value = getEventsAsJson();

    function getEventsAsJson() {
        const events = calendar.getEvents();
        const jsonEvents = events.map(function(event) {
            return {
                start: event.start,
                end: event.end ? event.end : null,
            };
        });

        return JSON.stringify(jsonEvents, null, 2); // Beautify the JSON output
    }
}

function addEvent(title, start, end) {
        calendar.addEvent({
            title: title,
            start: start,
            end: end
        })
}

function addEvents(events) {
    JSON.parse(events).forEach(function(event) {
        calendar.addEvent({
            title: event.title,
            start: event.start,
            end: event.end
        })
    })
}