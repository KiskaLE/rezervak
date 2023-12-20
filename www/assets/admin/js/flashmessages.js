
    // Get the flash messages container
    var flashMessages = document.getElementById("flash-messages");

    // Check if the container exists
    if (flashMessages) {
        // Apply a CSS class to trigger the fading effect
        setTimeout(function () {
            flashMessages.style.animation = "fadeOut 1s linear normal forwards";
        }, 4000)

        // Set a timeout to remove the container after a specified time (e.g., 5 seconds)
        setTimeout(function() {
            flashMessages.remove();
        }, 6000); // 5000 milliseconds = 5 seconds
    }