<?php
/*
Plugin Name: Contact Form Scroll Enhancer
Description: Enhances Contact Form 7 with custom scroll and button text functionality.
Version: 1.0
Author: しんや
Author URI: https://officeueda.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

function cfse_enqueue_script() {
    wp_register_script(
        'scroll-enhancer',
        plugins_url( 'js/scroll-enhancer.js', __FILE__ ),
        array('jquery'),
        '1.0',
        true
    );

    wp_localize_script( 'scroll-enhancer', 'cfseSettings', array(
        'buttonText' => get_option( 'cfse_button_text', '戻る' ),
        'scrollTarget' => get_option( 'cfse_scroll_target', 'contact-form' )
    ) );

    wp_enqueue_script( 'scroll-enhancer' );
}
add_action( 'wp_enqueue_scripts', 'cfse_enqueue_script' );

function cfse_add_admin_menu() {
    add_options_page(
        'Contact Form Scroll Enhancer Settings',
        'Contact Form Scroll Enhancer',
        'manage_options',
        'contact-form-scroll-enhancer',
        'cfse_options_page'
    );
}
add_action( 'admin_menu', 'cfse_add_admin_menu' );

function cfse_settings_init() {
    register_setting( 'cfse_options_group', 'cfse_button_text' );
    register_setting( 'cfse_options_group', 'cfse_scroll_target' );

    add_settings_section(
        'cfse_settings_section',
        'Contact Form Scroll Enhancer Settings',
        null,
        'contact-form-scroll-enhancer'
    );

    add_settings_field(
        'cfse_button_text',
        'Button Text',
        'cfse_button_text_render',
        'contact-form-scroll-enhancer',
        'cfse_settings_section'
    );

    add_settings_field(
        'cfse_scroll_target',
        'Scroll Target ID',
        'cfse_scroll_target_render',
        'contact-form-scroll-enhancer',
        'cfse_settings_section'
    );
}
add_action( 'admin_init', 'cfse_settings_init' );

function cfse_button_text_render() {
    $button_text = get_option( 'cfse_button_text', '戻る' );
    echo "<input type='text' name='cfse_button_text' value='" . esc_attr( $button_text ) . "'>";
}

function cfse_scroll_target_render() {
    $scroll_target = get_option( 'cfse_scroll_target', 'contact-form' );
    echo "<input type='text' name='cfse_scroll_target' value='" . esc_attr( $scroll_target ) . "'>";
}

function cfse_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Contact Form Scroll Enhancer Settings</h2>
        <?php
        settings_fields( 'cfse_options_group' );
        do_settings_sections( 'contact-form-scroll-enhancer' );
        submit_button();
        ?>
    </form>
    <?php
}
?>
