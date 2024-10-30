jQuery(document).ready(function($) {
    $('#phrasesTable').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "pageLength": 50,
        "order": [[ 0, "asc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disables sorting on the last column (Actions)
        ]
    });

    $('#keywordReasearchTable').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[ 1, "desc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disables sorting on the last column (Actions)
        ]
    });

    $('#keywordsTable').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[ 0, "asc" ]], 
        "columnDefs": [
            { "orderable": false, "targets": -1 } // Disables sorting on the last column (Actions)
        ]
    });  
    
    $('#competitorsTable').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[ 0, "asc" ]], 
    });     

    $('#rankingTable1').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[ 0, "asc" ]], 
    }); 
    
    $('#rankingTable2').DataTable({
        "paging": true, 
        "searching": true,
        "ordering": true,
        "info": true,
        "order": [[ 0, "asc" ]], 
    });     
});
