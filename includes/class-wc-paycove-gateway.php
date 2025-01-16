<?php

if (! defined('ABSPATH')) {
    exit;
}

class WC_Paycove_Gateway extends WC_Payment_Gateway
{
    public $id;
    public $icon;
    public $form_fields = [];
    public $has_fields;
    public $method_title;
    public $method_description;
    public $supports;
    public $title;
    public $description;
    public $enabled;
    public $test_mode;
    public $paycove_account_id;
    public $paycove_invoice_template_id;
    public $paycove_api_url;
    public $paycove_base_url;
    public $private_key;
    public $publishable_key;

    /**
     * Class constructor, more about it in Step 3
     */
    public function __construct()
    {
        $this->id = 'paycove'; // payment gateway plugin ID
        // $this->icon = PAYCOVE_GATEWAY_URL . 'assets/paycove-logo-wide-small.svg';
        $this->icon = PAYCOVE_GATEWAY_URL . 'assets/paycove-methods.svg';
        $this->has_fields = true; // in case you need a custom credit card form
        $this->method_title = 'Paycove';
        $this->method_description = 'Integrate with Paycove'; // will be displayed on the options page

        // gateways can support subscriptions, refunds, saved payment methods,
        // but in this tutorial we begin with simple payments
        $this->supports = array(
          'products',
          'refunds'
        );

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->test_mode = 'yes' === $this->get_option('test_mode');
        $this->paycove_account_id = $this->get_option('paycove_account_id');
        $this->paycove_invoice_template_id = $this->get_option('paycove_invoice_template_id');
        $this->paycove_api_url = $this->test_mode ? 'https://staging.paycove.io/api/checkout/' : 'https://app.paycove.io/api/checkout/';
        $this->paycove_base_url = $this->test_mode ? 'https://staging.paycove.io/' : 'https://app.paycove.io/';
        $this->private_key = $this->test_mode ? $this->get_option('test_private_key') : $this->get_option('private_key');
        $this->publishable_key = $this->test_mode ? $this->get_option('test_publishable_key') : $this->get_option('publishable_key');

        // This action hook saves the settings
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ));

        // Method with all the options fields
        $this->init_form_fields();

        // Load the settings.
        $this->init_settings();
    }

    /**
     * Plugin options, we deal with it in Step 3 too
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
          'enabled' => array(
            'title'       => 'Enable/Disable',
            'label'       => 'Enable Paycove',
            'type'        => 'checkbox',
            'description' => '',
            'default'     => 'yes'
          ),
          'title' => array(
            'title'       => 'Title',
            'type'        => 'text',
            'description' => 'This controls the title which the user sees during checkout.',
            'default'     => 'Pay on Paycove',
            'desc_tip'    => true,
          ),
          'description' => array(
            'title'       => 'Description',
            'type'        => 'textarea',
            'description' => 'This controls the description which the user sees during checkout.',
            'default'     => 'Pay with your credit card, bank account, and more.',
          ),
          'test_mode' => array(
            'title'       => 'Test mode',
            'label'       => 'Enable Test Mode',
            'type'        => 'checkbox',
            'description' => 'Place the payment gateway in test mode using test API keys. You should have an account on staging.paycove.io for this to work.',
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
            'description' => 'This is the template ID of your provided by Paycove.<br/><strong>This should not be blank.</strong> See your templates <a href="' . $this->paycove_base_url . 'invoice-template" target="_blank">here</a>.',
            'required'    => 'required'
          ),
        );
    }


    public function payment_fields()
    {
        $description = $this->get_description();
        ?>
      <div style="display: flex; align-items: center;">
        <img style="height: 60px; margin-right: 10px;" src="<?php echo esc_url(PAYCOVE_GATEWAY_URL . 'assets/card-payment-icon.svg') ?>" alt="paycove logo" />
        <span><?php echo esc_html(trim($description)) ?></span>
      </div>
      <br>
      <small>Secured by </small><img src="<?php echo esc_url(PAYCOVE_GATEWAY_URL . 'assets/paycove-logo-wide-small.svg') ?>" alt="paycove logo" />

      <?php
        if ($this->test_mode) {
            /* translators: draft saved date format, see http://php.net/date */
            echo sprintf('<div style="border-left: 3px solid #3173DC; border-radius: 3px; padding-left: 10px; margin-top: 20px;">In test mode, you can use the card number 4242424242424242 with any CVC and a valid expiration date or check the <a href="%s" target="_blank">Testing Stripe documentation</a> for more card numbers.</div>', 'https://stripe.com/docs/testing');
        }
    }

    /**
     * get_base64_image
     *
     * @param string $image_url
     * @return string
     */
    public function get_base64_image($image_url = '')
    {
        if (empty($image_url)) {
            return '';
        }

        // Desired dimensions
        $newWidth = 60;
        $newHeight = 60;

        // Use wp_remote_get to fetch the image data
        $response = wp_remote_get($image_url);

        if (class_exists('WC_Logger')) {
            $logger = new WC_Logger();
        }

        if (is_wp_error($response)) {
            // Handle the error appropriately
            if (isset($logger)) {
                $logger->error('Failed to fetch image: ' . $response->get_error_message(), ['source' => 'paycove-api-requests']);
            }
            return;
        }

        $image_data = wp_remote_retrieve_body($response);
        if ($image_data) {
            // Create the image resource from the fetched data
            $image = imagecreatefromstring($image_data);

            if ($image === false) {
                if (isset($logger)) {
                    $logger->error('Failed to create image from string.', ['source' => 'paycove-api-requests']);
                }
                return;
            }
        } else {
            if (isset($logger)) {
                $logger->error('No image data retrieved from URL', ['source' => 'paycove-api-requests']);
            }
            return;
        }

        // Load the original image
        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);

        // Create a blank canvas for the resized image
        $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

        // Resize the original image onto the blank canvas
        imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        // Encode the resized image to Base64
        ob_start();
        imagepng($resizedImage);
        $base64Image = base64_encode(ob_get_clean());
        $mime_type = mime_content_type($image_url);

        // Clean up resources
        imagedestroy($image);
        imagedestroy($resizedImage);

        return "data:$mime_type;base64, $base64Image";
    }

    /**
     * This will process the payment and submit the payment details to the payment processor.
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

                // Get the image data.
                $image_id = $product->get_image_id(); // Get the image attachment ID
                $image_url = wp_get_attachment_url($image_id); // Get the image URL
                $base64Image = $this->get_base64_image($image_url);

                $line_items[] = [
                    'name'     => $item->get_name(),
                    'price'    => $product ? wc_get_price_to_display($product) : '',
                    'quantity' => $item->get_quantity(),
                    'thumbnail'    => $base64Image,
                    'description' => substr($product->get_description(), 0, 50) . '...',
                ];
            }

            // Get contact information
            $contact = [
                'name'  => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'phone' => $order->get_billing_phone(),
                'line1' => $order->get_billing_address_1(),
                'line2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postal_code' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
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
          "template_id" => $this->paycove_invoice_template_id,
          "subtotal" => $order->get_subtotal(),
          "fees" => [
            [ "label" => "Tax", "amount" => $order->get_total_tax(), "percent" => null ],
            [ "label" => "Shipping", "amount" => $order->get_shipping_total(), "percent" => null ]
          ],
          "total" => $order->get_total(),
          "success_url" => $this->get_return_url($order),
          "cancel_url" => wc_get_checkout_url(),
          "failure_url" => wc_get_checkout_url(),
        ];

        // Set up the request arguments
        $args = [
          'body'        => wp_json_encode($data),
          'headers'     => [
              'Content-Type' => 'application/json'
          ],
          'method'      => 'POST',
          'data_format' => 'body'
        ];

        $order->add_order_note('Attempting checkout on Paycove...', false);

        try {
            // Make the request
            $response = wp_remote_post($this->paycove_api_url . $this->paycove_account_id, $args);

            // Check for errors.
            if (is_wp_error($response)) {
                throw new Exception("Request Error: " . $response->get_error_message());
            }

            // Get and log the code and response body.
            $code = wp_remote_retrieve_response_code($response);
            $logger->info("API Response Code: " . $code, ['source' => 'paycove-api-requests']);
            $body = wp_remote_retrieve_body($response);

            // Decode the JSON response
            $response_data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                // Successfully decoded JSON
                $checkout_url = isset($response_data['checkout_url']) ? $response_data['checkout_url'] : null;

                // Log the checkout URL.
                if (isset($logger)) {
                    $logger->info("Checkout URL: " . $checkout_url, ['source' => 'paycove-api-requests']);
                }
            } else {
                if (isset($logger)) {
                    $logger->error("JSON decode error: " . json_last_error_msg(), ['source' => 'paycove-api-requests']);
                }
            }

            // Log with WooCommerce logger if available
            if (isset($logger)) {
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

        $logger->info("Should redirect to Paycove...", ['source' => 'paycove-api-requests']);

        // This will redirect to the the paycove page, and we can send whatever we want.
        return [
          'result'                => 'success',
          'redirect'              => $checkout_url,
        ];
    }
}
