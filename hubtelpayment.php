<?php
/*
Plugin Name: Hubtel Payment Gateway
Plugin URI: http://www.nanakwamezoe.com/
Description: Hubtel Payment Plugin.
Version: 1.0
Author: Nana Kwame Zoe
Author URI: http://www.nanakwamezoe.com/
*/


// Include our Gateway Class and register Payment Gateway with WooCommerce
add_action( 'plugins_loaded', 'hubtel_hub_payment_init', 0 );
function hubtel_hub_payment_init() {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;
	
	// If we made it this far, then include our Gateway Class
	include_once( 'hubtelsetup.php' );

	// Now that we have successfully included our class,
	// Lets add it too WooCommerce
	add_filter( 'woocommerce_payment_gateways', 'hubtel_payment_gateway' );
	function hubtel_payment_gateway( $methods ) {
		$methods[] = 'Hubtel_Payment_Gateway';
		return $methods;
	}
}

// Add custom action links
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'hubtel_payment_action_links' );
function hubtel_payment_action_links( $links ) {
	$plugin_links = array(
		'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">' . __( 'Settings', 'spyr-authorizenet-aim' ) . '</a>',
	);

	// Merge our new link with the default ones
	return array_merge( $plugin_links, $links );	
}




