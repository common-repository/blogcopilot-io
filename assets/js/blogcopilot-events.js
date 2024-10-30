document.addEventListener('DOMContentLoaded', function() {
    var confirmationModal = document.getElementById('confirmationModal');
    var confirmDeleteBtn = document.getElementById('confirmDelete');

    confirmationModal.addEventListener('show.bs.modal', function(event) {
        var button = event.relatedTarget; // Button that triggered the modal
        var deleteUrl = button.getAttribute('data-delete-url'); // Extract info from data-* attributes
        var deleteNonce = button.getAttribute('data-nonce'); // Extract info from data-* attributes

        // If the confirm button inside the modal is clicked, redirect to the delete URL
        confirmDeleteBtn.onclick = function() {
            window.location.href = deleteUrl + "&_wpnonce=" + deleteNonce;
        };
    });
});
