<?php

/**
 * Plugin Name: One-Click Checkout
 * Plugin URI: https://github.com/spyreto
 * Description: A plugin to add a one-click checkout button to your WooCommerce store.
 * Version: 1.0
 * Author: Spiros Dimou
 * Author URI: https://www.linkedin.com/in/spiridon-dimou/
 * License: GPL2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

/**
 * The core plugin class.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-one-click-checkout.php';

/**
 * Function to check plugin requirements on activation.
 */
function one_click_check_activation()
{
  if (!class_exists('WooCommerce')) {
    wp_die('This plugin requires WooCommerce to be installed and active.', 'Plugin dependency check', array('back_link' => true));
  }

  // Further activation code can go here.
}

register_activation_hook(__FILE__, 'one_click_checkout_activation');

/**
 * Begins execution of the plugin.
 */
function run_one_click_checkout()
{
  $plugin = new One_Click_Checkout();
  $plugin->run();
}



/**
 * Add settings link on plugin page
 * 
 */
function one_click_checkout_add_settings_link($links)
{
  $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=one_click_checkout') . '">' . __('Settings', 'one-click-checkout') . '</a>';
  array_push($links, $settings_link);;
  return $links;
}

// Add settings link on plugin page
add_filter('plugin_action_links_one-click-checkout/one-click-checkout.php', 'one_click_checkout_add_settings_link');


run_one_click_checkout();
