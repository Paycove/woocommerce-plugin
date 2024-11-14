<?php
/**
 * Plugin Name: Paycove Payment Gateway
 * Plugin URI: https://paycove.io
 * Description: Integrate your store with Paycove.
 * Author: Nate Finch
 * Author URI: http://n8finch.com
 * Version: 1.0.0
 * License: MIT
 */

if (! defined('ABSPATH')) {
    exit;
}

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
    require_once __DIR__ . '/includes/checkout-block.php';
    require_once __DIR__ . '/includes/class-wc-paycove-gateway.php';
    require_once __DIR__ . '/includes/class-wc-paycove-confirmation.php';
    require_once __DIR__ . '/includes/class-paycove-webhook.php';
    require_once __DIR__ . '/includes/class-create-order-from-cart.php';

    // Load every file in the includes directory
    foreach (glob(plugin_dir_path(__FILE__) . 'includes/*.php') as $file) {
        require_once $file;
    }
}
