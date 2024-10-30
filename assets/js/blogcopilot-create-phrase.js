document.addEventListener("DOMContentLoaded", function() {
    var form = document.getElementById("blogcopilot-create-form");
    var spinnerContainer = document.getElementById("blogcopilot-spinner-container");
    var myDiv = document.getElementById("blogcopilot-create-form-div");

    form.onsubmit = function() {
        spinnerContainer.style.display = "flex"; // Show the spinner
        myDiv.style.opacity = "0.5";  // 50% opacity

        // Disable form inputs and buttons
        var formElements = form.querySelectorAll('button');
        formElements.forEach(function(element) {
            element.disabled = true;
        });
    };           
});