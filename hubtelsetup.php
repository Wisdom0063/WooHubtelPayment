<?php
if (!defined('ABSPATH'))
exit("No script kiddies");


/* Hubtel Payment Gateway Class */
class Hubtel_Payment_Gateway extends WC_Payment_Gateway {

	// Setup our Gateway's id, description and other values
	function __construct() {

		// The global ID for this Payment method
		$this->id = "hubtel_payment";

		// The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
		$this->method_title = __( "Hubtel Payment Gateway", 'hubtel-payment' );

		// The description for this Payment Gateway, shown on the actual Payment options page on the backend
		$this->method_description = __( "Integrate credit card, debit card and mobile money payment into your Woocommerce site", 'hubtel-payment' );

		// The title to be used for the vertical tabs that can be ordered top to bottom
		$this->title = __( "Hubtel Payment Gateway", 'hubtel-payment' );

		// If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
		$this->icon = plugin_dir_url(__FILE__) . "assets/images/logo.png";

         $this->has_fields = false;

         $this->posturl = 'https://api.hubtel.com/v1/merchantaccount/onlinecheckout/invoice/create';
         $this->geturl = 'https://api.hubtel.com/v1/merchantaccount/onlinecheckout/invoice/status';


		// This basically defines your settings which are then loaded with init_settings()
		$this->init_form_fields();

		// After init_settings() is called, you can get the settings and load them into variables, e.g:
		// $this->title = $this->get_option( 'title' );
		$this->init_settings();
		
		// Turn these settings into variables we can use
		foreach ( $this->settings as $setting_key => $value ) {
			$this->$setting_key = $value;
        }
     
      
        if (isset($_REQUEST["token"])) {
            $token = trim($_REQUEST["token"]);
            if (!class_exists('WC_HubtelResponse')){
                require plugin_dir_path(__FILE__) . 'hubtel-class-response.php';
            }

            $resp_obj = new WC_HubtelResponse($this);
            $resp_obj->get_response($token);
        }
        if (isset($_REQUEST['hubtel_payment_status'])) {
            wc_add_notice('Hubtel Payment Cancelled or Payment Failed', "error");
        }

    
       // $results = $wpdb->get_results( "select post_id from $wpdb->postmeta where meta_value = '$token' and meta_key = 'HubtelToken'", ARRAY_A );
      //   echo "<script>console.log(" . $results . " );</script>";
        
        //else {
         //   wc_add_notice('Payment Cancelled', "error");
      //  }

        if (isset($_REQUEST["hubtel"])) {
            wc_add_notice($_REQUEST["hubtel"], "error");
        }
		
		// Lets check for SSL
       /// add_action( 'admin_notices', array( $this,	'do_ssl_check' ) );
        
        ////add notice////
        add_action( 'admin_notices', array( $this,	'check_client_id' ) );
		
		// Save settings
        if (version_compare(WOOCOMMERCE_VERSION, '2.0.0', '>=')) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array(&$this, 'process_admin_options'));
        } else {
            add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
        }		
    } // End __construct()
    

	// Check if we are forcing SSL on checkout pages
	// Custom function not required by the Gateway
	public function do_ssl_check() {
		if( $this->enabled == "yes" ) {
			if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
				echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";	
			}
		}		
    }
    
    public function check_client_id(){
        if( $this->enabled == "yes" ) {
        $clientid = $this->get_option( 'clientid' );
        if ($this->clientid == '' || $this->secret == '') {
            echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled. Please enter <strong> client</strong>  and <strong>Secret</strong> to Continue. <a href=\"%s\">Go to settings</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout&section='. $this->id ) ) ."</p></div>";
        }
    }
        
    }

    public function admin_options() {
        #Generate the HTML For the settings form.
        echo '<h3>' . __('Hubtel Payment Gateway', 'hubtel_payment_gateway') . '</h3>';
        echo '<p>' . __('Hubtel Payment is most popular payment gateway for online shopping in Ghana.') . '</p>';
        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
    }

	
	// Submit payment and handle response
	public function process_payment( $order_id ) {
		global $woocommerce;
        if (!class_exists('WC_Hubte_Payment_Utility'))
        require plugin_dir_path(__FILE__) . 'hubtel-class-utility.php';
        $url = "";
        $order = new WC_Order($order_id);
        $credential =  'Basic ' . base64_encode($this->clientid . ':' . $this->secret);
        $response = WC_Hubte_Payment_Utility::post_to_url($this->posturl, $credential, $this->get_payment_args($order));
       // echo "<script>console.log(" . $response . " );</script>";
        if($response){
            $response_decoded = json_decode($response);
            if (isset($response_decoded->response_code) && $response_decoded->response_code == "00") {
                update_post_meta($order_id, "HubtelToken", $response_decoded->token);
                $url = $response_decoded->response_text;
            } else {
                global $woocommerce;
                $url = $woocommerce->cart->get_checkout_url();
                $err_msg = isset($response_decoded->response_text) ? $response_decoded->response_text : "Request could not be completed";
                if (strstr($url, "?")) {
                    $url .= "&hubtel=" . $err_msg;
                } else {
                    $url .= "?hubtel=" . $err_msg;
                }
            }
        }else{
            $url .= "?hubtel=Request could not be completed. Please try again later.";
        }
        return array(
            'result' => 'success',
            'redirect' => $url
        );
    }
    

    protected function get_payment_args($order) {
        global $woocommerce;

        $txnid = $order->id . '_' . date("ymds");
        $redirect_url = $woocommerce->cart->get_checkout_url();
        $productinfo = "Order: " . $order->id;
        $str = "$txnid|$order->order_total|$productinfo|$order->billing_first_name|$order->billing_email";
        $hash = hash('sha512', $str);
        if( strpos( $redirect_url, '?') !== false ) {
            $redirect_url .= '&';
        } else {
            $redirect_url .= '?';
        }

        WC()->session->set('hubtel_wc_hash_key', $hash);
        $items = $woocommerce->cart->get_cart();
        $hubtel_items = array();
        $item_index = 0;
        $currency = get_option('woocommerce_currency', 'GHS');
        foreach ($items as $item) {
            $hubtel_items["item_" . $item_index] = array(
                "name" => $item["data"]->post->post_title,
                "quantity" => $item["quantity"],
                "unit_price" => $item["line_total"] / (($item["quantity"] == 0) ? 1 : $item["quantity"]),
                "total_price" => $item["line_total"],
                "description" => ""
            );
            $item_index++;
        }

        $order_shipping_total = $order->get_total_shipping();
        if($order_shipping_total > 0){
            $item_index +=1;
            $hubtel_items["item_" . $item_index] = array(
                "name" => "Shipping fee",
                "quantity" => "1",
                "unit_price" => $order_shipping_total,
                "total_price" => $order_shipping_total,
                "description" => ""
            );
        }
       $cancel_url = $redirect_url . '?hubtel_payment_status=cancelled&order_id='.  $order->id;
        $hubtelpayment_args = array(
            "invoice" => array(
                "items" => $hubtel_items,
                "total_amount" => $order->order_total,
                "description" => "Payment of GHs" . $order->order_total . " for item(s) bought on " . get_bloginfo("name")
            ), "store" => array(
                "name" => get_bloginfo("name"),
                "website_url" => get_site_url()
            ), "actions" => array(
                "cancel_url" => $cancel_url,
                "return_url" => $redirect_url
            ), "custom_data" => array(
                "order_id" => $order->id,
                "trans_id" => $txnid,
                "hash" => $hash
            )
        );

      //  apply_filters('woocommerce_hubtelpayment_args', $hubtelpayment_args, $order);
        return $hubtelpayment_args;
    }
	
	// Validate fields
	public function validate_fields() {
		return true;
	}
    
    
    	// Build the administration fields for this specific Gateway
  public  function init_form_fields() {
    $this->form_fields  = array(
        'enabled' => array(
            'title' => __('Enable/Disable', 'hubtel_payment_gateway'),
            'type' => 'checkbox',
            'label' => __('Enable Hubtel Payment Gateway.', 'hubtel_payment_gateway'),
            'default' => 'no'),
        'title' => array(
            'title' => __('Title', 'hubtel_payment_gateway'),
            'type' => 'text',
            'description' => __('This controls the title which the user sees during checkout.', 'hubtel_payment_gateway'),
            'default' => __('Hubtel Payment', 'hubtel_payment_gateway')
        ),
        'description' => array(
            'title' => __('Description', 'hubtel_payment_gateway'),
            'type' => 'textarea',
            'description' => __('This controls the description which the user sees during checkout.','hubtel_payment_gateway'),
            'default' => __('Integrate credit card, debit card and mobile money payment into your Woocommerce site.', 'hubtel_payment_gateway')
        ),
        'clientid' => array(
            'title' => __('Client Id', 'hubtel_payment_gateway'),
            'type' => 'text',
            'description' => __('', 'hubtel_payment_gateway'),
            'default' => __('', 'hubtel_payment_gateway')
        ),
        'secret' => array(
            'title' => __('Secret', 'hubtel_payment_gateway'),
            'type' => 'text',
            'description' => __('', 'hubtel_payment_gateway'),
            'default' => __('', 'hubtel_payment_gateway')
        ),
    );
}


} 