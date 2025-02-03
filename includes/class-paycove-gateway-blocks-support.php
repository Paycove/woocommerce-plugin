<?php

if (! defined('ABSPATH')) {
    exit;
}

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class Paycove_Gateway_Blocks_Support extends AbstractPaymentMethodType
{
    protected $name = 'paycove';
    protected $settings;

    public function initialize()
    {
        // Get payment gateway settings.
        $this->settings = get_option("woocommerce_{$this->name}_settings", array());
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
            'description'  => $this->get_setting('description'),
            'icon'         => plugin_dir_url(__DIR__) . 'assets/paycove-icon.png',
            'testMode'     => 'yes' === $this->get_setting('test_mode'),
        );
    }
}
