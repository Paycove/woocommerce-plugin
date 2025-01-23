<?php
/**
 * Plugin Name: Paycove
 * Plugin URI: https://paycove.io
 * Description: Integrate your store with Paycove.
 * Author: Paycove
 * Author URI: https://paycove.io
 * Version: 0.1.5
 * License: MIT
 * Text Domain: paycove
 */

if (! defined('ABSPATH')) {
    exit;
}

// Define PATH and URL constants.
define('PAYCOVE_GATEWAY_PATH', plugin_dir_path(__FILE__));
define('PAYCOVE_GATEWAY_URL', plugin_dir_url(__FILE__));

/**
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter('woocommerce_payment_gateways', 'paycove_add_gateway_class');
function paycove_add_gateway_class($gateways)
{
    $gateways[] = 'WC_Paycove_Gateway'; // your class name is here
    return $gateways;
}

/**
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action('plugins_loaded', 'paycove_init_gateway_class');
function paycove_init_gateway_class()
{
    // Load every file in the includes directory
    foreach (glob(plugin_dir_path(__FILE__) . 'includes/*.php') as $file) {
        require_once $file;
    }
}
