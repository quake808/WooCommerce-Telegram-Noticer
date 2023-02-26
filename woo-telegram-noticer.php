<?php
/**
 * Plugin Name: WooCommerce Telegram Noticer
 * Plugin URI: https://github.com/quake808
 * Version: 2.0
 * Author: Viktor Shevchenko
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * WC requires at least: 3.0.0
 * WC tested up to: 5.2.2
 * Requires WooCommerce plugin to work
 */

defined( 'ABSPATH' ) or die( 'No direct access allowed.' );

// Connecting WooCommerce
add_action( 'plugins_loaded', 'noticer_woo_hooks_init' );
function noticer_woo_hooks_init() {
    if ( ! class_exists( 'WooCommerce' ) ) {

        return;
    }

    add_action( 'woocommerce_checkout_create_order', 'noticer_create_order', 10, 2 );

    add_action( 'woocommerce_order_status_changed', 'noticer_update_order_status', 10, 4 );
}

function noticer_settings_page() {
    add_submenu_page(
        'woocommerce',
        'Woo Telegram Noticer',
        'Woo Telegram Noticer',
        'manage_options',
        'noticer_settings',
        'noticer_settings_callback'
    );
}
add_action( 'admin_menu', 'noticer_settings_page' );

function noticer_settings_init() {
    add_settings_section(
        'noticer_settings',
        'WooCommerce Telegram Noticer',
        'noticer_settings_section_callback',
        'noticer_settings'
    );

    add_settings_field(
        'telegram_bot_token',
        'Telegram bot API token: ',
        'telegram_bot_token_callback',
        'noticer_settings',
        'noticer_settings'
    );

    add_settings_field(
        'telegram_chat_id',
        'Chat ID Telegram:',
        'telegram_chat_id_callback',
        'noticer_settings',
        'noticer_settings'
    );

    add_settings_field(
        'telegram_notifications_enabled',
        'Enable/Disable: ',
        'telegram_notifications_enabled_callback',
        'noticer_settings',
        'noticer_settings'
    );

    register_setting(
        'noticer_settings',
        'telegram_bot_token'
    );

    register_setting(
        'noticer_settings',
        'telegram_chat_id'
    );

    register_setting(
        'noticer_settings',
        'telegram_notifications_enabled',
        array(
            'type' => 'boolean',
            'sanitize_callback' => 'boolval'
        )
    );
}
add_action( 'admin_init', 'noticer_settings_init' );

function telegram_notifications_enabled_callback() {
    $value = get_option( 'telegram_notifications_enabled', false );
    ?>
    <label for="telegram_notifications_enabled">
        <input type="checkbox" id="telegram_notifications_enabled" name="telegram_notifications_enabled" <?php checked( $value ); ?>>
    </label>
    <?php
}

function noticer_settings_callback() {
    ?>
    <div class="wrap">
        <h1>Woo Telegram Noticer</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'noticer_settings' );
            do_settings_sections( 'noticer_settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function noticer_settings_section_callback() {
    echo '<p>Configurations for WooCommerce Telegram Noticer</p>';
}

function telegram_bot_token_callback() {
    $value = get_option( 'telegram_bot_token' );
    echo '<input type="text" name="telegram_bot_token" value="' . esc_attr( $value ) . '" />';
}

function telegram_chat_id_callback() {
    $value = get_option( 'telegram_chat_id' );
    echo '<input type="text" name="telegram_chat_id" value="' . esc_attr( $value ) . '" />';
}


function noticer_create_order( $order, $data ) {
    // Order created
}


function noticer_update_order_status( $order_id, $old_status, $new_status, $order ) {

        $order = wc_get_order( $order_id );
        // Order completed
        if ( $order->get_status() !== 'completed' ) {
            return;
        }
    
        $telegram_notifications_enabled = get_option( 'telegram_notifications_enabled', false );
        if ( ! $telegram_notifications_enabled ) {
            return;
        }
        
        // Receiving order details 
        $order_number = $order->get_order_number();
        $customer_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $customer_email = $order->get_billing_email();
        $order_total = $order->get_total();
    
        // Message for Telegram
        $message = "Completed order #{$order_number} from {$customer_name} ({$customer_email}). Order amount: {$order_total}.";
    
        // Sending message to Telegram
        $telegram_bot_token = get_option( 'telegram_bot_token' );
        $telegram_chat_id = get_option( 'telegram_chat_id' );
        $telegram_api_url = "https://api.telegram.org/bot{$telegram_bot_token}/sendMessage";
        $telegram_params = array(
            'chat_id' => $telegram_chat_id,
            'text' => $message,
        );
        wp_remote_post( $telegram_api_url, array( 'body' => $telegram_params ) );
}

// Activate plugin
function noticer_activate() {
    // After plugin is activated
}
register_activation_hook( __FILE__, 'noticer_activate' );

// Deactivate plugin
function noticer_deactivate() {
    // After plugin is deactivated 
}
register_deactivation_hook( __FILE__, 'noticer_deactivate' );

// Uninstall plugin
function noticer_uninstall() {
    // After plugin is uninstalled 
}
register_uninstall_hook( __FILE__, 'noticer_uninstall' );
