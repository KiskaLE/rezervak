
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

function toggleDropdown() {
    console.log("test")
    const dropdown = document.getElementById('user-dropdown');
    dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
}

function closeDropdown(event) {
    var dropdown = document.getElementById('user-dropdown');
    var userAvatar = document.getElementById('user-avatar');
    
    if (!dropdown.contains(event.target) && !userAvatar.contains(event.target)) {
        dropdown.style.display = 'none';
    }
}

// Event listener for closing the dropdown
document.addEventListener('click', closeDropdown);


function sidebarOpen() {
    const sidebar = document.querySelector('.left-sidebar-container');
    const mainContent = document.querySelector('main');
    sidebar.style.transform = 'translateX(0%)';
    mainContent.style.marginLeft = 'calc(var(--left-nav-with) + var(--main-padding))';
}

function sidebarClose() {
    const sidebar = document.querySelector('.left-sidebar-container');
    const mainContent = document.querySelector('main');
    sidebar.style.transform = 'translateX(-100%)';
    mainContent.style.marginLeft = '0px';
}

function listItemToggle(listItemId) {
    const listItem = document.getElementById(listItemId);
    const listItemBody = listItem.querySelector('.list-item-body');
    //close all .list-item-body if opening
    if (listItemBody.style.display === 'none') {
        const listItems = document.querySelectorAll('.list-item-body');
        listItems.forEach(item => {
            item.style.display = 'none';
            const svg = item.parentElement.querySelector('svg');
            svg.style.transform ='rotate(0deg)';
        });
    }
    //get listItem by id
    //get listItem children .list-item-body
    const svg = listItem.querySelector('svg');
    svg.style.transform = svg.style.transform === 'rotate(90deg)' ? 'rotate(0deg)' : 'rotate(90deg)';
    listItemBody.style.display = listItemBody.style.display === 'none' ? 'flex' : 'none';



}

function listFilterToggle() {
    const toggle = document.getElementById("list-filter-toggle");
    toggle.style.display = toggle.style.display === 'none' ? 'block' : 'none';
}

function closelistFilterToggle(event) {
    var dropdown = document.getElementById('list-filter-toggle');
    var filter = document.getElementById('list-filter');
    
    if (!dropdown.contains(event.target) && !filter.contains(event.target)) {
        dropdown.style.display = 'none';
    }
}

document.addEventListener('click', closelistFilterToggle);

function setTab(tabId) {
    const tab = document.getElementById('tab'+tabId);
    tab.classList.add('selected');
    
}

