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
    add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

    add_action('admin_init', array($this, 'check_woocommerce_active'));
  }

  /**
   * Register all of the hooks related to the plugin functionality.
   */
  public function run()
  {
    add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab_array'), 50);
    add_action('woocommerce_settings_tabs_one_click_checkout', array($this, 'settings_tab_content'));
    add_action('woocommerce_update_options_one_click_checkout', array($this, 'update_settings'));
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
    add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab_array'), 20);
    add_action('woocommerce_settings_tabs_one_click_checkout', array($this, 'settings_tab_content'));
    add_action('woocommerce_update_options_one_click_checkout', array($this, 'update_settings'));
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
    $settings_tabs['one_click_checkout'] = __('One Click Checkout', 'one-click-check');
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
        'name'     => __('One Click Checkout Settings', 'one-click-check'),
        'type'     => 'title',
        'desc'     => '',
        'id'       => 'one_click_checkout_section_title'
      ),
      'activate_buy_now' => array(
        'name'     => __('Buy Now Button', 'one-click-check'),
        'type'     => 'checkbox',
        'desc'     => __('Enable the Buy Now button on product pages', 'one-click-check'),
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
}
