// const phraseInput = document.getElementById('blogcopilot_phrase_name_display');
// const phraseIdInput = document.getElementById('blogcopilot_phrase_id');
// const phraseOptions = document.getElementById('phrase_options');

// phraseInput.addEventListener('input', function() {
//     const selectedOption = Array.from(phraseOptions.options).find(option => option.value === phraseInput.value);
//     if (selectedOption) {
//         phraseIdInput.value = selectedOption.dataset.phraseId;
//     } else {
//         phraseIdInput.value = ''; // Clear if no match or "No Phrase Selected" is chosen
//     }
// });


var input = document.querySelector('input[name=tags-outside]')

jQuery.ajax({
    url: ajaxurl, // WordPress AJAX URL
    type: 'POST',
    data: {
        action: 'blogcopilot_get_phrases' // Name of the AJAX action
    },
    success: function(response) {
        if (response.success) {
            const phrases = response.data;

            // Prepare the whitelist for Tagify
            const whitelist = phrases.map(phrase => ({
                value: phrase.Phrase,
                id: phrase.PhraseID
            }));

            var tagify = new Tagify(input, {
                whitelist: whitelist,
                focusable: false,
                dropdown: {
                    position: 'input',
                    enabled: 0 
                }
            });
        } else {
            console.error('Error fetching phrases:', response.data.message);
            // Handle the error appropriately
        }
    },
    error: function(error) {
        console.error('AJAX error:', error);
        // Handle the AJAX error appropriately
    }
});