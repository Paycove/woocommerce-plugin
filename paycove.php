<?php
/**
 * Plugin Name: Paycove
 * Plugin URI: https://paycove.io
 * Description: Integrate your store with Paycove.
 * Author: Paycove
 * Version: 0.1.12
 * License: MIT
 * Text Domain: paycove
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define PATH and URL constants.
define('PAYCOVE_GATEWAY_PATH', plugin_dir_path(__FILE__));
define('PAYCOVE_GATEWAY_URL', untrailingslashit(plugin_dir_url(__FILE__)));

// Check if WooCommerce is active, if not, deactivate the plugin.
add_action('admin_init', 'paycove_check_plugin_dependency');
function paycove_check_plugin_dependency()
{
    if (!class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', 'paycove_admin_notice_missing_main_plugin');
        deactivate_plugins(plugin_basename(__FILE__));
    }
}

/**
 * Display an admin notice if WooCommerce is not active.
 */
function paycove_admin_notice_missing_main_plugin()
{
    // Make sure that the "Plugin activated" notice is not displayed.
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }

    $message = sprintf(
        /* translators: 1: Plugin name 2: Elementor */
        esc_html__('"%1$s" requires "%2$s" to be installed and activated.', 'paycove'),
        '<strong>Paycove</strong>',
        '<strong>' . esc_html__('WooCommerce', 'paycove') . '</strong>'
    );

    printf('<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message);
}

/**
 * Registers our PHP class as a WooCommerce payment gateway.
 */
add_filter('woocommerce_payment_gateways', 'paycove_add_gateway_class');
function paycove_add_gateway_class($gateways)
{
    $gateways[] = 'Paycove_Gateway';
    return $gateways;
}

/**
 * Load our classes.
 */
add_action('plugins_loaded', 'paycove_init_gateway_class');
function paycove_init_gateway_class()
{
    // Check if WooCommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Load every file in the includes directory
    foreach (glob(plugin_dir_path(__FILE__) . 'includes/*.php') as $file) {
        require_once $file;
    }
}
