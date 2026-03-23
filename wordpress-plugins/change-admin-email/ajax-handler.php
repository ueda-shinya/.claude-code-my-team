add_action('wp_ajax_cae_change_email', 'cae_change_email_callback');

function cae_change_email_callback() {
    $new_email = $_POST['new_email'];
    if (is_email($new_email) && current_user_can('manage_options')) {
        update_option('admin_email', $new_email);
        echo 'Success';
    } else {
        echo 'Error';
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
