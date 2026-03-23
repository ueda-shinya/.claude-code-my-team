
document.getElementById('change-admin-email-form').addEventListener('submit', function(e) {
    e.preventDefault();
    var confirmationPopup = document.getElementById('confirmation-popup');
    confirmationPopup.style.display = 'block';

    document.getElementById('confirm-change').addEventListener('click', function() {
        var newEmail = document.getElementById('new-email-address').value;
        // AJAX call to update email address
        var data = {
            'action': 'cae_change_email',
            'new_email': newEmail
        };

        jQuery.post(ajaxurl, data, function(response) {
            alert('Email address updated successfully.');
            window.location.reload();
        });

        confirmationPopup.style.display = 'none';
    });

    document.getElementById('cancel-change').addEventListener('click', function() {
        confirmationPopup.style.display = 'none';
    });
});
