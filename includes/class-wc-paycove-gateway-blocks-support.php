<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (! defined('ABSPATH')) {
    exit;
}

if (! class_exists('WC_Paycove_Gateway_Blocks_Support', false)) {
    return;
}

final class WC_Paycove_Gateway_Blocks_Support extends AbstractPaymentMethodType
{
    private $gateway;

    protected $name = 'paycove'; // payment gateway id

    public function initialize()
    {
        // get payment gateway settings
        $this->settings = get_option("woocommerce_{$this->name}_settings", array());

        // Can initialize your payment gateway here, not required though, so commented out.
        // $gateways = WC()->payment_gateways->payment_gateways();
        // $this->gateway  = $gateways[ $this->name ];
    }

    public function is_active()
    {
        return ! empty($this->settings[ 'enabled' ]) && 'yes' === $this->settings[ 'enabled' ];
    }

    public function get_payment_method_script_handles()
    {

        $asset_path   = plugin_dir_path(__DIR__) . 'build/index.asset.php';
        $version      = null;
        $dependencies = array();
        if(file_exists($asset_path)) {
            $asset        = require $asset_path;
            $version      = isset($asset[ 'version' ]) ? $asset[ 'version' ] : $version;
            $dependencies = isset($asset[ 'dependencies' ]) ? $asset[ 'dependencies' ] : $dependencies;
        }

        wp_register_script(
            'wc-paycove-blocks-integration',
            plugin_dir_url(__DIR__) . 'build/checkout-button-block/index.js',
            $dependencies,
            $version,
            true
        );

        return array( 'wc-paycove-blocks-integration' );
    }

    public function get_payment_method_data()
    {
        return array(
            'title'        => $this->get_setting('title'),
            // almost the same way:
            // 'title'     => isset( $this->settings[ 'title' ] ) ? $this->settings[ 'title' ] : 'Default value';
            'description'  => $this->get_setting('description'),
            // if $this->gateway was initialized on line 15
            // 'supports'  => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] ),

            // example of getting a public key
            // 'publicKey' => $this->get_publishable_key(),
        );
    }

    //private function get_publishable_key() {
    //	$test_mode   = ( ! empty( $this->settings[ 'testmode' ] ) && 'yes' === $this->settings[ 'testmode' ] );
    //	$setting_key = $test_mode ? 'test_publishable_key' : 'publishable_key';
    //	return ! empty( $this->settings[ $setting_key ] ) ? $this->settings[ $setting_key ] : '';
    //}

}
