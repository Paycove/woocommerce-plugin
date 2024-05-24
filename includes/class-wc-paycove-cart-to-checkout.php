<?php

class WC_Paycove_Cart_To_Checkout
{
    /**
     * Constructor
     */
    public function __construct()
    {
        // remove_action('woocommerce_proceed_to_checkout', 'woocommerce_button_proceed_to_checkout', 20);
        add_action('woocommerce_proceed_to_checkout', array($this, 'custom_proceed_to_checkout_button'), 20);
    }

    /**
     * Custom Proceed to Checkout button
     */
    public function custom_proceed_to_checkout_button()
    {
        // Get the checkout URL
        $checkout_url = wc_get_checkout_url();

        // Get the cart total
        $cart_total = WC()->cart->total;

        // Get the account ID
        $paycove_settings = get_option('woocommerce_paycove_settings', []);
        $account_id = $paycove_settings['paycove_account_id'] ?? 'no_key_found';
        $invoice_template_id = $paycove_settings['paycove_invoice_template_id'] ?? 'no_key_found';

        // Get current user name and email if logged in.
        $current_user = wp_get_current_user();
        $name = $current_user->display_name;
        $email = $current_user->user_email;

        // Construct your custom URL with the total
        $custom_url = 'https://staging.paycove.io/checkout-builder-form?type=invoice&invoice_template_id=' . $invoice_template_id . '&total=' . $cart_total . '&account_id=' . $account_id . '&adjustable_amount=true&full_name=' . $name . '&email=' . $email . '&line_items[0][name]=Payment for something';

        ?>
      <a href="<?php echo esc_url($custom_url); ?>" class="checkout-button button alt wc-forward wp-element-button">
          Checkout on Paycove (Total: <?php echo wc_price($cart_total); ?>)
      </a>
      <?php
    }
}

new WC_Paycove_Cart_To_Checkout();
