<?php
/*
Plugin Name: Change Admin Email
Description: Allows changing the site administrator's email address from the WordPress dashboard.
Version: 1.0
 * Author: しんや
 * Author URI: https://officeueda.com
*/

// Add menu item
function cae_add_admin_menu() {
    add_menu_page('Change Admin Email', 'Change Admin Email', 'manage_options', 'change-admin-email', 'cae_display_admin_page', 'dashicons-email-alt');
}
add_action('admin_menu', 'cae_add_admin_menu');

// Display admin page
function cae_display_admin_page() {
    $current_email = get_option('admin_email');
    ?>
    <div class="wrap">
        <h2>Change Admin Email</h2>
        <p>Current Email: <strong><?php echo esc_html($current_email); ?></strong></p>
        <form id="change-admin-email-form">
            <label for="new-email-address">New Email Address:</label>
            <input type="email" id="new-email-address" name="new-email-address" required>
            <input type="button" value="Change Email" onclick="showConfirmationPopup()">
        </form>
        <div id="confirmation-popup" style="display:none;">
            <p>Are you sure you want to change the admin email address to <strong id="new-email-confirm"></strong>?</p>
            <button onclick="changeEmail()">Confirm</button>
            <button onclick="hideConfirmationPopup()">Cancel</button>
        </div>
    </div>
    <script src="<?php echo plugins_url('/js/admin-email-script-final.js', __FILE__); ?>"></script>
    <?php
}

// Register settings
function cae_register_settings() {
    register_setting('general', 'admin_email', 'email');
}
add_action('admin_init', 'cae_register_settings');

// Enqueue script with nonce for AJAX
function cae_enqueue_scripts() {
    wp_enqueue_script('cae-script', plugins_url('/js/admin-email-script-final.js', __FILE__), array('jquery'), null, true);
    wp_localize_script('cae-script', 'cae_ajax_obj', array( 'ajax_url' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('cae_nonce')));
}
add_action('admin_enqueue_scripts', 'cae_enqueue_scripts');

// AJAX handler for changing email
function cae_change_email() {
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
add_action('wp_ajax_cae_change_email', 'cae_change_email');
