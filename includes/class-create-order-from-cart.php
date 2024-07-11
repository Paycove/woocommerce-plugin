<?php

class Create_Order_From_Cart
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action( 'rest_api_init', [$this, 'add_route_create_order_from_cart'], 10 );
    }
    
    public function add_route_create_order_from_cart() {
      register_rest_route('paycove/v1', '/create-pending-order-from-cart', array(
          'methods' => 'POST',
          'callback' => [$this, 'create_pending_order_from_cart' ],
          'permission_callback' => '__return_true',
      ));
  }
  
  public function create_pending_order_from_cart() {
    // Ensure WooCommerce is loaded
    if (!class_exists('WooCommerce')) {
        return;
    }

    // Get the post body
    $request = file_get_contents('php://input');
    $data = json_decode($request, true);

    if (empty($data)) {
        return new WP_Error('no_data', 'No data provided', array('status' => 400));
    }

    // Set customer billing and shipping data (this should come from the user input)
    $customer_data = array(
        'billing' => $data['billing'],
        'shipping' => $data['shipping'],
    );

    // Create a new WooCommerce order
    $order = wc_create_order();
    
    // Add items to the order
    foreach ($data['items'] as $cart_item) {
        $product = wc_get_product($cart_item['id']);
        $order->add_product($product, $cart_item['quantity']);
    }

    // Set billing and shipping data
    $order->set_address($customer_data['billing'], 'billing');
    $order->set_address($customer_data['shipping'], 'shipping');

    // Calculate totals
    $order->calculate_totals();
    
    // Set order status to pending
    $order->update_status('pending', __('Awaiting Paycove payment', 'your-textdomain'));

    // Return the order ID
    return $order->get_id();
}
}

new Create_Order_From_Cart();