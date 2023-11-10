document.addEventListener("DOMContentLoaded", function() {
    const submitButton = document.getElementById("btn-submit");
    console.log(submitButton)
    submitButton.addEventListener("click", function(event) {
        const form = document.getElementById("basicForm");
        let isValid = true;


        // Validate sampleRate
        let sampleRate = document.getElementsByName("sampleRate")[0];
        if (sampleRate.value <= 0) {
            document.getElementById("sampleRateError").style.display = 'block';
            isValid = false;
        } else {
            document.getElementById("sampleRateError").style.display = 'none';
        }

        // Validate paymentInfo - assuming it should be a numeric value
        let paymentInfo = document.getElementsByName("paymentInfo")[0];
        if (false) {
            // Show some error message for paymentInfo
            isValid = false;
        }

        // Validate verificationTime
        let verificationTime = document.getElementsByName("verificationTime")[0];
        if (verificationTime.value <= 0) {
            document.getElementById("verificationTimeError").style.display = 'block';
            isValid = false;
        } else {
            document.getElementById("verificationTimeError").style.display = 'none';
        }

        // Validate numberOfDays
        let numberOfDays = document.getElementsByName("numberOfDays")[0];
        if (numberOfDays.value <= 0) {
            document.getElementById("numberOfDaysError").style.display = 'block';
            isValid = false;
        } else {
            document.getElementById("numberOfDaysError").style.display = 'none';
        }

        // Validate timeToPay
        let timeToPay = document.getElementsByName("timeToPay")[0];
        if (timeToPay.value <= 0) {
            document.getElementById("timeToPayError").style.display = 'block';
            isValid = false;
        } else {
            document.getElementById("timeToPayError").style.display = 'none';
        }

        console.log(isValid)
        // If any validation failed, prevent the form from submitting
        if (isValid) {
            form.submit();
        }
    });
});