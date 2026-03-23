
function showConfirmationPopup() {
    var newEmail = document.getElementById('new-email-address').value;
    document.getElementById('new-email-confirm').textContent = newEmail;
    document.getElementById('confirmation-popup').style.display = 'block';
}

function hideConfirmationPopup() {
    document.getElementById('confirmation-popup').style.display = 'none';
}

function changeEmail() {
    var newEmail = document.getElementById('new-email-address').value;
    jQuery.post(cae_ajax_obj.ajax_url, {
        'action': 'cae_change_email',
        'new_email': newEmail,
        'security': cae_ajax_obj.nonce
    }, function(response) {
        if (response.success) {
            alert('Email address updated successfully.');
        } else {
            alert('Error: ' + response.data);
        }
        window.location.reload();
    });
}
