<?php
/*
Plugin Name: WPVivid Backup Cleaner
Description: Deletes all files in the /wp-content/wpvividbackups directory.
Version: 1.0
Author: しんや
Author URI: https://officeueda.com

*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

function wpvividbackup_cleaner_delete_files() {
    $backup_dir = ABSPATH . 'wp-content/wpvividbackups/';
    if (is_dir($backup_dir)) {
        $files = glob($backup_dir . '*'); // get all file names
        foreach($files as $file){
            if(is_file($file)) {
                unlink($file); // delete file
            }
        }
        echo 'All files in /wp-content/wpvividbackups have been deleted.';
    } else {
        echo 'Directory does not exist.';
    }
}

function wpvividbackup_cleaner_admin_menu() {
    add_management_page(
        'WPVivid Backup Cleaner',
        'WPVivid Backup Cleaner',
        'manage_options',
        'wpvividbackup-cleaner',
        'wpvividbackup_cleaner_page'
    );
}

function wpvividbackup_cleaner_page() {
    if (isset($_POST['wpvividbackup_cleaner_delete'])) {
        wpvividbackup_cleaner_delete_files();
    }
    ?>
    <div class="wrap">
        <h1>WPVivid Backup Cleaner</h1>
        <form method="post">
            <input type="hidden" name="wpvividbackup_cleaner_delete" value="1">
            <p>
                <input type="submit" value="Delete All Backup Files" class="button button-primary">
            </p>
        </form>
    </div>
    <?php
}

add_action('admin_menu', 'wpvividbackup_cleaner_admin_menu');
?>
