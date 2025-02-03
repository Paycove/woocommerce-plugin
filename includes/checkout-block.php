<?php

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly

add_action('woocommerce_blocks_loaded', 'paycove_gateway_block_support');

function paycove_gateway_block_support()
{
    // Here we're including our "gateway block support class".
    require_once __DIR__ . '/class-paycove-gateway-blocks-support.php';

    // Registering the PHP class we have just included.
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {

            if (!class_exists('Paycove_Gateway_Blocks_Support')) {
                return;
            }
            $payment_method_registry->register(new Paycove_Gateway_Blocks_Support());
        }
    );
}
