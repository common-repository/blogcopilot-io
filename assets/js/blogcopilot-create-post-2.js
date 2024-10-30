document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById("blogcopilot-recreate-form");
    var spinnerContainer = document.getElementById("blogcopilot-spinner-container");
    var myDiv = document.getElementById("blogcopilot-image-selection-div");
    var buttons = document.querySelectorAll("button");

    form.onsubmit = function() {
        spinnerContainer.style.display = "flex"; // Show the spinner
        myDiv.style.opacity = "0.5";  // 50% opacity
        buttons.forEach(function(button) {
            button.disabled = true; // Disable each button
        });      
    };
});