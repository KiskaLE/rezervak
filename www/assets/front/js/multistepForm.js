let currentTab = 0; // Current tab is set to be the first tab (0)
let tabCounter = 0;
showTab(currentTab); // Display the current tab
let showMonth = new Date().getMonth();
let showYear = new Date().getFullYear();
let address = new URL(window.location.href);
let searchParams = address.searchParams;

async function showTab(n) {
  // This function will display the specified tab of the form ...
  let x = document.getElementsByClassName("tab");
  x[n].style.display = "flex";

  // ... and fix the Previous/Next buttons:
  document.getElementById("prevBtn").style.display = "none";
  if (n == 0) {
    document.getElementById("prevBtn").style.display = "none";
  }
  if (n < 2) {
    document.getElementById("nextBtn").style.display = "none";
  } else {
    // document.getElementById("prevBtn").style.display = "inline";
    document.getElementById("nextBtn").style.display = "inline";
  }
  // if (n == 1) {
  //     document.getElementById("prevBtn").style.display = "inline";
  // }
  if (n == x.length - 1) {
    document.getElementById("nextBtn").innerHTML = "Odeslat";
  } else {
    document.getElementById("nextBtn").innerHTML = "Další";
  }

  if (n == x.length - 1) {
    document.getElementById("nextBtn").innerHTML = "Odeslat";
  }
  //render recap
  if (currentTab >= x.length - 1) {
    setRecap();
  }
  //render calendar
  if (currentTab == 1) {
    showMonth = new Date().getMonth();
    showYear = new Date().getFullYear();
    await createCalendar(showMonth, showYear);
  }

  // ... and run a function that displays the correct step indicator:
  if (n == 2) {
    await changeDay();
  }
  fixStepIndicator(n);
}

async function nextPrev(n) {
  // This function will figure out which tab to display
  let x = document.getElementsByClassName("tab");
  // Exit the function if any field in the current tab is invalid:
  if (n == 1 && !validateForm()) return false;
  // Hide the current tab:
  x[currentTab].style.display = "none";
  // Increase or decrease the current tab by 1:
  currentTab = currentTab + n;
  // if you have reached the end of the form... :
  if (currentTab >= x.length) {
    //...the form gets submitted:
    document.getElementById("regForm").submit();
    return false;
  }

  // Otherwise, display the correct tab:
  await showTab(currentTab);
  tabCounter++;
}

async function goToTab(n) {
  let x = document.getElementsByClassName("tab");
  // activeted tabs
  const tabs = document.querySelectorAll(".step");

  if (n < currentTab) {
    x[currentTab].style.display = "none";
    currentTab = n;
    await showTab(currentTab);
  }
  //remove active from tabs berore active tab
  for (let i = 0; i < tabs.length; i++) {
    if (i >= n) {
      tabs[i].classList.remove("finish");
    }
  }
}

function validateForm() {
  // This function deals with validation of the form fields
  let x,
    y,
    i,
    valid = true;
  x = document.getElementsByClassName("tab");
  y = x[currentTab].getElementsByTagName("input");
  // A loop that checks every input field in the current tab:
  for (i = 0; i < y.length; i++) {
    // If a field is empty...
    let name = y[i].name;
    let value = y[i].value;
    y[i].className = "multiform";
    const parentEl = y[i].parentNode;
    parentEl.classList.remove("invalid");
    if (name == "service" || name == "time") {
      if (!value.match(/\d+/)) {
        y[i].className += " invalid";
        valid = false;
      }
    } else if (name == "date") {
      if (!value.match(/^\d{4}\-(0[1-9]|1[012])\-(0[1-9]|[12][0-9]|3[01])$/)) {
        y[i].className += " invalid";
        valid = false;
      }
    } else if (name == "firstname" || name == "lastname" || name == "city") {
      if (value.match(/\d+/) || value.length == 0) {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "phone") {
      if (!value.match(/^\+?[1-9]\d{1,14}$/)) {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "email") {
      if (!value.match(/[^@ \t\r\n]+@[^@ \t\r\n]+\.[^@ \t\r\n]+/)) {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "address") {
      if (value == "") {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "code") {
      if (!value.match(/^\d{5}$/)) {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "dateType") {
      if (!(value == "default" || value == "backup")) {
        y[i].className += " invalid";
        parentEl.classList.add("invalid");
        valid = false;
      }
    } else if (name == "discountCode") {
      //TODO validate discount code
    }
  }
  // If the valid status is true, mark the step as finished and valid:
  if (valid) {
    document.getElementsByClassName("step")[currentTab].className += " finish";
  }
  return valid; // return the valid status
}

function fixStepIndicator(n) {
  // This function removes the "active" class of all steps...
  let i,
    x = document.getElementsByClassName("step");
  for (i = 0; i < x.length; i++) {
    x[i].className = x[i].className.replace(" active", "");
  }
  //... and adds the "active" class to the current step:
  x[n].className += " active";
}

async function createRecap() {
  const container = document.getElementById("recap");
  container.innerHTML = "";

  //get form data into objest with key and value format, key = name, value = value, text is in uft-8 format;
  const inputs = document.querySelectorAll(".multiform");
  let data = [];
  for (let i = 0; i < inputs.length; i++) {
    const input = inputs[i];
    data.push({
      name: input.name,
      value: input.value,
    });
  }
  // generate text from data
  for (let i = 0; i < data.length; i++) {
    const div = document.createElement("div");
    const h2 = document.createElement("h2");
    const p = document.createElement("p");
    p.id = data[i].name;
    h2.innerHTML = data[i].name;

    div.appendChild(h2);
    div.appendChild(p);
    container.appendChild(div);
  }
}

function setRecap() {
  let data = document.querySelectorAll(".multiform");
  for (let i = 0; i < data.length; i++) {
    if (
      data[i].name == "service" ||
      data[i].name == "time" ||
      data[i].name == "dateType" ||
      data[i].name == "date"
    ) {
      continue;
    }
    document.querySelector("#" + data[i].name).innerHTML = data[i].value;
  }
}

async function getServiceName(service_id) {
  let naja = window.Naja;
  await naja
    .makeRequest(
      "GET",
      "/",
      {
        run: "getServiceName",
        service_id: service_id,
      },
      {
        fetch: {
          credentials: "include",
        },
      }
    )
    .then((payload) => {
      // processing the response
    });
}

async function getTime(time_id) {
  let naja = window.Naja;
  const res = await naja.makeRequest(
    "GET",
    "/",
    {
      u: searchParams.get("u"),
      run: "getTime",
      time_id: time_id,
    },
    {
      fetch: {
        credentials: "include",
      },
    }
  );
  console.log(res.time);
  return Promise.resolve(res.time);
}

function getOption(name, number) {
  //get all select options into array
  let selectElement = document.getElementsByName(name)[0];
  let options = selectElement.options;
  let optionsArray = [];
  for (let i = 0; i < options.length; i++) {
    optionsArray.push(options[i].text);
  }

  return optionsArray[parseInt(number)];
}

async function changeDay() {
  const button = document.querySelector("#load-day");
  const day = document.querySelector("[name='date']").value;
  const service = document.querySelector("[name='service']").value;
  const box = document.querySelector(".calendar-times");
  let naja = window.Naja;
  if (!box.classList.contains("open")) {
    calendarLoading(true);
  }
  timesLoading(true);
  await naja
    .makeRequest(
      "GET",
      "/",
      {
        u: searchParams.get("u"),
        run: "setDate",
        day: day,
        service_id: service,
      },
      {
        fetch: {
          credentials: "include",
        },
      }
    )
    .then((payload) => {
      // processing the response
    });
  let time = day.split("-");
  const timesTitle = document.querySelector("#calendar-selected-date");
  timesTitle.innerHTML = time[2] + "." + time[1] + "." + time[0];

  //set recap
  const date = document.getElementById("date");
  date.innerHTML = time[2] + "." + time[1] + "." + time[0];
  if (window.innerWidth > 770) {
    //box.style.display = "block";
    box.classList.add("open");
  } else {
    toggleCalendarTimes();
  }
  calendarLoading(false);
  timesLoading(false);
}

function toggleCalendarTimes() {
  const calendar = document.querySelector(".calendar-date");
  const times = document.querySelector(".calendar-times");
  const back = document.querySelector("#times-back");
  if (times.style.display) {
    if (times.style.display != "none") {
      back.classList.add("hidden");
      calendar.style.display = "block";
      times.style.display = "none";
    } else {
      back.classList.remove("hidden");
      calendar.style.display = "none";
      times.style.display = "block";
      removeDaySelected();
    }
  } else {
    back.classList.remove("hidden");
    calendar.style.display = "none";
    times.style.display = "block";
    removeDaySelected();
  }
}

function setService(id, name, price) {
  if (document.readyState === "complete") {
    // Fully loaded!
    document.querySelector("[name='service']").value = id;
    const recap = document.querySelector("#service");
    const box = document.querySelector(".calendar-times");
    box.classList.remove("open");

    recap.innerHTML = name;
    document.querySelector("#price").innerHTML = price;
    nextPrev(1);
  }
}

function setTime(id, type, timeStart, timeEnd) {
  document.querySelector("[name='time']").value = id;
  document.querySelector("[name='dateType']").value = type;
  const timeStartEl = document.querySelector("#time");
  timeStartEl.innerHTML = timeStart;
  const timeEndEl = document.querySelector("#time-end");
  timeEndEl.innerHTML = timeEnd;
  nextPrev(1);
}

function calNext(n) {
  showMonth += n;
  if (showMonth < 0) {
    showMonth = 11;
    showYear--;
  } else if (showMonth > 11) {
    showMonth = 0;
    showYear++;
  }
  createCalendar(showMonth, showYear);
}

function timesLoading(isLoading) {
  const loading = $("#times-loading");
  const container = document.querySelector("#snippet--content");
  const timesList = document.querySelectorAll(".button-time");
  console.log(container);
  if (isLoading) {
    loading.show();
    container.style.filter = "grayscale(100%)";
    timesList.forEach((item) => {
      item.style.pointerEvents = "none";
    });
  } else {
    loading.hide();
    container.style.filter = "grayscale(0%)";
  }
}

function calendarLoading(isLoading) {
  const loading = $("#calendar-times-loading");
  const container = document.querySelector("#calendar");
  const navList = document.querySelectorAll("#calendar-nav img");
  if (isLoading) {
    navList.forEach((item) => {
      item.style.pointerEvents = "none";
    });
    loading.show();
    container.style.filter = "grayscale(100%)";
    const cover = document.createElement("div");
    cover.id = "calendar-cover";
    container.appendChild(cover);
  } else {
    navList.forEach((item) => {
      item.style.pointerEvents = "auto";
    });
    loading.hide();
    container.style.filter = "grayscale(0%)";
    const cover = document.getElementById("calendar-cover");
    if (cover) {
      cover.remove();
    }
  }
}

async function createCalendar(month, year) {
  const container = document.querySelector("#calendar");
  calendarLoading(true);

  const calendarTitle = document.querySelector("#calendar-month");
  const curDate = new Date();
  const curMonth = curDate.getMonth();
  const firstDateOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  const availableDays = await getAvailableDays();
  container.style.filter = "grayscale(0%)";
  container.innerHTML = "";
  let curMonthName = "";
  switch (month) {
    case 0:
      curMonthName = "Leden";
      break;
    case 1:
      curMonthName = "Únor";
      break;
    case 2:
      curMonthName = "Březen";
      break;
    case 3:
      curMonthName = "Duben";
      break;
    case 4:
      curMonthName = "Květen";
      break;
    case 5:
      curMonthName = "Červen";
      break;
    case 6:
      curMonthName = "Červenec";
      break;
    case 7:
      curMonthName = "Srpen";
      break;
    case 8:
      curMonthName = "Záři";
      break;
    case 9:
      curMonthName = "Říjen";
      break;
    case 10:
      curMonthName = "Listopad";
      break;
    case 11:
      curMonthName = "Prosinec";
  }
  calendarTitle.innerHTML = curMonthName + " " + year;
  //days in calendar
  let days = [];
  //create last month days
  const date = new Date(year, month, 1);
  date.setMonth(date.getMonth() - 1);
  date.setDate(0);
  const daysInPreviousMonth = date.getDate();
  const legend = ["Po", "Út", "St", "Čt", "Pá", "So", "Ne"];
  for (let i = 0; i < legend.length; i++) {
    const div = document.createElement("div");
    div.className = "day-legend";
    div.innerHTML = legend[i];
    container.appendChild(div);
  }
  for (let i = 0; i < getDayIndexMondaySunday(firstDateOfMonth); i++) {
    const div = document.createElement("div");
    div.classList.add("day");
    div.classList.add("unavailable");
    div.classList.add("different-month");
    div.innerHTML =
      daysInPreviousMonth - getDayIndexMondaySunday(firstDateOfMonth) + i + 1;
    container.appendChild(div);
  }

  for (let i = 1; i < lastDayOfMonth.getDate() + 1; i++) {
    const date = new Date(year, month, i);
    const div = document.createElement("div");
    div.innerHTML = i;
    div.className = "day";
    if (date <= curDate) {
      div.classList.add("unavailable");
    }
    if (date.getDay() === 0 || date.getDay() === 6) {
      div.classList.add("weekend");
    }
    let isFull = true;
    for (let j = 0; j < availableDays?.length; j++) {
      if (
        availableDays[j] ==
        date.getFullYear() +
          "-" +
          (date.getMonth() + 1).toString().padStart(2, "0") +
          "-" +
          date.getDate().toString().padStart(2, "0")
      ) {
        isFull = false;
        break;
      }
    }
    if (isFull) {
      div.classList.add("unavailable");
    } else {
      div.classList.add("available");
      div.addEventListener("click", () => {
        document.querySelector("[name='date']").value =
          date.getFullYear() +
          "-" +
          (date.getMonth() + 1).toString().padStart(2, "0") +
          "-" +
          date.getDate().toString().padStart(2, "0");
        removeDaySelected();
        div.classList.add("selected");
        changeDay();
      });
    }
    container.appendChild(div);
    container.style.visibility = "visible";
  }

  //add days to end of week from next month
  for (let i = 0; i < 7 - getDayIndexMondaySunday(lastDayOfMonth) - 1; i++) {
    const div = document.createElement("div");
    div.classList.add("day");
    div.classList.add("unavailable");
    div.classList.add("different-month");
    div.innerHTML = i + 1;
    container.appendChild(div);
  }
  calendarLoading(false);

  function getDayIndexMondaySunday(date) {
    return date.getDay() === 0 ? 6 : date.getDay() - 1;
  }

  async function getAvailableDays() {
    let naja = window.Naja;
    const service_id = document.querySelector("[name='service']").value;
    const res = await naja.makeRequest(
      "POST",
      `/`,
      {
        u: searchParams.get("u"),
        run: "fetch",
        service_id: service_id,
      },
      {
        fetch: {
          credentials: "include",
        },
      }
    );
    return Promise.resolve(res.availableDates);
  }
}

function removeDaySelected() {
  const days = document.querySelectorAll(".day.selected");
  if (days != null) {
    for (let i = 0; i < days.length; i++) {
      days[i].classList.remove("selected");
    }
  }
}

function debounce(func, delay) {
  let debounceTimer;
  return function () {
    const context = this;
    const args = arguments;
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => func.apply(context, args), delay);
  };
}

const debouncedVerify = debounce(verify, 300);

async function verify() {
  let code = document.querySelector("[name='dicountCode']").value;
  const service = document.querySelector("[name='service']").value;
  if (service != null) {
    if (code == null) {
      code = "";
    }
    let naja = window.Naja;
    const res = await naja.makeRequest(
      "POST",
      "/",
      {
        u: searchParams.get("u"),
        run: "verifyCode",
        discountCode: code,
        service_id: service,
      },
      {
        fetch: {
          credentials: "include",
        },
      }
    );
    if (res.status == false) {
      document.querySelector("[name='dicountCode']").className = "invalid";
    } else {
      document.querySelector("[name='dicountCode']").className = "valid";
    }

    if (res.price != undefined) {
      document.querySelector("#price").innerHTML = res.price;
    }
    //show code success
  }
}
