jQuery(document).ready(function($) {
    $('.generate-article').click(function(e) {
        e.preventDefault();
        var $btn = $(this);
        var data = {
            'action': 'blogcopilot_generate_article',
            'keyword': $btn.data('keyword'),
            'monthly_search': $btn.data('monthly-search'),
            'difficulty': $btn.data('difficulty'),
            'nonce': $btn.data('nonce')
        };

        $.post(ajaxurl, data, function(response) {
            alert('Article Generation: ' + response.data.message);
        });
    });
});
