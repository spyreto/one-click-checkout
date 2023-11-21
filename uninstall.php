<?php
// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

// Security check: Ensure that the uninstall process is initiated by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
  die;
}

/**
 * Custom function to clean up user meta data related to the plugin
 */
function one_click_checkout_cleanup_user_meta()
{
  global $wpdb;

  // Delete user meta related to One-Click Checkout
  $wpdb->query("DELETE FROM $wpdb->usermeta WHERE meta_key LIKE 'one_click_checkout_%'");
}

/**
 * Custom function to clean up options related to the plugin
 */
function one_click_checkout_cleanup_options()
{
  delete_option('one_click_checkout_activate_buy_now');
  delete_option('one_click_checkout_custom_css');
  // Add other options as needed
}

// Call the cleanup functions
one_click_checkout_cleanup_user_meta();
one_click_checkout_cleanup_options();
