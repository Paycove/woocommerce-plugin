<?php

class Paycove_Webhook
{
    /**
     * Constructor
     */
    public function __construct()
    {
        add_action( 'init', [ $this, 'register_paycove_webhook_endpoint' ] );
        add_filter( 'query_vars', [ $this, 'add_get_paycove_webhook_query_var' ] );
        add_action( 'template_redirect', [ $this, 'handle_paycove_webhook' ] );
    }

    /**
     * Register the Paycove webhook endpoint
     * 
     * @return void
     */
    public function register_paycove_webhook_endpoint()
    {
      add_rewrite_rule('^paycove-webhook/?$', 'index.php?paycove_webhook=1', 'top');
    }

    /**
     * Add the GET paycove_webhook query var
     * 
     * @param array $vars
     * @return array
     */
    public function add_get_paycove_webhook_query_var($vars)
    {
      $vars[] = 'paycove_webhook';
      $vars[] = 'order_id';
      $vars[] = 'payment_status';
      return $vars;
    }

    /**
     * Add the POST paycove_webhook query var
     * 
     * @param array $vars
     * @return array
     */
    public function add_post_paycove_webhook_query_var($vars)
    {
        $vars[] = 'paycove_webhook';
        return $vars;
    }

    /**
     * Handle the Paycove webhook
     * 
     * @return void
     */
    public function handle_paycove_webhook()
    {
        global $wp_query;

        if (isset($wp_query->query_vars['paycove_webhook'])) {
            // Handle the Paycove webhook here
            $this->handle_get_paycove_order_confirmation( $wp_query );
            // $this->handle_post_paycove_order_confirmation();
            exit;
        }
    }

    /**
     * Handle the GET Paycove order confirmation
     * 
     * @param WP_Query $wp_query
     * @return void
     */
    public function handle_get_paycove_order_confirmation( $wp_query )
    {
      $order_id = sanitize_text_field($wp_query->query_vars['order_id']);
      $payment_status = sanitize_text_field($wp_query->query_vars['payment_status']);

      if ($order_id && $payment_status == 'completed') {
          $order = wc_get_order($order_id);

          if ($order) {
              // Mark the order as completed
              $order->update_status('completed', __('Payment received from Paycove', 'your-textdomain'));

              // Optionally add order note
              $order->add_order_note(__('Order payment completed via Paycove', 'your-textdomain'));

              // Optionally send email notification
              WC()->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order_id);

              // Respond to the webhook request
              echo json_encode(array('message' => 'Order processed successfully.'));
          } else {
              echo json_encode(array('message' => 'Order not found.'));
          }
      } else {
          echo json_encode(array('message' => 'Invalid webhook data.'));
      }

      // End the request
      exit;
    }

    /**
     * Handle the POST Paycove order confirmation
     */
    public function handle_post_paycove_order_confirmation()
    {
        // Get the request body and decode it.
        $data = json_decode(file_get_contents('php://input'), true);

        // Check if the request body contains the required data
        if (isset($data['order_id']) && isset($data['payment_status']) && $data['payment_status'] == 'completed') {
          $order_id = sanitize_text_field($data['order_id']);
          $order = wc_get_order($order_id);
  
          if ($order) {
              // Mark the order as completed
              $order->update_status('completed', __('Payment received from Paycove', 'your-textdomain'));
  
              // Optionally add order note
              $order->add_order_note(__('Order payment completed via Paycove', 'your-textdomain'));
  
              // Optionally send email notification
              WC()->mailer()->emails['WC_Email_Customer_Completed_Order']->trigger($order_id);
  
              // Respond to the webhook request
              wp_send_json_success(array('message' => 'Order processed successfully.'));
          } else {
              wp_send_json_error(array('message' => 'Order not found.'));
          }
      } else {
          wp_send_json_error(array('message' => 'Invalid webhook data.'));
      }
  
      // End the request
      exit;
    }
}

new Paycove_Webhook();