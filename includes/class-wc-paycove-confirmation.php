<?php

if (! defined('ABSPATH')) {
    exit;
}

class WC_Paycove_Confirmation
{
    /**
     * Class constructor
     */
    public function __construct()
    {
        add_action('template_redirect', [ $this, 'custom_redirect_on_thank_you' ]);
        add_action('woocommerce_view_order', [ $this, 'display_deal_ids_in_order_details' ], 10, 1);
        add_filter('woocommerce_thankyou_order_received_text', [ $this, 'display_deal_ids_on_confirmation_page' ], 10, 2);
    }

    public function display_deal_ids_in_order_details($order_id)
    {
        $order = wc_get_order($order_id);
        $deal_id = $order->get_meta('_deal_id');
        $unique_deal_id = $order->get_meta('_unique_deal_id');

        if ($deal_id || $unique_deal_id) {
            echo '<div class="order-deal-ids">';
            echo '<h2>Paycove Invoice Information</h2>';
            if ($deal_id) {
                echo '<p><strong>Deal ID:</strong> ' . esc_html($deal_id) . '</p>';
            }
            if ($unique_deal_id) {
                echo '<p><strong>Unique Deal ID:</strong> ' . sprintf('<a href="https://feature.paycove.io/checkout/%s" target="_blank">%s</a>', $unique_deal_id, $unique_deal_id) . '</p>';
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
            if ($deal_id) {
                echo '<p><strong>Deal ID:</strong> ' . esc_html($deal_id) . '</p>';
            }
            if ($unique_deal_id) {
                echo '<p><strong>Unique Deal ID:</strong> ' . sprintf('<a href="https://feature.paycove.io/checkout/%s" target="_blank">%s</a>', $unique_deal_id, $unique_deal_id) . '</p>';
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

        if (! isset($_GET['deal_id']) && ! isset($_GET['unique_deal_id'])) {
            return;
        }


        $unique_deal_id = sanitize_text_field($_GET['unique_deal_id']);
        $deal_id = sanitize_text_field($_GET['deal_id']);

        // @todo Check here if the deal_id and unique_deal_id are valid via the Paycove API
        // Assuming they are...

        // Add Deal ID and Unique Deal ID to order meta
        $order->update_meta_data('_deal_id', $deal_id);
        $order->update_meta_data('_unique_deal_id', $unique_deal_id);
        // Add note with the deail_id and unique_deal_id, linking out to the Paycove unique deal ID.
        $order->add_order_note(sprintf('<a href="https://feature.paycove.io/checkout/%s" target="_blank">View UDID on Paycove</a>', $unique_deal_id));
        $order->add_order_note(sprintf('Deal ID: %s', $deal_id));
        // Reduce stock levels.
        wc_reduce_stock_levels($order_id);
        // Empty cart.
        WC()->cart->empty_cart();
        // Mark the order as completed
        $order->update_status('completed', 'Order completed .');
        $order->payment_complete();

        $order->save();
    }

}
new WC_Paycove_Confirmation();
