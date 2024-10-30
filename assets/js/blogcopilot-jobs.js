document.addEventListener('DOMContentLoaded', function() {
    const searchButton = document.getElementById('searchButton');

    searchButton.addEventListener('click', function() {
        const dateFrom = document.getElementById('dateFrom').value;
        const dateTo = document.getElementById('dateTo').value;
        const title = document.getElementById('title').value.toLowerCase();
        const status = document.getElementById('status').value;
        const nonce = document.getElementById('blogcopilot_search_nonce').value;

        const data = {
            action: 'blogcopilot_io_search_jobs',
            dateFrom: dateFrom,
            dateTo: dateTo,
            title: title,
            status: status,
            blogcopilot_search_nonce: nonce
        };

        jQuery.post(ajaxurl, data, function(response) {
            if (response.success) {
                const rows = document.querySelectorAll('#jobTable tbody tr');
                rows.forEach(row => row.style.display = 'none');

                if (Array.isArray(response.data) && response.data.length > 0) {
                    response.data.forEach(job => {
                        const row = document.querySelector(`#jobTable tbody tr[data-job-id="${job.JobGroupID}"]`);
                        if (row) row.style.display = '';
                    });
                }
            } else {
                alert(response.data || 'An error occurred.');
            }
        }).fail(function() {
            alert('An error occurred while processing the request.');
        });
    });


    // Allow clearing date fields
    const dateFields = document.querySelectorAll('input[type="date"]');
    dateFields.forEach(field => {
        field.addEventListener('focus', function() {
            field.type = 'date';
        });

        field.addEventListener('blur', function() {
            if (!field.value) {
                field.type = 'date';
            }
        });
    });    
});
