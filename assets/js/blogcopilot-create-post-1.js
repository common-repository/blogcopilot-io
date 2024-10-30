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

    var collapseElement = document.getElementById('additionalFields');
    var toggleButton = document.getElementById("additionalFieldsButton");

    collapseElement.addEventListener('show.bs.collapse', function () {
        toggleButton.textContent = 'Hide Optional Fields'; // Text when the section is expanded
    });

    collapseElement.addEventListener('hide.bs.collapse', function () {
        toggleButton.textContent = 'Show Optional Fields'; // Text when the section is collapsed
    });

    var premiumCheck = document.getElementById('premiumCheck');
    var premiumArticleLengthDiv = document.getElementById('premiumArticleLength');
    var premiumArticleLengthSlider = document.getElementById('premiumArticleLengthSlider');
    var premiumArticleLengthValue = document.getElementById('premiumArticleLengthValue');

    function togglePremiumArticleLength(checked) {
        premiumArticleLengthDiv.style.display = checked ? 'block' : 'none';
    }

    premiumCheck.addEventListener('change', function() {
        togglePremiumArticleLength(this.checked);
    });

    premiumArticleLengthSlider.addEventListener('input', function() {
        premiumArticleLengthValue.textContent = this.value + ' words';
    });

    togglePremiumArticleLength(premiumCheck.checked);            
});