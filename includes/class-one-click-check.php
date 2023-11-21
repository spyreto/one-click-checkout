<?php

/**
 * The core plugin class.
 */

class One_Click_Checkout
{

  /**
   * Initialize the class and set its properties.
   */
  public function __construct()
  {
  }

  /**
   * Register all of the hooks related to the plugin functionality.
   */
  public function run()
  {
    // Check if WooCommerce is active, hook in the rest of your plugin's code if it is.
    add_action('admin_init', array($this, 'check_woocommerce_active'));
    // Update the settings from the settings page.
    add_action('woocommerce_update_options_one_click_checkout', array($this, 'update_settings'));
    // Register the stylesheets for the public-facing side of the site.
    add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    // Add custom CSS to the "Buy now" button.
    add_action('wp_head', array($this, 'add_custom_css'));
    // Save user data for one-click checkout after a successful purchase.
    add_action('woocommerce_order_status_completed', array($this, 'save_checkout_data'));
    // Display the Buy Now button on the product page.
    add_action('woocommerce_after_add_to_cart_button', array($this, 'display_buy_now_button'));
    // Handle the Buy Now button click.
    add_action('wp_ajax_one_click_checkout', array($this, 'handle_one_click_checkout'));
    // Restore the original cart after a successful purchase.
    add_action('woocommerce_thankyou', array($this, 'restore_original_cart'));
  }

  /**
   * Check if WooCommerce is active, hook in the rest of your plugin's code if it is.
   */
  public function check_woocommerce_active()
  {
    if (class_exists('WooCommerce')) {
      $this->add_settings_tab();
    }
  }

  /**
   * Register the stylesheets for the public-facing side of the site.
   */
  public function enqueue_styles()
  {
    // wp_enqueue_style( ... );
  }

  /**
   * Register the JavaScript for the public-facing side of the site.
   */
  public function enqueue_scripts()
  {
    wp_enqueue_script(
      'one-click-checkout-script',
      plugin_dir_url(dirname(__FILE__)) . 'assets/js/one-click-checkout.js',
      array(),
      '1.0.0',
      true
    );

    wp_localize_script(
      'one-click-checkout-script',
      'oneClickCheckoutParams',
      array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('one_click_checkout_nonce'),
        // You can pass other parameters here as needed
      )
    );
  }

  public function add_custom_css()
  {
    $custom_css = get_option('one_click_checkout_custom_css');
    if (!empty($custom_css)) {
      echo '<style type="text/css">' . $custom_css . '</style>';
    }
  }

  /**
   * Adds settings tab to WooCommerce settings.
   */
  public function add_settings_tab()
  {
    add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab_array'), 50);
    add_action('woocommerce_settings_tabs_one_click_checkout', array($this, 'settings_tab_content'));
  }


  /**
   * Adds new tab to the WooCommerce settings tabs.
   *
   * @param array $settings_tabs Existing tabs.
   * @return array Modified tabs with your custom tab added.
   */
  public function add_settings_tab_array($settings_tabs)
  {
    // Set the title of your tab here
    $settings_tabs['one_click_checkout'] = __('One-Click Checkout', 'one-click-checkout');
    return $settings_tabs;
  }


  /**
   * Outputs the settings tab content.
   */
  public function settings_tab_content()
  {
    woocommerce_admin_fields($this->get_settings());
  }

  /**
   * Retrieves the settings for the plugin.
   *
   * @return array The settings array.
   */
  private function get_settings()
  {
    $settings = array(
      'section_title' => array(
        'name'     => __('One-Click Checkout Settings', 'one-click-checkout'),
        'type'     => 'title',
        'desc'     => '',
        'id'       => 'one_click_checkout_section_title'
      ),
      array(
        'name'     => __('Buy Now Button', 'one-click-checkout'),
        'type'     => 'checkbox',
        'desc'     => __('Enable the Buy Now button on product pages', 'one-click-checkout'),
        'id'       => 'one_click_checkout_activate_buy_now',
        'default'  => 'no',
      ),
      array(
        'title'    => __('Custom CSS for "Buy Now" Button', 'one-click-checkout'),
        'desc'     => __('Add your custom CSS styles for the "Buy Now" button.', 'one-one-click-checkout'),
        'id'       => 'one_click_checkout_custom_css',
        'type'     => 'textarea',
        'css'      => 'min-width:300px; height: 200px;', // Optional: CSS styling for the textarea
        'placeholder'  => "#buy-now {\n       /* " . __('Custom styles here', 'one-click-checkout') . " */\n}",
      ),
      'section_end' => array(
        'type'     => 'sectionend',
        'id'       => 'one_click_checkout_section_end'
      )
    );

    return apply_filters('one_click_checkout_settings', $settings);
  }


  /**
   * Updates the settings from the settings page.
   */
  public function update_settings()
  {
    woocommerce_update_options($this->get_settings());
  }

  /**
   * Saves user data for one-click checkout after a successful purchase.
   *
   * @param int $order_id The ID of the completed order.
   */
  public function save_checkout_data($order_id)
  {
    $order = wc_get_order($order_id);

    // Check if order is valid
    if (!$order) {
      return;
    }

    $user_id = $order->get_user_id();

    // Save billing information
    update_user_meta($user_id, 'one_click_checkout_billing_address', array(
      'first_name' => $order->get_billing_first_name(),
      'last_name' => $order->get_billing_last_name(),
      'company' => $order->get_billing_company(),
      'address_1' => $order->get_billing_address_1(),
      'address_2' => $order->get_billing_address_2(),
      'city' => $order->get_billing_city(),
      'state' => $order->get_billing_state(),
      'postcode' => $order->get_billing_postcode(),
      'country' => $order->get_billing_country(),
      'email' => $order->get_billing_email(),
      'phone' => $order->get_billing_phone()
    ));

    // Save shipping information
    update_user_meta($user_id, 'one_click_checkout_shipping_address', array(
      'first_name' => $order->get_shipping_first_name(),
      'last_name' => $order->get_shipping_last_name(),
      'company' => $order->get_shipping_company(),
      'address_1' => $order->get_shipping_address_1(),
      'address_2' => $order->get_shipping_address_2(),
      'city' => $order->get_shipping_city(),
      'state' => $order->get_shipping_state(),
      'postcode' => $order->get_shipping_postcode(),
      'country' => $order->get_shipping_country()
    ));

    // Save selected shipping method
    $shipping_methods = $order->get_shipping_methods();


    if (!empty($shipping_methods)) {
      $shipping_method_ids = array();
      foreach ($shipping_methods as $item_id => $shipping_method) {
        $shipping_method_ids[] = $shipping_method->get_method_id();
      }
      update_user_meta($user_id, 'one_click_checkout_shipping_methods', $shipping_method_ids);
    }

    // Note: Payment method tokenization should be handled by your payment gateway.
    // Save the selected payment method
    $payment_method = $order->get_payment_method();
    if ($payment_method) {
      update_user_meta($user_id, 'one_click_checkout_payment_method', $payment_method);
    }
  }

  /**
   * Display the Buy Now button on the product page.
   *
   * This function checks if the logged-in user has the necessary data saved for a one-click checkout,
   * including billing information, shipping information, and a preferred payment method.
   * If all the required data is available, it displays a Buy Now button next to the Add to Cart button.
   */
  public function display_buy_now_button()
  {
    // Ensure the user is logged in
    if (!is_user_logged_in()) {
      return; // User is not logged in, so the Buy Now button is not applicable
    }

    // Check if the Buy Now button is enabled in settings
    if ('yes' !== get_option('one_click_checkout_activate_buy_now', 'no')) {
      return; // The Buy Now button is not enabled, so don't display it
    }

    $user_id = get_current_user_id();

    // Retrieve saved user data
    $billing_data = get_user_meta($user_id, 'one_click_checkout_billing_address', true);
    $shipping_data = get_user_meta($user_id, 'one_click_checkout_shipping_address', true);
    $preffered_shipping_methods = get_user_meta($user_id, 'one_click_checkout_shipping_methods', true);

    $preferred_payment_method = get_user_meta($user_id, 'one_click_checkout_payment_method', true);

    // Check if the necessary data is available and complete
    if (empty($billing_data) || empty($shipping_data) || empty($preffered_shipping_methods)) {
      return; // Required data is not complete, don't show the Buy Now button
    }

    global $product;
    $product_id = $product->get_id();

    // Display the Buy Now button
    echo '<button id="buy-now" data-product-id="' . esc_attr($product_id) . '" class="single_add_buy_now_button button">Buy Now</button>';
  }

  /**
   * Handle the Buy Now button click.
   *
   * This function is called when the Buy Now button is clicked.
   * It checks the nonce, retrieves the product ID and quantity from the request,
   */
  // public function handle_one_click_checkout()
  // {
  //   // Check the nonce
  //   if (!check_ajax_referer('one_click_checkout_nonce', 'nonce')) {
  //     wp_die('Nonce verification failed!');
  //   }

  //   $product_id = intval($_POST['product_id']);
  //   $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
  //   $user_id = get_current_user_id();

  //   // Retrieve saved user data
  //   $preferred_payment_method = get_user_meta($user_id, 'one_click_checkout_payment_method', true);
  //   $preffered_shipping_methods = get_user_meta($user_id, 'one_click_checkout_shipping_methods', true);

  //   // Create a new order
  //   $order = wc_create_order();
  //   $order->add_product(wc_get_product($product_id), $quantity);

  //   // Set billing and shipping from user meta
  //   $billing_data = get_user_meta($user_id, 'one_click_checkout_billing_address', true);
  //   $shipping_data = get_user_meta($user_id, 'one_click_checkout_shipping_address', true);

  //   $order->set_address($billing_data, 'billing');
  //   $order->set_address($shipping_data, 'shipping');

  //   $order->calculate_totals();

  //   // Return the order pay URL
  //   wp_send_json_success(['checkout_url' => $order->get_checkout_payment_url()]);

  //   // Proceed with checkout logic
  //   // Validate product, retrieve user data, create an order, process payment
  //   // Return a response

  //   wp_die();
  // }

  /**
   * Handle the Buy Now button click.
   *
   * This function is called when the Buy Now button is clicked.
   * It checks the nonce, retrieves the product ID and quantity from the request,
   */
  public function handle_one_click_checkout()
  {

    // Check the nonce
    if (!check_ajax_referer('one_click_checkout_nonce', 'nonce')) {
      wp_die('Nonce verification failed!');
    }

    $user_id = get_current_user_id();

    // Retrieve the product ID and quantity
    $product_id = $_POST['product_id'];
    $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

    // Store current cart items
    $cart_contents = WC()->cart->get_cart_contents();
    WC()->session->set('saved_cart_contents', $cart_contents);

    // Empty the current cart
    WC()->cart->empty_cart(false);

    // Add the 'Buy Now' product to the cart
    WC()->cart->add_to_cart($product_id, $quantity);

    // Apply stored shipping method
    $preferred_shipping_method = get_user_meta($user_id, 'preferred_shipping_method', true);
    if ($preferred_shipping_method) {
      WC()->session->set('chosen_shipping_methods', array($preferred_shipping_method));
    }

    // Return the checkout URL
    wp_send_json_success(['checkout_url' => wc_get_checkout_url()]);
  }

  /**
   * Restore the original cart after a successful purchase.
   *
   * @param int $order_id The ID of the completed order.
   */
  function restore_original_cart($order_id)
  {
    $saved_cart_contents = WC()->session->get('saved_cart_contents');
    if ($saved_cart_contents) {
      foreach ($saved_cart_contents as $item_key => $item) {
        WC()->cart->add_to_cart($item['product_id'], $item['quantity'], $item['variation_id'], $item['variation']);
      }
      WC()->session->__unset('saved_cart_contents');
    }
  }
}
