<?php

if ( ! defined('ABSPATH') ) {
    exit;
}

// if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
//     return;
// }


class WC_Paycove_Gateway extends WC_Payment_Gateway
{
  public $id;
  public $icon;
  public $has_fields;
  public $method_title;
  public $method_description;
  public $supports;
  public $title;
  public $description;
  public $enabled;
  public $template_id;
  public $test_mode;
  public $private_key;
  public $publishable_key;



    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {
        $this->id = 'paycove'; // payment gateway plugin ID
        $this->icon = 'https://www.paycove.io/hubfs/Imported%20images/paycove_logo_explore_nautical_wide-08.svg'; // URL of the icon that will be displayed on checkout page near your gateway name
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Paycove Gateway';
        $this->method_description = 'Integrate with Paycove'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
          'products',
          'refunds'
        );

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->template_id = $this->get_option('paycove_invoice_template_id');
        $this->test_mode = 'yes' === $this->get_option('test_mode');
        $this->private_key = $this->test_mode ? $this->get_option('test_private_key') : $this->get_option('private_key');
        $this->publishable_key = $this->test_mode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        // We need custom JavaScript to obtain a token
        add_action('wp_enqueue_scripts', array( $this, 'payment_scripts' ));

        // You can also register a webhook here
        add_action('woocommerce_api_{webhook name}', array( $this, 'webhook' ));
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
        'enabled' => array(
          'title'       => 'Enable/Disable',
          'label'       => 'Enable Paycove Gateway',
          'type'        => 'checkbox',
          'description' => '',
          'default'     => 'no'
        ),
        'title' => array(
          'title'       => 'Title',
          'type'        => 'text',
          'description' => 'This controls the title which the user sees during checkout.',
          'default'     => 'Credit Card',
          'desc_tip'    => true,
        ),
        'description' => array(
          'title'       => 'Description',
          'type'        => 'textarea',
          'description' => 'This controls the description which the user sees during checkout.',
          'default'     => 'Pay with your credit card via our super-cool payment gateway.',
        ),
        'test_mode' => array(
          'title'       => 'Test mode',
          'label'       => 'Enable Test Mode',
          'type'        => 'checkbox',
          'description' => 'Place the payment gateway in test mode using test API keys.',
          'default'     => 'yes',
          'desc_tip'    => true,
        ),
        'paycove_account_id' => array(
          'title'       => 'Paycove Account ID',
          'type'        => 'text',
          'description' => 'This is the account ID provided by Paycove.<br/><strong>This should not be blank.<strong>',
          'required'    => 'required'
        ),
        'paycove_invoice_template_id' => array(
          'title'       => 'Paycove Invoice Template ID',
          'type'        => 'text',
          'description' => 'This is the template ID of your provided by Paycove.<br/><strong>This should not be blank.<strong>',
          'required'    => 'required'
        ),
        'test_publishable_key' => array(
          'title'       => 'Test Publishable Key',
          'type'        => 'text'
        ),
        'test_private_key' => array(
          'title'       => 'Test Private Key',
          'type'        => 'password',
        ),
        'publishable_key' => array(
          'title'       => 'Live Publishable Key',
          'type'        => 'text'
        ),
        'private_key' => array(
          'title'       => 'Live Private Key',
          'type'        => 'password'
        )
        );
    }

    /**
     * You will need it if you want your custom credit card form, Step 4 is about it
     */
    public function payment_fields_xxx()
    {
        // ok, let's display some description before the payment form
        if($this->description) {
            // you can instructions for test mode, I mean test card numbers etc.
            if($this->test_mode) {
                $this->description .= ' TEST MODE ENABLED. In test mode, you can use the card numbers listed in <a href="#">documentation</a>.';
                $this->description  = trim($this->description);
            }
            // display the description with <p> tags etc.
            echo wpautop(wp_kses_post($this->description));
        }

        // I will echo() the form, but you can close PHP tags and print it directly in HTML
        echo '<fieldset id="wc-' . esc_attr($this->id) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';

        // Add this action hook if you want your custom payment gateway to support it
        do_action('woocommerce_credit_card_form_start', $this->id);

        // I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
        echo '<div class="form-row form-row-wide"><label>Card Number <span class="required">*</span></label>
          <input id="paycove_ccNo" type="text" autocomplete="off">
          </div>
          <div class="form-row form-row-first">
            <label>Expiry Date <span class="required">*</span></label>
            <input id="paycove_expdate" type="text" autocomplete="off" placeholder="MM / YY">
          </div>
          <div class="form-row form-row-last">
            <label>Card Code (CVC) <span class="required">*</span></label>
            <input id="paycove_cvv" type="password" autocomplete="off" placeholder="CVC">
          </div>
          <div class="clear"></div>';

        do_action('woocommerce_credit_card_form_end', $this->id);

        echo '<div class="clear"></div></fieldset>';

    }

    /**
     * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
     */
    public function payment_scripts()
    {
        // we need JavaScript to process a token only on cart/checkout pages, right?
        if(! is_cart() && ! is_checkout() && ! isset($_GET[ 'pay_for_order' ])) {
            return;
        }

        // if our payment gateway is disabled, we do not have to enqueue JS too
        if('no' === $this->enabled) {
            return;
        }

        // no reason to enqueue JavaScript if API keys are not set
        if(empty($this->private_key) || empty($this->publishable_key)) {
            return;
        }

        // do not work with card detailes without SSL unless your website is in a test mode
        if(! $this->test_mode && ! is_ssl()) {
            return;
        }

        // let's suppose it is our payment processor JavaScript that allows to obtain a token
        // wp_enqueue_script('paycove_js', 'some payment processor site/api/token.js');

        // and this is our custom JS in your plugin directory that works with token.js
        // wp_register_script('woocommerce_paycove', plugins_url('paycove.js', __FILE__), array( 'jquery', 'paycove_js' ));

        // in most payment processors you have to use PUBLIC KEY to obtain a token
        wp_localize_script(
            'woocommerce_paycove',
            'paycove_params',
            array(
            'publishableKey' => $this->publishable_key
            )
        );

        wp_enqueue_script('woocommerce_paycove');
    }

    /**
     * Fields validation, more in Step 5
     * // @todo probably not needed to validate fields ourselves
     */
    // public function validate_fields()
    // {

    //     if(empty($_POST[ 'billing_first_name' ])) {
    //         wc_add_notice('First name is required!', 'error');
    //         return false;
    //     }
    //     return true;

    // }

    /**
     * This
     */
    public function process_payment($order_id)
    {
      // Include WooCommerce logger if WooCommerce is available
      if (class_exists('WC_Logger')) {
        $logger = new WC_Logger();
      }

      // Get the order object
      $order = wc_get_order($order_id);

      if ($order) {
          // Get line items
          $line_items = [];
          foreach ($order->get_items() as $item_id => $item) {
              $product = $item->get_product();

              // Get the Path of the full image
              // $encoded_image = get_attached_file( $product->get_image_id() );
              // $logger->info("Image: " . print_r($encoded_image, true), ['source' => 'paycove-api-requests']);
              // if ( file_get_contents( $encoded_image ) !== false) {
              //   $encoded_image = base64_encode( file_get_contents( $encoded_image ) );
              // }

            // Get the URL
            $encoded_image = wp_get_attachment_image_src( get_post_thumbnail_id( $item->get_product_id() ), 'woocommerce_thumbnail' );
              $logger->info("Image: " . print_r($encoded_image[0], true), ['source' => 'paycove-api-requests']);


              // $imageData = file_get_contents($encoded_image[0]);
              // $logger->info("ImageData: " . print_r($imageData, true), ['source' => 'paycove-api-requests']);
              // $base64Image = base64_encode($imageData);
              // $logger->info("base64Image: " . print_r($base64Image, true), ['source' => 'paycove-api-requests']);
              // $encoded = 'data:image/' . mime_content_type($encoded_image[0]) . ';base64,' . $base64Image;

              $line_items[] = [
                  'name'     => $item->get_name(),
                  'price'    => $product ? wc_get_price_to_display($product) : '',
                  'quantity' => $item->get_quantity(),
                  // 'image'    => $encoded_image[0],
              ];
          }

          // Retrieve customer contact information
          $billing_address = [
              'first_name' => $order->get_billing_first_name(),
              'last_name' => $order->get_billing_last_name(),
              'email' => $order->get_billing_email(),
              'phone' => $order->get_billing_phone(),
              'address_1' => $order->get_billing_address_1(),
              'address_2' => $order->get_billing_address_2(),
              'city' => $order->get_billing_city(),
              'state' => $order->get_billing_state(),
              'postcode' => $order->get_billing_postcode(),
              'country' => $order->get_billing_country(),
          ];

          $shipping_address = [
              'first_name' => $order->get_shipping_first_name(),
              'last_name' => $order->get_shipping_last_name(),
              'address_1' => $order->get_shipping_address_1(),
              'address_2' => $order->get_shipping_address_2(),
              'city' => $order->get_shipping_city(),
              'state' => $order->get_shipping_state(),
              'postcode' => $order->get_shipping_postcode(),
              'country' => $order->get_shipping_country(),
          ];

          // Get contact information
          $contact = [
              'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
              'email' => $order->get_billing_email(),
              'first_name' => $order->get_billing_first_name(),
              'last_name' => $order->get_billing_last_name(),
              'phone' => $order->get_billing_phone(),
              'line1' => $order->get_billing_address_1(),
              'address_2' => $order->get_billing_address_2(),
              'city' => $order->get_billing_city(),
              'state' => $order->get_billing_state(),
              'postal_code' => $order->get_billing_postcode(),
              'country' => $order->get_billing_country(),
              'billing_address' => $billing_address,
              'shipping_address' => $shipping_address,
          ];
      } else {
          echo "Order not found.";
      }

      // Define the data array
      $data = [
        "order_id" => $order_id,
        "key" => $order->get_order_key(),
        "line_items" => $line_items,
        "contact" => $contact,
        "type" => "invoice",
        "template_id" => $this->template_id,
        "subtotal" => $order->get_subtotal(),
        "fees" => [
          [ "label"=> "Tax", "amount"=> $order->get_total_tax(), "percent"=> null ],
          [ "label"=> "Shipping", "amount"=> $order->get_shipping_total(), "percent"=> null ]
        ],
        "total" => $order->get_total(),
        "success_url" => $this->get_return_url($order),
        "cancel_url" => wc_get_checkout_url(),
        "failure_url" => "https://austindevs.github.io/paycove-checkout/"
      ];

      // Set up the request arguments
      $args = [
        'body'        => json_encode($data),
        'headers'     => [
            'Content-Type' => 'application/json'
        ],
        'method'      => 'POST',
        'data_format' => 'body'
      ];

      $logger->info("Attemping API call wtih: " . print_r($args, true), ['source' => 'paycove-api-requests']);

      $order->add_order_note('Attempting checkout on Paycove...', false);

      try {
        // Make the request
        $response = wp_remote_post('https://local.paycove.io/api/checkout/e3e742f36fb750a0151631184000807d', $args);

        // Check for errors
        if (is_wp_error($response)) {
            throw new Exception("Request Error: " . $response->get_error_message());
        }

        // Get and log the response body
        $body = wp_remote_retrieve_body($response);

        // Decode the JSON response
        $response_data = json_decode($body, true);

        if (json_last_error() === JSON_ERROR_NONE) {
          // Successfully decoded JSON
          $checkout_url = isset($response_data['checkout_url']) ? $response_data['checkout_url'] : null;
          
          // Log with WooCommerce logger if available
          if (isset($logger)) {
              $logger->info("Decoded API Response: " . print_r($response_data, true), ['source' => 'paycove-api-requests']);
              $logger->info("Checkout URL: " . $checkout_url, ['source' => 'paycove-api-requests']);
          }
        } else {
            // JSON decoding failed
            error_log("JSON decode error: " . json_last_error_msg());
        
            if (isset($logger)) {
                $logger->error("JSON decode error: " . json_last_error_msg(), ['source' => 'paycove-api-requests']);
            }
        }

        // Log with WooCommerce logger if available
        if (isset($logger)) {
            $logger->info("API Response Body Type: " . gettype($body), ['source' => 'paycove-api-requests']);
            $logger->info("API Response Body: " . $body, ['source' => 'paycove-api-requests']);
        }
      } catch (Exception $e) {
        // Log the exception message
        if (isset($logger)) {
            $logger->error("Exception caught: " . $e->getMessage(), ['source' => 'paycove-api-requests']);
        }

      $order->add_order_note('An error occurred: ' . $e->getMessage(), false);
      
        return [
          'result'   => 'fail',
          'redirect' => '',
        ];
      }

      $logger->info("Should redirect...", ['source' => 'paycove-api-requests']);

      // This is what the classic checkout expects
      // wp-content/plugins/woocommerce/includes/class-wc-checkout.php
      // This will redirect to the the paycove page, and we can send whatever we want.
      // @todo probably need to reserve the stock here, update the order status, etc.
      return [
        'result'                => 'success',
        'redirect'              => $checkout_url,
      ];

      // For the block checkout, the new file is wp-content/plugins/woocommerce/src/StoreApi/Routes/V1/Checkout.php
      // And the CheckoutTrait is used as well: wp-content/plugins/woocommerce/src/StoreApi/Utilities/CheckoutTrait.php

        $localized_message = __( 'Payment processing failed. Please retry.', 'woo-pay-addons' );
        $order->add_order_note( $localized_message );

        if(200 === wp_remote_retrieve_response_code($response)) {

            $body = json_decode(wp_remote_retrieve_body($response), true);

            // it could be different depending on your payment processor
            if('APPROVED' === $body[ 'response' ][ 'responseCode' ]) {

                // we received the payment
                $order->payment_complete();
                $order->reduce_order_stock();

                // some notes to customer (replace true with false to make it private)
                $order->add_order_note('Hey, your order is paid! Thank you!', true);

                // Empty cart
                WC()->cart->empty_cart();

                // Redirect to the thank you page
                return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order),
                );

            } else {
                wc_add_notice('Please try again.', 'error');
                return;
            }

        } else {
            wc_add_notice('Connection error.', 'error');
            return;
        }

    }

    // public function process_refund( $order_id, $amount = null )
    // {
    //     // Do your refund here. Refund $amount for the order with ID $order_id
    //     return true;
    // }

    /**
     * In case you need a webhook, like PayPal IPN etc
     */
    // public function webhook()
    // {

    //     $order = wc_get_order($_GET[ 'id' ]);
    //     $order->payment_complete();
    //     $order->reduce_order_stock();

    //     update_option('webhook_debug', $_GET);
    // }
}
