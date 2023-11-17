<?php

/**
 * Plugin Name: One-Click Checkout
 * Plugin URI: https://github.com/spyreto
 * Description: A plugin to add a one-click checkout button to your WooCommerce store.
 * Version: 1.0
 * Author: Spiros Dimou
 * Author URI: https://github.com/spyreto
 * License: GPL2
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

/**
 * The core plugin class.
 */
require_once plugin_dir_path(__FILE__) . 'includes/class-one-click-check.php';

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

register_activation_hook(__FILE__, 'one_click_check_activation');

/**
 * Begins execution of the plugin.
 */
function run_one_click_checkout()
{
  $plugin = new One_Click_Checkout();
  $plugin->run();
}

run_one_click_checkout();
