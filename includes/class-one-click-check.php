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
    // Save user data for one-click checkout after a successful purchase.
    add_action('woocommerce_order_status_completed', array($this, 'save_checkout_data'));
    // Display the Buy Now button on the product page.
    add_action('woocommerce_after_add_to_cart_button', array($this, 'display_buy_now_button'));
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
    // wp_enqueue_script( ... );
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
      'activate_buy_now' => array(
        'name'     => __('Buy Now Button', 'one-click-checkout'),
        'type'     => 'checkbox',
        'desc'     => __('Enable the Buy Now button on product pages', 'one-click-checkout'),
        'id'       => 'one_click_checkout_activate_buy_now',
        'default'  => 'no',
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
    $preferred_payment_method = get_user_meta($user_id, 'one_click_checkout_payment_method', true);

    // Check if the necessary data is available and complete
    if (empty($billing_data) || empty($shipping_data) || empty($preferred_payment_method)) {
      return; // Required data is not complete, don't show the Buy Now button
    }

    // Display the Buy Now button
    echo '<button id="buy-now" class="button checkout-button">Buy Now</button>';
  }
}
