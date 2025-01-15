<?php

if (! defined('ABSPATH')) {
    exit;
}

class WC_Paycove_Confirmation
{
  public $settings = [];
  public $test_mode;
  public $paycove_account_id;
  public $paycove_invoice_template_id;
  public $paycove_api_url;

    /**
     * Class constructor
     */
    public function __construct()
    {
        add_action('template_redirect', [ $this, 'custom_redirect_on_thank_you' ]);
        add_action('woocommerce_view_order', [ $this, 'display_deal_ids_in_order_details' ], 10, 1);
        add_filter('woocommerce_thankyou_order_received_text', [ $this, 'display_deal_ids_on_confirmation_page' ], 10, 2);

        $this->get_wc_paycove_settings();

        $this->test_mode = 'yes' === $this->settings['test_mode'];
        $this->paycove_account_id = $this->settings['paycove_account_id'] ?? '';
        $this->paycove_invoice_template_id = $this->settings['paycove_invoice_template_id'] ?? '';
        $this->paycove_api_url = $this->test_mode ? 'https://staging.paycove.io/' : 'https://app.paycove.io/';
    }

    public function get_wc_paycove_settings()
    {
        $settings = get_option('woocommerce_paycove_settings', []);
        $this->settings = $settings;
    }

    public function display_deal_ids_in_order_details($order_id)
    {
        $order = wc_get_order($order_id);
        $deal_id = $order->get_meta('_deal_id');
        $unique_deal_id = $order->get_meta('_unique_deal_id');

        if ($deal_id || $unique_deal_id) {
            echo '<div class="order-deal-ids">';
            echo '<h2>Paycove Invoice Information</h2>';

            if ('on-hold' === $order->get_status()) {
                echo '<p><strong>Note:</strong> Your order is on-hold, please follow up with a payment or contact support.</p>';
            }
            if ($deal_id) {
                echo '<p><strong>Deal ID:</strong> ' . esc_html($deal_id) . '</p>';
            }
            if ($unique_deal_id) {
                echo '<p><strong>Unique Deal ID:</strong> ' . sprintf('<a href="https://staging.paycove.io/checkout/%s" target="_blank">%s</a>', esc_html($unique_deal_id), esc_html($unique_deal_id)) . '</p>';
            }
            echo '</div>';
        }
    }

    public function display_deal_ids_on_confirmation_page($text, $order)
    {
        if(! is_wc_endpoint_url('order-received') || ! $order) {
            return $text;
        }

        $deal_id = $order->get_meta('_deal_id');
        $unique_deal_id = $order->get_meta('_unique_deal_id');

        if ($deal_id || $unique_deal_id) {
            ob_start();
            echo '<div class="order-deal-ids" style="font-size: 16px; border: 1px solid #eee; border-radius: 4px; padding: 16px;">';
            echo '<h3 style="margin-top:0;">Paycove Invoice Information</h3>';

            if ('on-hold' === $order->get_status()) {
                echo '<p><strong>Note:</strong> Your order is on-hold, please follow up with a payment or contact support.</p><br/>';
            }
            if ($deal_id) {
                echo '<p><strong>Deal ID:</strong> ' . esc_html($deal_id) . '</p>';
            }
            if ($unique_deal_id) {
                echo '<p><strong>Unique Deal ID:</strong> ' . sprintf('<a href="https://staging.paycove.io/checkout/%s" target="_blank">%s</a>', esc_html($unique_deal_id), esc_html($unique_deal_id)) . '</p>';
            }
            echo '</div>';
            $text .= ob_get_clean();
        }
        return $text;
    }

    public function custom_redirect_on_thank_you()
    {
        // Run through the validation process
        if (! is_wc_endpoint_url('order-received')) {
            return;
        }

        $order_id = absint(get_query_var('order-received'));

        if (! $order_id) {
            return;
        }

        $order = wc_get_order($order_id);

        if (! $order) {
            return;
        }

        if ($order->get_status() !== 'pending' || $order->get_payment_method() !== 'paycove') {
            return;
        }

        if (! isset($_GET['deal_id']) || ! isset($_GET['unique_deal_id'])) {
            return;
        }

        $unique_deal_id = sanitize_text_field(wp_unslash($_GET['unique_deal_id']));
        $deal_id = sanitize_text_field(wp_unslash($_GET['deal_id']));

        // Check here if the deal_id and unique_deal_id are valid via the Paycove API
        try {
            // Include WooCommerce logger if WooCommerce is available
            if (class_exists('WC_Logger')) {
                $logger = new WC_Logger();
            }

            // Make the request
            // @todo replace with the actual URL:
            $response = wp_remote_get($this->paycove_api_url. "api/deals/$unique_deal_id/status");

            // Check for errors
            if (is_wp_error($response)) {
                throw new Exception("Request Error: " . $response->get_error_message());
            }

            // Get and log the response body
            $code = wp_remote_retrieve_response_code($response);
            $logger->info("API Response Code: " . $code, ['source' => 'paycove-api-requests']);
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON response
            $response_data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully decoded JSON
                $payment_status = isset($response_data['status']) ? $response_data['status'] : null;

                // Log with WooCommerce logger if available
                if (isset($logger)) {
                    $logger->info("Unique Deal ID: " . $unique_deal_id, ['source' => 'paycove-api-requests']);
                    $logger->info("Payment Status: " . $payment_status, ['source' => 'paycove-api-requests']);
                }
            } else {
                // JSON decoding failed
                if (isset($logger)) {
                    $logger->error("JSON decode error: " . json_last_error_msg(), ['source' => 'paycove-api-requests']);
                }
            }
        } catch (Exception $e) {
            // Log the error
            $logger->error($e->getMessage(), ['source' => 'paycove-api-requests']);
            // Return an error message
            wc_add_notice('There was an error processing your order. Please try again.', 'error');
            return;
        }

        if($payment_status === 'Paid') {
            // Add Deal ID and Unique Deal ID to order meta
            $order->update_meta_data('_deal_id', $deal_id);
            $order->update_meta_data('_unique_deal_id', $unique_deal_id);
            // Add note with the deail_id and unique_deal_id, linking out to the Paycove unique deal ID.
            $order->add_order_note(sprintf('<a href="https://staging.paycove.io/checkout/%s" target="_blank">View UDID on Paycove</a>', $unique_deal_id));
            $order->add_order_note(sprintf('Order is %s on Paycove.', $payment_status));
            $order->add_order_note(sprintf('Deal ID: %s', $deal_id));
            // Reduce stock levels.
            wc_reduce_stock_levels($order_id);
            // Empty cart.
            WC()->cart->empty_cart();
            // Mark the order as completed
            // $order->update_status('completed', 'Order completed .');
            $order->payment_complete();

            $order->save();
            return;
        }

        if($payment_status === 'Pending') {
            // Add Deal ID and Unique Deal ID to order meta
            $order->update_meta_data('_deal_id', $deal_id);
            $order->update_meta_data('_unique_deal_id', $unique_deal_id);
            $order->update_meta_data('_payment_status', 'on-hold');
            // Add note with the deail_id and unique_deal_id, linking out to the Paycove unique deal ID.
            $order->add_order_note(sprintf('<a href="https://staging.paycove.io/checkout/%s" target="_blank">View Unique Deal ID on Paycove</a>', $unique_deal_id));
            $order->add_order_note(sprintf('Order is %s on Paycove.', $payment_status));
            $order->add_order_note(sprintf('Deal ID: %s', $deal_id));
            // Reduce stock levels.
            wc_reduce_stock_levels($order_id);
            // Empty cart.
            WC()->cart->empty_cart();
            // Mark the order as On Hold, admin will need to manually update the order status.
            $order->update_status('on-hold', 'Order on hold, waiting for completed payment.');

            $order->save();
            return;
        }

        // Return an error message
        wc_add_notice('There was an error processing your order. Please try again.', 'error');
        return;
    }

}
new WC_Paycove_Confirmation();
