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
    add_action('woocommerce_thankyou', array($this, 'save_checkout_data'));
    // Display the Buy Now button on the product page.
    add_action('woocommerce_after_add_to_cart_button', array($this, 'display_buy_now_button'));
    // Handle the Buy Now button click.
    add_action('wp_ajax_fetch_checkout_form', array($this, 'fetch_checkout_form'));
    add_action('wp_ajax_update_shipping_address', array($this, 'update_shipping_address'));
    add_action('wp_ajax_handle_modal_checkout', array($this, 'handle_modal_checkout'));
    // Load the modal template.
    add_action('wp_footer', array($this, 'conditionally_load_modal'));
    // Restore the original cart after a successful purchase.
    add_action('woocommerce_thankyou', 'restore_cart_after_order_complete', 10, 1);
    // Customize the thank you page for the modal.
    add_action('template_redirect', 'customize_thank_you_page_for_modal');
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
    wp_enqueue_style('one-click-checkout-style',  plugin_dir_url(dirname(__FILE__)) . 'assets/css/custom-style.css');
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
        // Pass other parameters...
      )
    );

    // wp_enqueue_script('woocommerce');
    // wp_enqueue_script('wc-cart');
    wp_enqueue_script('wc-checkout');
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
   * Displays the current user's shipping address in the checkout modal.
   *
   * This function fetches  user's shipping address from their WooCommerce profile
   * and formats it for display. It is intended to be called when rendering the checkout form
   * within a modal, showing a concise view of the shipping address with an option to change it.
   */
  function display_shipping_address()
  {
    $user_id = get_current_user_id();
    // Fetching user shipping details
    $customer = WC()->customer;
    $shipping_address = array(
      'first_name' => $customer->get_shipping_first_name(),
      'last_name'  => $customer->get_shipping_last_name(),
      'company'    => $customer->get_shipping_company(),
      'address_1'  => $customer->get_shipping_address_1(),
      'address_2'  => $customer->get_shipping_address_2(),
      'city'       => $customer->get_shipping_city(),
      'state'      => $customer->get_shipping_state(),
      'postcode'   => $customer->get_shipping_postcode(),
      'country'    => $customer->get_shipping_country()
    );
    // Format the address
    $formatted_address = WC()->countries->get_formatted_address($shipping_address);

    // Display the formatted address
    echo '<div class="woocommerce-billing-fields__field-wrapper one-click-checkout-shipping-address"">';
    echo '<h3>' . __('Shipping Details:', 'one-click-checkout') . '</h3>';
    echo '<address>' . $formatted_address . '</address>';
    echo '<a href="#" class="one-click-checkout-change-shipping-details">' . __('Change shipping details', 'one-click-checkout') . '</a>';
    echo '<div id="one-click-checkout-shipping-address-form"  style="display:none;">';

    // Change shipping address form
    // Address line 1
    echo '<p class="form-row form-row-wide">';
    echo '<label for="shipping_address_1">' . esc_html__('Address', 'woocommerce') . '</label>';
    echo '<input type="text" id="one_click_checkout_shipping_address_1" name="shipping_address_1" class="input-text" value="' . esc_attr($shipping_address['address_1']) . '">';
    echo '</p>';

    // City
    echo '<p class="form-row form-row-wide">';
    echo '<label for="shipping_city">' . esc_html__('City', 'woocommerce') . '</label>';
    echo '<input type="text" id="one_click_checkout_shipping_city" name="shipping_city" class="input-text" value="' . esc_attr($shipping_address['city']) . '">';
    echo '</p>';

    // State
    echo '<p class="form-row form-row-wide">';
    echo '<label for="shipping_state">' . esc_html__('State', 'woocommerce') . '</label>';
    echo '<select id="one_click_checkout_shipping_state" name="shipping_state" class="state_select">';
    $countries_obj = new WC_Countries();
    $states = $countries_obj->get_states($shipping_address['country']);
    foreach ($states as $state_code => $state_name) {
      $selected = ($state_code === $shipping_address['state']) ? ' selected="selected"' : '';
      echo '<option value="' . esc_attr($state_code) . '"' . $selected . '>' . esc_html($state_name) . '</option>';
    }
    echo '</select>';
    echo '</p>';

    // Postcode
    echo '<p class="form-row form-row-wide">';
    echo '<label for="shipping_postcode">' . esc_html__('Postcode / ZIP', 'woocommerce') . '</label>';
    echo '<input type="text" id="one_click_checkout_shipping_postcode" name="shipping_postcode" class="input-text" value="' . esc_attr($shipping_address['postcode']) . '">';
    echo '</p>';

    // Submit button
    echo '<p class="form-row">';
    echo '<button type="submit" class="button one-click-checkout-update-address-btn" disabled>' . esc_html__('Update Address', 'woocommerce') . '</button>';
    echo '</p>';
    echo '</div>';
    echo '</div>';
  }


  /**
   * Handle the Buy Now button click.
   * 
   * This function is called when the Buy Now button is clicked.
   * It checks the nonce, response with the checkout form HTML.
   */
  public function fetch_checkout_form()
  {
    // Verify the nonce
    if (!check_ajax_referer('one_click_checkout_nonce', 'nonce')) {
      wp_die('Nonce verification failed!');
    }
    // ob_start();

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

    // Add the shipping address to the checkout form
    add_action('woocommerce_checkout_after_customer_details',  array($this, 'display_shipping_address'), 20);

    // Load the checkout form
    echo do_shortcode('[woocommerce_checkout]');

    $checkout_form = ob_get_clean();

    echo $checkout_form;

    wp_die();
  }

  public function update_shipping_address()
  {
    // Verify the nonce
    if (!check_ajax_referer('one_click_checkout_nonce', 'nonce')) {
      wp_die('Nonce verification failed!');
    }

    if (is_checkout()) {
      error_log('is checkout');
    } else {
      error_log('is not checkout');
    }


    if (isset($_POST['address_data'])) {
      $address_data = json_decode(stripslashes($_POST['address_data']), true);

      $customer = WC()->customer;
      // 
      $shipping_country = $customer->get_shipping_country();

      // Sanitize and set the new shipping address
      $customer->set_shipping_address_1(sanitize_text_field($address_data['shippingAddress']));

      $customer->set_shipping_city(sanitize_text_field($address_data['shippingCity']));
      $customer->set_shipping_state(sanitize_text_field($address_data['shippingState']));

      $customer->set_shipping_postcode(sanitize_text_field($address_data['shippingPostcode']));

      $customer->save();

      // Recalculate shipping after address change
      WC()->cart->calculate_totals();

      // Get full state name
      $countries_obj = new WC_Countries();
      $states = $countries_obj->get_states($shipping_country);
      $shipping_state_name = isset($states[$address_data['shippingState']]) ? $states[$address_data['shippingState']] : $address_data['shippingState'];

      // Set state name instead of state code
      $address_data['shippingState'] = $shipping_state_name;

      add_action('woocommerce_checkout_after_customer_details',  array($this, 'display_shipping_address'), 20);

      ob_start();
      // Load the checkout form
      echo do_shortcode('[woocommerce_checkout]');
      $checkout_form = ob_get_clean();

      // Response data
      $response_data = [
        'shippingTotal' => WC()->cart->get_shipping_total(),
        'newShippingAddress' => $address_data,
        'checkoutHtml' => $checkout_form
      ];

      wp_send_json_success($response_data);
    } else {
      wp_send_json_error();
    }
  }

  /**
   * Handles the checkout form submition.
   *
   * This function is called after a successful purchase and returns the thank tou page.
   * It checks the nonce, processes the checkout, and returns the thank you page content.
   */
  public function handle_modal_checkout()
  {
    // Verify the nonce
    if (!check_ajax_referer('one_click_checkout_nonce', 'nonce')) {
      error_log('Nonce verification failed!');
      wp_die('Nonce verification failed!');
    }

    // Simulate the checkout process
    $checkout = WC_Checkout::instance();
    $order_id = $checkout->create_order($_POST);

    if (is_wp_error($order_id)) {
      // Handle error in order creation
      wp_send_json_error($order_id->get_error_message());
      return;
    }

    $order = wc_get_order($order_id);



    // Assuming the order requires payment
    if ($order->needs_payment()) {
      // Get the payment gateway redirect URL
      $payment_gateways = WC()->payment_gateways->payment_gateways();
      $payment_gateway = isset($payment_gateways[$order->get_payment_method()]) ? $payment_gateways[$order->get_payment_method()] : null;

      if ($payment_gateway) {
        // Manually process the payment
        $result = $payment_gateway->process_payment($order_id);

        if ($result['result'] === 'success') {
          // Send the redirect URL to the client
          wp_send_json_success(['redirect_url' => $result['redirect']]);
        } else {
          wp_send_json_error('Error processing payment.');
        }
      } else {
        // Capture 'thank you' page HTML
        ob_start();
        wc_get_template('checkout/thankyou.php', array('order' => $order));
        $thankyou_page_html = ob_get_clean();

        wp_send_json_success(['thankyou_page_html' => $thankyou_page_html]);
      }
    }
  }


  /**
   * Load the modal template.
   */
  public function load_modal_template()
  {
    include_once plugin_dir_path(__FILE__) . '../templates/modal-template.php';
  }


  /**
   * Conditionally load the modal template.
   */
  public function conditionally_load_modal()
  {
    if (is_product() || is_shop()) { // Adjust conditions as needed
      $this->load_modal_template();
    }
  }
}

/**
 * Customize the thank you page for the modal.
 */
function customize_thank_you_page_for_modal()
{
  if (is_wc_endpoint_url('order-received') && isset($_GET['from_modal'])) {
    // Enqueue a special CSS file or inline styles to hide unwanted elements
    wp_enqueue_style('custom-thank-you-modal-style', plugin_dir_url(dirname(__FILE__)) . 'assets/css/custom-thank-you-modal-style.css');
  }
}


/**
 * Restores the previously saved cart contents.
 *
 * This function retrieves the cart contents that were saved in the WooCommerce session
 * and restores them to the current cart. It's typically called after the checkout process
 * to restore the cart to its state before checkout began.
 */
function restore_saved_cart_contents()
{
  // Check if the WooCommerce session is initialized
  if (is_null(WC()->session)) {
    return;
  }

  // Retrieve the saved cart contents from the session
  $saved_cart_contents = WC()->session->get('saved_cart_contents');

  // Check if there are saved cart contents and they are in array format
  if ($saved_cart_contents && is_array($saved_cart_contents)) {
    // Optionally clear the current cart to avoid mixing old and new items
    WC()->cart->empty_cart();

    // Loop through the saved cart items and add them back to the cart
    foreach ($saved_cart_contents as $item_key => $item) {
      $product_id = $item['product_id'];
      $quantity = $item['quantity'];
      $variation_id = isset($item['variation_id']) ? $item['variation_id'] : '';
      $variation = isset($item['variation']) ? $item['variation'] : array();
      $cart_item_data = isset($item['cart_item_data']) ? $item['cart_item_data'] : array();

      WC()->cart->add_to_cart($product_id, $quantity, $variation_id, $variation, $cart_item_data);
    }

    // Clear the saved cart contents from the session to avoid duplication in future
    WC()->session->set('saved_cart_contents', null);
  }
}

/* 
 * Restore the original cart after a successful purchase.
 * 
 * This function is called after a successful purchase and restores the original cart contents.
 * It's intended to be hooked into the 'woocommerce_thankyou' action.
 */
function restore_cart_after_order_complete($order_id)
{
  // Optionally check additional conditions if necessary
  // Restore the original cart
  restore_saved_cart_contents();
}
