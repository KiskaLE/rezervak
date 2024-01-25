function deleteException(event) {
  const res = confirm("Opravdu chcete smazat tuto přestávku?");
  if (!res) {
    event.preventDefault();
  }
}

function deleteCode(event) {
  const res = confirm("Opravdu chcete smazat tento kód?");
  if (!res) {
    event.preventDefault();
  }
}

function deleteServiceCustomSchedule(event) {
  const res = confirm("Opravdu chcete smazat tento rozvrh?");
  if (!res) {
    event.preventDefault();
  }
}
function getDateRangeTimestamps(dateRange) {
  // Split the date range into start and end date strings
  let [startDateStr, endDateStr] = dateRange.split(" - ");

  // Function to parse date in "dd/mm/yyyy hh:mm" format
  function parseDate(dateStr) {
    let [datePart, timePart] = dateStr.split(" ");
    let [day, month, year] = datePart.split("/");
    let [hours, minutes] = timePart.split(":");

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
  const dropdown = document.getElementById("user-dropdown");
  dropdown.style.display = dropdown.style.display === "none" ? "block" : "none";
}

function closeDropdown(event) {
  var dropdown = document.getElementById("user-dropdown");
  var userAvatar = document.getElementById("user-avatar");

  if (!dropdown.contains(event.target) && !userAvatar.contains(event.target)) {
    dropdown.style.display = "none";
  }
}

// Event listener for closing the dropdown
document.addEventListener("click", closeDropdown);

function sidebarOpen() {
  const sidebar = document.querySelector(".left-sidebar-container");
  const mainContent = document.querySelector("main");
  sidebar.style.transform = "translateX(0%)";
  mainContent.style.marginLeft =
    "calc(var(--left-nav-with) + var(--main-padding))";
}

function sidebarClose() {
  const sidebar = document.querySelector(".left-sidebar-container");
  const mainContent = document.querySelector("main");
  sidebar.style.transform = "translateX(-100%)";
  mainContent.style.marginLeft = "0px";
}

function listItemToggle(listItemId) {
  const listItem = document.getElementById(listItemId);
  const listItemBody = listItem.querySelector(".list-item-body");
  const isClosed =
    listItemBody.style.height === "" || listItemBody.style.height === "0px";

  // Close all other items first
  document.querySelectorAll(".list-item-body").forEach((item) => {
    item.style.height = "0px";
    item.style.opacity = "0";
    item.classList.remove("open");
  });

  // Toggle the current item
  if (isClosed) {
    // Temporarily display the element to get its height
    listItemBody.style.display = "grid";
    const height = listItemBody.scrollHeight + "px";
    listItemBody.style.height = "0px";
    listItemBody.style.opacity = "0";
    setTimeout(() => {
      listItemBody.style.height = height;
      listItemBody.style.opacity = "1";
    }, 10); // Start the animation after a small delay
    listItemBody.classList.add("open");
    listItem.classList.add("open");
  } else {
    listItemBody.style.height = "0px";
    listItemBody.style.opacity = "0";
    listItemBody.classList.remove("open");
    listItem.classList.remove("open");
  }

  // Clean up after the transition ends
  listItemBody.addEventListener(
    "transitionend",
    function () {
      if (isClosed) {
        listItemBody.style.display = "grid";
      } else {
        listItemBody.style.display = "none";
      }
    },
    { once: true }
  );
}

function listFilterToggle() {
  const toggle = document.getElementById("list-filter-toggle");
  toggle.style.display = toggle.style.display === "none" ? "block" : "none";
}

function closelistFilterToggle(event) {
  var dropdown = document.getElementById("list-filter-toggle");
  var filter = document.getElementById("list-filter");

  if (!dropdown.contains(event.target) && !filter.contains(event.target)) {
    dropdown.style.display = "none";
  }
}

document.addEventListener("click", closelistFilterToggle);

function setTab(tabId) {
  const tab = document.getElementById("tab" + tabId);
  tab.classList.add("selected");
}

//workhours
$(function () {
  $(".weekday-group").each(function () {
    const checkbox = $(this).find(".weekday-checkbox input");

    if (checkbox.is(":checked")) {
      $(this).find(".weekday-times").show();
    } else {
      $(this).find(".weekday-times").hide();
    }

    checkbox.on("change", function () {
      if ($(this).is(":checked")) {
        $(this).parents(".weekday-group").find(".weekday-times").show();
      } else {
        $(this).parents(".weekday-group").find(".weekday-times").hide();
      }
    });
  });

  $("#holiday-add").on("click", function () {
    console.log("open modal");
    //open modal with form
    const modal = $("#holiday-add-modal");
    modal.modal("show");
  });
});

//modals
$(function () {
  $("#change-password").on("click", function() {
    console.log("open modal");
    //open modal with form
    const modal = $("#change-password-modal");
    modal.modal("show");
  })
});
