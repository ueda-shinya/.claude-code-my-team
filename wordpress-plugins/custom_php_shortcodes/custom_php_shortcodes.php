<?php
/*
Plugin Name: Custom PHP Shortcodes
Description: Manage and execute custom PHP shortcodes through the WordPress admin panel, with uninstall options. This version includes a character limit validation for shortcode names.
Version: 1.0.2
Author: しんや
Author URI: https://officeueda.com
*/

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Add admin menu
function cps_final_v7_add_admin_menu()
{
    add_menu_page(
        'Custom PHP Shortcodes',
        'PHP Shortcodes',
        'manage_options',
        'custom_php_shortcodes_final_v7',
        'cps_final_v7_admin_page',
        'dashicons-editor-code',
        80
    );
}
add_action('admin_menu', 'cps_final_v7_add_admin_menu');

// Admin page content
function cps_final_v7_admin_page()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_php_shortcodes_final_v7";

    // Initialize error message
    $error_message = '';

    // Handle form submission
    if (isset($_POST['cps_final_v7_action']) && check_admin_referer('cps_final_v7_nonce_action', 'cps_final_v7_nonce')) {
        if ($_POST['cps_final_v7_action'] === 'add') {
            $shortcode_name = sanitize_text_field($_POST['shortcode_name']);
            $php_code = wp_unslash($_POST['php_code']);

            // Validate shortcode name length
            if (strlen($shortcode_name) > 50) {
                $error_message = 'Error: Shortcode name cannot exceed 50 characters.';
            } else {
                $wpdb->insert($table_name, ['shortcode_name' => $shortcode_name, 'php_code' => $php_code]);
                echo '<div class="updated"><p>Shortcode added.</p></div>';
            }
        } elseif ($_POST['cps_final_v7_action'] === 'delete' && isset($_POST['id'])) {
            $wpdb->delete($table_name, ['id' => intval($_POST['id'])]);
            echo '<div class="updated"><p>Shortcode deleted.</p></div>';
        } elseif ($_POST['cps_final_v7_action'] === 'edit' && isset($_POST['id'])) {
            $id = intval($_POST['id']);
            $shortcode_name = sanitize_text_field($_POST['shortcode_name']);
            $php_code = wp_unslash($_POST['php_code']);

            // Validate shortcode name length
            if (strlen($shortcode_name) > 50) {
                $error_message = 'Error: Shortcode name cannot exceed 50 characters.';
            } else {
                $wpdb->update($table_name, ['shortcode_name' => $shortcode_name, 'php_code' => $php_code], ['id' => $id]);
                echo '<div class="updated"><p>Shortcode updated.</p></div>';
            }
        }
    }

    // Handle uninstall option
    if (isset($_POST['cps_final_v7_uninstall_action']) && check_admin_referer('cps_final_v7_uninstall_nonce_action', 'cps_final_v7_uninstall_nonce')) {
        $uninstall_option = sanitize_text_field($_POST['uninstall_option']);
        update_option('cps_final_v7_uninstall_option', $uninstall_option);
        echo '<div class="updated"><p>Uninstall option updated.</p></div>';
    }

    // Get current uninstall option
    $uninstall_option = get_option('cps_final_v7_uninstall_option', 'delete');

    // Display existing shortcodes and uninstall option
    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
?>
    <div class="wrap">
        <h1>Custom PHP Shortcodes</h1>

        <?php if (!empty($error_message)): ?>
            <div class="error">
                <p><?php echo esc_html($error_message); ?></p>
            </div>
        <?php endif; ?>

        <form method="POST">
            <?php wp_nonce_field('cps_final_v7_nonce_action', 'cps_final_v7_nonce'); ?>
            <input type="hidden" name="cps_final_v7_action" value="add">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Shortcode Name</th>
                    <td><input type="text" name="shortcode_name" required /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">PHP Code</th>
                    <td><textarea name="php_code" rows="10" cols="50" required></textarea></td>
                </tr>
            </table>
            <?php submit_button('Add Shortcode'); ?>
        </form>

        <h2>Existing Shortcodes</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Shortcode Name</th>
                    <th>PHP Code</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo esc_html($row['id']); ?></td>
                        <td><?php echo esc_html($row['shortcode_name']); ?></td>
                        <td>
                            <pre><?php echo esc_html($row['php_code']); ?></pre>
                        </td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <?php wp_nonce_field('cps_final_v7_nonce_action', 'cps_final_v7_nonce'); ?>
                                <input type="hidden" name="cps_final_v7_action" value="edit">
                                <input type="hidden" name="id" value="<?php echo esc_html($row['id']); ?>">
                                <input type="text" name="shortcode_name" value="<?php echo esc_html($row['shortcode_name']); ?>" required>
                                <textarea name="php_code" rows="5" cols="50" required><?php echo esc_textarea($row['php_code']); ?></textarea>
                                <input type="submit" value="Update" class="button button-primary">
                            </form>
                            <form method="POST" style="display:inline;">
                                <?php wp_nonce_field('cps_final_v7_nonce_action', 'cps_final_v7_nonce'); ?>
                                <input type="hidden" name="cps_final_v7_action" value="delete">
                                <input type="hidden" name="id" value="<?php echo esc_html($row['id']); ?>">
                                <input type="submit" value="Delete" class="button button-secondary" onclick="return confirm('Are you sure?');">
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Uninstall Options</h2>
        <form method="POST">
            <?php wp_nonce_field('cps_final_v7_uninstall_nonce_action', 'cps_final_v7_uninstall_nonce'); ?>
            <input type="hidden" name="cps_final_v7_uninstall_action" value="update_uninstall_option">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Uninstall Behavior</th>
                    <td>
                        <select name="uninstall_option">
                            <option value="delete" <?php selected($uninstall_option, 'delete'); ?>>Delete all data</option>
                            <option value="keep" <?php selected($uninstall_option, 'keep'); ?>>Keep all data</option>
                        </select>
                    </td>
                </tr>
            </table>
            <?php submit_button('Save Uninstall Option'); ?>
        </form>
    </div>
<?php
}

// Create database table on plugin activation
function cps_final_v7_create_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_php_shortcodes_final_v7";
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        shortcode_name varchar(50) NOT NULL,
        php_code MEDIUMTEXT NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'cps_final_v7_create_table');

// Update table structure to modify `php_code` field to MEDIUMTEXT
function cps_final_v7_update_table_structure()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_php_shortcodes_final_v7";

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name) {
        $wpdb->query("ALTER TABLE $table_name MODIFY php_code MEDIUMTEXT");
    }
}
add_action('plugins_loaded', 'cps_final_v7_update_table_structure');

// Handle plugin uninstall
function cps_final_v7_uninstall_plugin()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_php_shortcodes_final_v7";

    $uninstall_option = get_option('cps_final_v7_uninstall_option', 'delete');
    if ($uninstall_option === 'delete') {
        $wpdb->query("DROP TABLE IF EXISTS $table_name");
    }
    delete_option('cps_final_v7_uninstall_option');
}
register_uninstall_hook(__FILE__, 'cps_final_v7_uninstall_plugin');

// Register each shortcode
function cps_final_v7_register_shortcodes()
{
    global $wpdb;
    $table_name = $wpdb->prefix . "custom_php_shortcodes_final_v7";
    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
    foreach ($results as $row) {
        add_shortcode($row['shortcode_name'], function () use ($row) {
            ob_start();
            eval($row['php_code']);
            return ob_get_clean();
        });
    }
}
add_action('init', 'cps_final_v7_register_shortcodes');

// Add custom CSS to widen the textarea
function cps_final_v7_admin_custom_css()
{
    echo '<style>
        .widefat textarea {
            width: 100%;
            min-width: 600px;
        }
        .widefat input[type="text"] {
            width: 100%;
        }
    </style>';
}
add_action('admin_head', 'cps_final_v7_admin_custom_css');
