
    function toggleFields() {
        console.log("toggle")
        const checkbox = document.getElementById('servicesCustom');
        const inputFields = document.getElementById("servicesCustomFields");
        if (checkbox.checked) {
            inputFields.classList.remove('hidden');
        } else {
            inputFields.classList.add('hidden');
        }
    }

    // Initial check
    toggleFields();

    // Event listener

