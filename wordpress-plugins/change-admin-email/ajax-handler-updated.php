add_action('wp_ajax_cae_change_email', 'cae_change_email_callback');

function cae_change_email_callback() {
    check_ajax_referer('cae_nonce', 'security');
    if (current_user_can('manage_options')) {
        $new_email = sanitize_email($_POST['new_email']);
        if (is_email($new_email)) {
            update_option('admin_email', $new_email);
            // Check if 'new_admin_email' exists and update it too
            if (get_option('new_admin_email') !== false) {
                update_option('new_admin_email', $new_email);
            }
            wp_send_json_success('Email updated successfully');
        } else {
            wp_send_json_error('Invalid email address');
        }
    } else {
        wp_send_json_error('Unauthorized');
    }
    wp_die(); // this is required to terminate immediately and return a proper response
}
