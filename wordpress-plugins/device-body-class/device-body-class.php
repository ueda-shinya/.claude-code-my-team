<?php
/*
Plugin Name: Device Body Class
Plugin URI: https://officeueda.com
Description: Adds a class to the body tag for specific devices to apply specific CSS fixes.
Version: 1.1
Author: しんや
Author URI: https://officeueda.com
License: GPL2
*/

// Add device-specific class to body tag
function add_device_body_class() {
    echo "
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var userAgent = navigator.userAgent;
        var body = document.body;

        if (userAgent.indexOf('Android') > 0) {
            body.classList.add('Android');
        } else if (userAgent.indexOf('iPhone') > 0 || userAgent.indexOf('iPad') > 0) {
            body.classList.add('iOS');
        } else if (userAgent.indexOf('Windows Phone') > 0) {
            body.classList.add('WindowsPhone');
        } else if (userAgent.indexOf('Macintosh') > 0) {
            body.classList.add('Mac');
        } else if (userAgent.indexOf('Windows NT') > 0) {
            body.classList.add('Windows');
        } else if (userAgent.indexOf('Linux') > 0) {
            body.classList.add('Linux');
        }
    });
    </script>
    ";
}
add_action('wp_footer', 'add_device_body_class');

// Add an admin menu for plugin instructions
function device_body_class_menu() {
    add_menu_page(
        'Device Body Class Instructions',
        'Device Body Class',
        'manage_options',
        'device-body-class',
        'device_body_class_instructions_page'
    );
}
add_action('admin_menu', 'device_body_class_menu');

function device_body_class_instructions_page() {
    ?>
    <div class="wrap">
        <h1>Device Body Class Instructions</h1>
        <p>This plugin adds specific classes to the <code>&lt;body&gt;</code> tag based on the device being used. Here are the classes that are added for each device:</p>
        <ul>
            <li><strong>Android:</strong> <code>body.Android</code></li>
            <li><strong>iPhone/iPad:</strong> <code>body.iOS</code></li>
            <li><strong>Windows Phone:</strong> <code>body.WindowsPhone</code></li>
            <li><strong>Mac:</strong> <code>body.Mac</code></li>
            <li><strong>Windows:</strong> <code>body.Windows</code></li>
            <li><strong>Linux:</strong> <code>body.Linux</code></li>
        </ul>
        <h2>Usage</h2>
        <p>To apply specific CSS for a device, add the corresponding class to your CSS selectors. For example:</p>
        <pre><code>body.Android .your-class { 
    font-size: 16px; 
    /* Other styles for Android devices */
}

body.iOS .your-class { 
    font-size: 14px; 
    /* Other styles for iOS devices */
}
</code></pre>
    </div>
    <?php
}
?>
