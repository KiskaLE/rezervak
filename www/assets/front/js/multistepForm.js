
let currentTab = 0; // Current tab is set to be the first tab (0)
showTab(currentTab); // Display the current tab


function showTab(n) {
    // This function will display the specified tab of the form ...
    let x = document.getElementsByClassName("tab");
    x[n].style.display = "block";
    // ... and fix the Previous/Next buttons:
    if (n == 0) {
        document.getElementById("prevBtn").style.display = "none";
        document.getElementById("nextBtn").style.display = "none";
    } else {
        document.getElementById("prevBtn").style.display = "inline";
        document.getElementById("nextBtn").style.display = "inline";
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
        createCalendar();
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

    let datas = $('#regForm').serialize();
    datas = datas.split("&");
    //remove last element (_sumbit)
    datas.pop();
    console.log(datas)
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
    button.href = `/reservation/create?date=${day}&service_id=${service}&do=sendDate`;
    button.click();
}

function setService(id) {
    console.log(id);
    document.querySelector("[name='service']").value = id;
    nextPrev(currentTab+1);

}

function createCalendar() {

    getAvailableDays().then((data) => {
        const container = document.querySelector("#calendar");
        const curDate = new Date();
        const firstDateOfMonth = new Date(curDate.getFullYear(), curDate.getMonth(), 1);
        const curMonth = curDate.getMonth();
        const curYear = curDate.getFullYear();
        const availableDays = data;
        const lastDayOfMonth = new Date(curDate.getFullYear(), curDate.getMonth() + 1, 0);
        let days = [];

        container.innerHTML = "";

        for (let i = 0; i < getDayIndexMondaySunday(firstDateOfMonth); i++) {
            const th = document.createElement("th");
            th.className = "day unavailable";
            days.push(th);
        }
        //week
        for (let i = 1; i <= curDate.getDate(); i++) {
            const date = new Date(curYear, curMonth, i);
            const th = document.createElement("th");
            th.className = "day unavailable";
            if (date.getDay() === 0 || date.getDay() === 6) {
                th.className += " weekend";
            }
            th.innerHTML = i;

            days.push(th);
        }

        for (let i = curDate.getDate()+1; i < lastDayOfMonth.getDate(); i++) {
            const date = new Date(curYear, curMonth, i);
            const th = document.createElement("th");
            th.className = "day";
            if (date.getDay() === 0 || date.getDay() === 6) {
                th.className += " weekend";
            }
            th.innerHTML = i;
            let isFull = true;
            for (let j = 0; j < availableDays.length; j++) {
                console.log(availableDays[j] + ": " + date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate() );
                if (availableDays[j] == date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate()) {
                    isFull = false;
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
        }
    });
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