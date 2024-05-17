<?php

add_action( 'woocommerce_blocks_loaded', 'paycove_gateway_block_support' );
function paycove_gateway_block_support() {
	// Here we're including our "gateway block support class".
	require_once __DIR__ . '/includes/class-wc-paycove-gateway-blocks-support.php';

	// Registering the PHP class we have just included.
	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
			$payment_method_registry->register( new WC_Paycove_Gateway_Blocks_Support );
		}
	);
}

/**
 * Let your users know that your payment method is not compatible with the WooCommerce Checkout block.
 * My not be necessary, // @todo check!
 */
add_action( 'before_woocommerce_init', 'paycove_cart_checkout_blocks_compatibility' );
function paycove_cart_checkout_blocks_compatibility() {

    if( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'cart_checkout_blocks',
				__FILE__,
				false // true (compatible, default) or false (not compatible)
			);
    }
		
}