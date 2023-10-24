let currentTab = 0; // Current tab is set to be the first tab (0)
let tabCounter = 0;
showTab(currentTab); // Display the current tab
let showMonth = new Date().getMonth();
let showYear = new Date().getFullYear();


function showTab(n) {
    // This function will display the specified tab of the form ...
    let x = document.getElementsByClassName("tab");
    x[n].style.display = "block";
    // ... and fix the Previous/Next buttons:
    if (n == 0) {
        document.getElementById("prevBtn").style.display = "none";
    }
    if (n < 2) {
        document.getElementById("nextBtn").style.display = "none";
    } else {
        document.getElementById("prevBtn").style.display = "inline";
        document.getElementById("nextBtn").style.display = "inline";
    }
    if (n == 1) {
        document.getElementById("prevBtn").style.display = "inline";
    }
    if (n == (x.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Odeslat";
    } else {
        document.getElementById("nextBtn").innerHTML = "Další";
    }

    if (n == (x.length - 1)) {
        document.getElementById("nextBtn").innerHTML = "Odeslat";
    }
    //render recap
    if (currentTab >= x.length-1) {
        recap();
    }
    //render calendar
    if (currentTab == "1") {
        createCalendar(showMonth, showYear);
    }

    // ... and run a function that displays the correct step indicator:
    if (n == 2) {
        changeDay();
    }
    fixStepIndicator(n)
}

function nextPrev(n) {
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
    showTab(currentTab);
    tabCounter++;
}

function goToTab(n) {
    let x = document.getElementsByClassName("tab");
    // activeted tabs
    const activeTabs = document.getElementsByClassName("step finish");

    if (n<= activeTabs.length) {
        x[currentTab].style.display = "none";
        currentTab = n;
        showTab(currentTab);
    }
}

function validateForm() {
    // This function deals with validation of the form fields
    let x, y, i, valid = true;
    x = document.getElementsByClassName("tab");
    y = x[currentTab].getElementsByTagName("input");
    // A loop that checks every input field in the current tab:
    for (i = 0; i < y.length; i++) {
        // If a field is empty...
        if (y[i].value == "") {
            // add an "invalid" class to the field:
            y[i].className += " invalid";
            // and set the current valid status to false:
            valid = false;
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
    let i, x = document.getElementsByClassName("step");
    for (i = 0; i < x.length; i++) {
        x[i].className = x[i].className.replace(" active", "");
    }
    //... and adds the "active" class to the current step:
    x[n].className += " active";
}

function recap(){
    const container = document.getElementById("recap");
    container.innerHTML = "";

    //get form data into objest with key and value format, key = name, value = value, text is in uft-8 format;
    const inputs = document.querySelectorAll(".multiform");
    let data = []
    for (let i = 0; i <inputs.length; i++) {
        const input = inputs[i];
        data.push({
            name: input.name,
            value: input.value
        })
    }
    // generate text from data
    for (let i = 0; i < data.length; i++) {
        const div = document.createElement("div");
        const p = document.createElement("p");
        p.innerHTML = data[i].name + ": " + data[i].value;
        div.appendChild(p);
        container.appendChild(div);
    }



  /*
    let datas = $('#regForm').serialize();
    datas = datas.split("&");
    //remove last element (_sumbit)
    datas.pop();
    for (let i = 0; i < datas.length; i++) {
        let data = datas[i].split("=");
        const div = document.createElement("div");
        const p = document.createElement("p");
        if (i == 0) {
            //p.innerHTML = getOption("service", data[1]);
        }else if (i == 1){
            //p.innerHTML = getOption("time", data[1]);
        }else {
            p.innerHTML = data[1];
        }

        const h2 = document.createElement("h2");
        h2.innerHTML = data[0];
        div.appendChild(h2);
        div.appendChild(p);
        document.getElementById("recap").appendChild(div);
    }
    */
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


function writeData() {
    const dummi = document.querySelector(".dummiTime").value;
    const time = document.querySelector(".time");

    time.value = dummi;
}

function changeDay() {
    const button = document.querySelector("#load-day");
    const day = document.querySelector("[name='date']").value;
    const service = document.querySelector("[name='service']").value;
    let naja = window.Naja;
    naja.makeRequest("GET", "/reservation/create", {run: "setDate", day: day, service_id: service}, {
        fetch: {
            credentials: 'include',
        },
    })
}

function setService(id) {
    console.log(id);
    document.querySelector("[name='service']").value = id;
    nextPrev(currentTab+1);

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
    console.log(showMonth, showYear)
    createCalendar(showMonth,showYear);
}
async function createCalendar(month, year) {
        const container = document.querySelector("#calendar");
        const calendarTitle = document.querySelector("#calendar-month")
        const curDate = new Date();
        const curMonth = curDate.getMonth();
        const firstDateOfMonth = new Date(year, month, 1);
        const lastDayOfMonth = new Date(year, month+1, 0);
        const availableDays = await getAvailableDays();

        calendarTitle.innerHTML = "";
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
        calendarTitle.innerHTML = curMonthName+" "+year;

        //days in calendar
        let days = [];
        //create last month days
        for (let i = 0; i < getDayIndexMondaySunday(firstDateOfMonth); i++) {
            const th = document.createElement("th");
            th.className = "day unavailable";
            days.push(th);
        }
        //week
        for (let i = 1; i < lastDayOfMonth.getDate()+1; i++) {
            const date = new Date(year, month, i);
            const th = document.createElement("th");
            th.innerHTML = i;
            th.className = "day";
            if (date <=  curDate) {
                th.className += " unavailable";
                days.push(th);
                continue;
            }

            if (date.getDay() === 0 || date.getDay() === 6) {
                th.className += " weekend";
            }
            let isFull = true;
            for (let j = 0; j < availableDays.length; j++) {
                if (availableDays[j] == date.getFullYear() + "-" + (date.getMonth() + 1).toString().padStart(2, '0') + "-" + date.getDate().toString().padStart(2, '0')) {
                    isFull = false;
                    break;
                }
            }
            if (isFull){
                th.className += " unavailable"
            } else {
                th.className += " available"
                th.addEventListener("click", () => {
                    document.querySelector("[name='date']").value = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
                    document.querySelector("#nextBtn").click();
                });
            }
            days.push(th);
        }

        //split days into weeks
        let week = document.createElement("tr");
        for (let i = 0; i < days.length; i++) {
            if (i % 7 === 0) {
                container.appendChild(week);
                week = document.createElement("tr");
                week.className = "week";
            }
            week.appendChild(days[i]);
            if (i === days.length - 1) {
                container.appendChild(week);
            }
        };
    function getDayIndexMondaySunday(date) {
        return date.getDay() === 0 ? 6 : date.getDay() - 1
    }

    async function getAvailableDays() {
        let naja = window.Naja;
        const req = await naja.makeRequest("GET", "/reservation/create", {run: "fetch"}, {
            fetch: {
                credentials: 'include',
            },
        })
        return Promise.resolve(req.availableDates);



    }


}