async function changeDay(service) {
  const button = document.querySelector("#load-day");
  const day = document.querySelector("[name='date']").value;
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
  if (window.innerWidth > 900) {
    //box.style.display = "block";
    box.classList.add("open");
  } else {
    toggleCalendarTimes();
  }
  calendarLoading(false);
  timesLoading(false);
  document.querySelectorAll(".calendar-reservation-date").forEach((item) => {
    item.innerHTML = time[2] + "." + time[1] + "." + time[0];
  });
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

function setService(id, name, price, duration) {
  if (document.readyState === "complete") {
    // Fully loaded!
    document.querySelector("[name='service']").value = id;
    const recap = document.querySelector("#service");
    const box = document.querySelector(".calendar-times");
    box.classList.remove("open");

    recap.innerHTML = name;
    document
      .querySelectorAll(".calendar-reservation-service")
      .forEach((item) => {
        item.innerHTML = name;
      });
    document.querySelectorAll(".calendar-reservation-price").forEach((item) => {
      item.innerHTML = price.toLocaleString("cs-CZ").replace(/,/g, " ");
    });
    document
      .querySelectorAll(".calendar-reservation-duration")
      .forEach((item) => {
        item.innerHTML = duration;
      });

    document.querySelector("#price").innerHTML = price
      .toLocaleString("cs-CZ")
      .replace(/,/g, " ");
    nextPrev(1);
  }
}

function setTime(id, type) {
  document.querySelector("[name='time']").value = id;
  document.querySelector("[name='dateType']").value = type;
  console.log(document.querySelector("#submit2"));
  document.querySelector("#submit2").click();
}

function calNext(n, service_id) {
  //get mont and year from data attribute
  showMonth = Number(
    document.querySelector("#calendar").getAttribute("data-month")
  );
  showYear = Number(
    document.querySelector("#calendar").getAttribute("data-year")
  );
  showMonth += n;
  if (showMonth < 0) {
    showMonth = 11;
    showYear--;
  } else if (showMonth > 11) {
    showMonth = 0;
    showYear++;
  }
  createCalendar(showMonth, showYear, service_id);
}

function timesLoading(isLoading) {
  const loading = $("#times-loading");
  const container = document.querySelector("#snippet--content");
  const timesList = document.querySelectorAll(".button-time");
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

async function createCalendar(month, year, service_id, availableDays = []) {
  const container = document.querySelector("#calendar");
  calendarLoading(true);
  month = Number(month);
  year = Number(year);
  console.log(month, year);
  //write mont and year to data attribute
  container.setAttribute("data-month", month);
  container.setAttribute("data-year", year);

  const calendarTitle = document.querySelector("#calendar-month");
  const curDate = new Date();
  const firstDateOfMonth = new Date(year, month, 1);
  const lastDayOfMonth = new Date(year, month + 1, 0);
  console.log(lastDayOfMonth);
  if (availableDays.length == 0) {
    availableDays = await getAvailableDays(service_id);
  }
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
        changeDay(service_id);
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
}

async function getAvailableDays(service_id) {
  let naja = window.Naja;
  const res = await naja.makeRequest(
    "POST",
    `/`,
    {
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
  const service = document.querySelector("#service_id").value;
  if (service != null) {
    if (code == null) {
      code = "";
    }
    let naja = window.Naja;
    const res = await naja.makeRequest(
      "POST",
      "/",
      {
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
    console.log(res);
    if (res.status == false && code != "") {
      document.querySelector("[name='dicountCode']").className = "invalid";
    } else if (code == "") {
      document.querySelector("[name='dicountCode']").className = "";
    } else {
      document.querySelector("[name='dicountCode']").className = "valid";
    }

    if (res.price != undefined) {
      document.querySelector("#price").innerHTML = res.price
        .toLocaleString("cs-CZ")
        .replace(/,/g, " ");
    }
    //show code success
  }
}
