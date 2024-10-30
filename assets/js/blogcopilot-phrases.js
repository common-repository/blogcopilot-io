jQuery(document).ready(function($) {
    // Function to fetch keywords from the API
    function fetchKeywords(phraseId, phraseName, phraseCategory, nonce) {
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'blogcopilot_io_get_proposed_keywords', // Custom AJAX action
                keywords: phraseName, // Pass the phrase name as the keywords
                phraseId: phraseId,
                phraseCategory: phraseCategory,
                nonce: nonce,                  
            },
            beforeSend: function() {
                // Optional: Add a loading indicator here
                $('#keywordsList').html('<p>Loading keywords...</p>');
                $('#keywordsList').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
            },
            success: function(response) {
                if (response.success) {
                    if (Array.isArray(response.data)) {
                        let keywords = response.data; 
    
                        // Shuffle the keywords array randomly
                        for (let i = keywords.length - 1; i > 0; i--) {
                            const j = Math.floor(Math.random() * (i + 1));
                            [keywords[i], keywords[j]] = [keywords[j], keywords[i]];
                        }
        
                        // Limit the number of keywords to 10
                        keywords = keywords.slice(0, 10);
        
                        $('#modalPhrase').text(phraseName);
                        const keywordsList = $('#keywordsList');
                        keywordsList.empty();
                
                        // Create a table to display keywords
                        const table = $('<table>').addClass('table table-striped'); // Add Bootstrap classes for styling
                        const thead = $('<thead>').append('<tr><th></th><th>Article title</th><th>Competition</th><th>Search Volume</th></tr>');
                        const tbody = $('<tbody>');
                
                        keywords.forEach(keywordData => {
                            const row = $('<tr>');
                            row.append(`<td><input class="form-check-input" type="checkbox" value="${keywordData.Keyword}" id="keyword_${keywordData.Keyword.replace(/\s+/g, '_')}"></td>`);
                            row.append(`<td><label class="form-check-label" for="keyword_${keywordData.Keyword.replace(/\s+/g, '_')}">${keywordData.Keyword}</label></td>`);
                            row.append(`<td>${keywordData.Competition}</td>`);
                            row.append(`<td>${keywordData.SearchVolume}</td>`);
                            tbody.append(row);
                        });
                
                        table.append(thead).append(tbody);
                        keywordsList.append(table);
                    } else {
                        $('#keywordsList').html(`<p class="text-danger">${response.data}</p>`);
                    }                    
                } else {
                    // Handle errors (display an error message, etc.)
                    $('#keywordsList').html('<p>Error fetching keywords.</p>');
                }
            },
            error: function() {
                // Handle AJAX errors
                $('#keywordsList').html('<p>An error occurred while fetching keywords.</p>');
            },
            complete: function() {
                // Remove the loading indicator (optional, if you want it to disappear even on error)
                $('#keywordsList .spinner-border').remove();
            }            
        });    
    }

    function fetchPhrases(nonce) {
        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            data: {
                action: 'blogcopilot_io_get_proposed_phrases', // New AJAX action for phrase suggestions
                nonce: nonce,                  
            },
            beforeSend: function() {
                // Optional: Add a loading indicator here
                $('#phrasesList').html('<p>Loading phrases...</p>');
                $('#phrasesList').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
            },
            success: function(response) {
                const phrases = response.data; 

                const keywordsList = $('#phrasesList');
                keywordsList.empty();
        
                // Create a table to display phrases
                const table = $('<table>').addClass('table table-striped'); 
                const thead = $('<thead>').append('<tr><th></th><th>Phrase</th><th>Title Suggestions</th></tr>'); // Updated header
                const tbody = $('<tbody>');
        
                phrases.forEach(phraseData => {
                    const row = $('<tr>');
                    row.append(`<td><input class="form-check-input" type="checkbox" value="${phraseData.phrase}" id="phrase_${phraseData.phrase.replace(/\s+/g, '_')}"></td>`);
                    row.append(`<td><label class="form-check-label" for="phrase_${phraseData.phrase.replace(/\s+/g, '_')}">${phraseData.phrase}</label></td>`); // Display phrase
                    row.append(`<td>${phraseData.title}</td>`); // Display title in a separate column
                    tbody.append(row);
                });
        
                table.append(thead).append(tbody);
                keywordsList.append(table);
            },
            error: function() {
                $('#phrasesList').html('<p>An error occurred while fetching phrase suggestions.</p>');
            },
            complete: function() {
                $('#phrasesList .spinner-border').remove();
            }            
        });    
    }

    // Event handler for "Refresh Phrases" button click
    $('#refreshPhrasesButton').on('click', function() { 
        const nonce = $('#generatePhrasesModal').data('nonce'); 
        fetchPhrases(nonce);
    });

    // Store phraseId, Category in the modal when it's shown
    $('#generatePhrasesModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget); 
        const nonce = button.data('nonce');
        $(this).data('nonce', nonce);

        fetchPhrases(nonce);
    });

    $('#generatePhrasesModal').on('hidden.bs.modal', function () {
        // Clear the keywords list (table or error message)
        $('#keywordsList').empty();
    });    

    // Event handler for "Save Phrases and Generate Articles" button click
    $('#generatePhrasesArticlesButton').on('click', function() {
        const selectedPhrases = [];
        $('#phrasesList input[type="checkbox"]:checked').each(function() {
            const phrase = $(this).val();
            const title = $(this).closest('tr').find('td:nth-child(3)').text(); // Get the text from the third column directly
            
            selectedPhrases.push({ phrase: phrase, title: title });
        });

        if (selectedPhrases.length === 0) {
            alert('Please select at least one phrase suggestion.');
            return;
        }

        const nonce = $('#generatePhrasesModal').data('nonce');

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: {
                action: 'blogcopilot_io_save_phrases_and_generate_articles',
                selectedPhrases: selectedPhrases,
                nonce: nonce
            },
            beforeSend: function() {
                $('#generatePhrasesArticlesButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');
            },
            success: function(response) {
                $('#generatePhrasesArticlesButton').prop('disabled', false).text('Save Phrases and Generate Articles');

                if (response.success) {
                    alert('Articles are being generated for the selected phrases. Please check back later for results.');
                } else {
                    alert('An error occurred while generating articles. ' + response.data.message); 
                }
            },
            error: function() {
                $('#generatePhrasesArticlesButton').prop('disabled', false).text('Save Phrases and Generate Articles');
                alert('An error occurred while communicating with the server.');
            },
            complete: function() {
                $('#generatePhrasesModal').modal('hide');
            }
        });
    });

    // Event handler for "Generate Links" button click
    $('#phrasesTable').on('click', 'button[data-bs-target="#generateLinksModal"]', function() {
        const phraseId = $(this).data('phrase-id');
        const phraseName = $(this).data('phrase-name');
        const phraseCategory = $(this).data('phrase-category');    
        const wordpressId = $(this).data('data-wordpress-id');            
        const nonce = $(this).data('nonce'); // Get the nonce from the button
        fetchKeywords(phraseId, phraseName, phraseCategory, nonce); // Pass the nonce to fetchKeywords
    });

    // Event handler for "Refresh Keywords" button click
    $('#refreshKeywordsButton').on('click', function() {
        const phraseId = $('#generateLinksModal').data('phrase-id'); // Get phraseId stored in the modal
        const phraseName = $('#modalPhrase').text();
        const phraseCategory = $('#generateLinksModal').data('phrase-category'); // Get phraseId stored in the modal        
        const nonce = $('#generateLinksModal').data('nonce'); // Get the nonce from the modal
        fetchKeywords(phraseId, phraseName, phraseCategory, nonce); // Pass the nonce to fetchKeywords
    });

    // Event handler for "Generate Articles" button click
    $('#generateArticlesButton').on('click', function() {
        let selectedKeywords = [];

        // Get keywords from checkboxes
        $('#keywordsList input[type="checkbox"]:checked').each(function() {
            selectedKeywords.push($(this).val());
        });

        // Get keywords from manual input fields
        $('#manualKeywords input[type="text"]').each(function() {
            const keyword = $(this).val().trim(); // Trim whitespace
            if (keyword !== '') {
                selectedKeywords.push(keyword);
            }
        });

        if (selectedKeywords.length === 0) {
            alert('Please select at least one keyword or enter manual suggestions.');
            return;
        }

        const nonce = $('#generateLinksModal').data('nonce'); // Get from the modal
        const phraseId = $('#generateLinksModal').data('phrase-id');
        const phraseCategory = $('#generateLinksModal').data('phrase-category'); 
        const wordpressId = $('#generateLinksModal').data('wordpress-id');

        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: {
                action: 'blogcopilot_io_add_linking_subphrases', 
                selectedKeywords: selectedKeywords,
                nonce: nonce,
                phraseId: phraseId,
                wordpressId: wordpressId,
                phraseCategory: phraseCategory,
            },
            beforeSend: function() {
                $('#generateArticlesButton').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Generating...');
            },
            success: function(response) {
                if (response.data.status === 'Success') {
                    // Handle success
                    alert('Articles are being generated for the selected keywords. Please check back later for results.');
                } else {
                    // Handle specific error messages from the API
                    let errorMessage = 'An error occurred while generating articles.';
                    if (response.data.message) {
                        errorMessage = response.data.message;
            
                        // Check for quota exceeded error and display additional info
                        if (response.data.error === 'Monthly article generation quota exceeded.') {
                            errorMessage += '\n\nMonthly Generated Articles: ' + response.data.monthlyGeneratedArticles +
                                            '\nMonthly Article Quota: ' + response.data.monthlyArticleQuota +
                                            '\nRemaining Article Quota: ' + response.data.remainingArticleQuota + 
                                            '\n\nUpgrade to a higher plan to increase your limits!';
                        }
                    }
                    alert(errorMessage);
                }
            },
            error: function() {
                // Handle AJAX errors
                // Re-enable the button and restore the original text
                alert('An error occurred while communicating with the server.');
            },
            complete: function() {
                $('#generateArticlesButton').prop('disabled', false).text('Generate Articles');
                $('#generateLinksModal').modal('hide');
            }
        });        
    });

    // Store phraseId, Category in the modal when it's shown
    $('#generateLinksModal').on('show.bs.modal', function(event) {
        const button = $(event.relatedTarget); 
        const phraseId = button.data('phrase-id');
        const phraseCategory = button.data('phrase-category');        
        const wordpressId = button.data('wordpress-id');
        const nonce = button.data('nonce');
        $(this).data('phrase-category', phraseCategory);        
        $(this).data('phrase-id', phraseId);
        $(this).data('wordpress-id', wordpressId);        
        $(this).data('nonce', nonce);
    });

    $('#generateLinksModal').on('hidden.bs.modal', function () {
        // Clear the keywords list (table or error message)
        $('#keywordsList').empty();

        // Clear the manual keyword input fields (if you have them)
        $('#manualKeywords input[type="text"]').val(''); 
        $('#manualKeywords .form-control').remove(); 

        // Reset the button state
        $('#generateArticlesButton')
            .prop('disabled', false)
            .html('Generate Articles');
    });

    // Add event handler for "Add Keyword" button (you'll need to add this button in your HTML)
    $('#addKeywordButton').on('click', function() {
        const manualKeywordsContainer = $('#manualKeywords');
        const newInput = $('<input type="text" class="form-control mb-2">');
        manualKeywordsContainer.append(newInput);
    });

    function fetchSubphrases(phraseId, phraseName, nonce) {
        $.ajax({
            url: ajaxurl, 
            type: 'POST',
            data: {
                action: 'blogcopilot_io_get_subphrases',
                phraseId: phraseId,
                nonce: nonce
            },
            beforeSend: function() {
                $('#subphrasesList').html('<p>Loading subphrases...</p>');
            },
            success: function(response) {
                if (response.success) {
                    const subphrases = response.data; 

                    $('#detailsModalPhrase').text(phraseName);
                    const subphrasesList = $('#subphrasesList');
                    subphrasesList.empty();
    
                    if (subphrases.length > 0) {
                        // Create a table to display subphrases and their statuses
                        const table = $('<table>').addClass('table table-striped'); 
                        const thead = $('<thead>').append('<tr><th>Subphrase</th><th>Status</th></tr>');
                        const tbody = $('<tbody>');
            
                        subphrases.forEach(subphrase => {
                            const row = $('<tr>');
                            if (subphrase.editLink && subphrase.Status !== 'AI Pending') {
                                row.append(`<td>${subphrase.Phrase} <a href="${subphrase.editLink}" target="_blank">(Edit)</a> <a href="${subphrase.viewLink}" target="_blank">(View)</a></td>`);
                            } else {
                                row.append(`<td>${subphrase.Phrase}</td>`);
                            }
                            row.append(`<td>${subphrase.Status}</td>`);
                            tbody.append(row);
                        });
            
                        table.append(thead).append(tbody);
                        subphrasesList.append(table);
                    } else {
                        subphrasesList.html('<p>No subphrases or linking articles available.</p>');
                    }
                } else {
                    $('#subphrasesList').html('<p>Error fetching subphrases.</p>');
                }
            },
            error: function() {
                $('#subphrasesList').html('<p>An error occurred while fetching subphrases.</p>');
            }
        });
    }
    
    // Event handler for "View Details" button click
    $('#phrasesTable').on('click', 'button[data-bs-target="#phraseDetailsModal"]', function() {
        const phraseId = $(this).data('phrase-id');
        const phraseName = $(this).data('phrase-name');
        const nonce = $(this).data('nonce');    
        fetchSubphrases(phraseId, phraseName, nonce);
    });    
});