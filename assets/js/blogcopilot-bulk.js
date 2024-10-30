document.getElementById('blogcopilot-mass-creation-form').addEventListener('paste', (e) => {
    // Prevent the default paste action
    e.preventDefault();

    // Get the text from the clipboard
    const text = (e.clipboardData || window.clipboardData).getData('text');

    // Split the text into rows
    const rows = text.split('\n');

    // Find the currently focused title input
    const activeInput = document.activeElement;
    const titleInputs = Array.from(document.querySelectorAll('[name="titles[]"]'));
    const startIndex = titleInputs.indexOf(activeInput);

    // Check if the active element is indeed one of the title inputs
    if (startIndex !== -1) {
        rows.forEach((row, index) => {
            const titleIndex = startIndex + index;
            if (titleInputs[titleIndex]) {
                const title = row.split('\t')[0]; // Assuming the first column is the title
                titleInputs[titleIndex].value = title;

        // Add 'is-focused' class to the parent div of the input field
        const inputGroupDiv = titleInputs[titleIndex].closest('.input-group');
        if (inputGroupDiv) {
            inputGroupDiv.classList.add('is-focused');
        }
            }
        });
    }
});

document.getElementById('add-more-rows').addEventListener('click', function() {
    var container = document.getElementById('rows-container');
    var currentRows = container.querySelectorAll('.row').length;
    var newRowHtml = ''; // Initialize HTML for new rows

    for (let i = currentRows; i < currentRows + 10; i++) {
        newRowHtml += '<div class="row my-2">';
        newRowHtml += '<div class="col-md-8"><input type="text" class="form-control" name="titles[]"></div>';
        newRowHtml += '<div class="col-md-4"><select name="categories[]" class="form-control">';
        bulkData.categories.forEach(function(category) {
            newRowHtml += '<option value="' + category.term_id + '">' + category.name + '</option>';
        });           
        newRowHtml += '</select></div>';
        newRowHtml += '</div>'; // Add your row HTML here
    }

    container.insertAdjacentHTML('beforeend', newRowHtml);
});

document.addEventListener('DOMContentLoaded', function () {
    var articleLengthSlider = document.getElementById('articleLengthSlider');
    var articleLengthValue = document.getElementById('articleLengthValue');

    articleLengthSlider.addEventListener('input', function () {
        articleLengthValue.textContent = this.value + ' words';
    });
});
