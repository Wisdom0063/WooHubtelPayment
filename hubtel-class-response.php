<?php

if (!defined('ABSPATH'))
	exit("No script kiddies");

class WC_HubtelResponse {
	var $token = null;
	var $url = null;
	var $order = null;
	var $credential = null;
	var $geturl = null;
    var $return_url = null;
    var $emails_addresses = null;
    var $payment_session = null;
    var $payment_failed = 'Thank you for shopping with us. However, the transaction could not be completed.';
    var $payment_successful = 'Thank you for shopping with us, your payment was successful. You order is currently being processed. Your Order id is';
	
	function __construct($parent = false){
		if($parent) {
			$parent->init_settings();
            $this->credential         = 'Basic ' . base64_encode( $parent->clientid . ':' . $parent->secret );
            
			$this->geturl             = $parent->geturl;
			$this->return_url         = $parent->get_return_url();
		}
	} 

	function get_response($token, $orderid = false){
		global $woocommerce;
        try {
            if (!class_exists('WC_Hubte_Payment_Utility')){
                require plugin_dir_path(__FILE__) . 'hubtel-class-utility.php';
            }


            $this->geturl =  $this->geturl .  '/' . $token;
            $response = WC_Hubte_Payment_Utility::post_to_url($this->geturl, $this->credential);
            if(!$response){
                echo "Payment could not be completed. Your token is: " . $token;
                exit;
            }
            $response_decoded = json_decode($response);
            $respond_code = $response_decoded->response_code;

            $custom_data = $response_decoded->custom_data;
            $wc_order_id = $custom_data->order_id;
            $order = new WC_Order($wc_order_id);
            
	        
            if ($respond_code == "00") {
                #payment found
            	$status = $response_decoded->status;
                if(!$order){
                    echo "Payment could not be completed. Your token is: " . $token;
                    exit;
                }
                if ($status == "completed") {
               		#payment was successful
                    $total_amount = strip_tags($woocommerce->cart->get_cart_total());
                    $message = $this->payment_successful . " " . $orderid;
                    $message_type = "success";

                    $order->payment_complete();
                    $order->update_status("completed");
                    $order->add_order_note("Hubtel payment successful");
                    $woocommerce->cart->empty_cart();

	                $redirect_url = $this->return_url.$wc_order_id.'/?key='.$order->order_key;

                    $customer = trim($order->get_billing_last_name() . " " . $order->get_billing_first_name());

	                $website = get_site_url();

	            
                } else {
                    #payment is still pending, or user cancelled request
                    $message = $this->payment_failed;
                    $message_type = "notice";
                    $order->update_status("failed");
                    $order->add_order_note($message);
                    $redirect_url = $order->get_cancel_order_url();
                }
            }else {
                #payment is still pending, or user cancelled request
                $message = $this->payment_failed;
                $message_type = "notice";
                $order->add_order_note($message);
                $redirect_url = $order->get_cancel_order_url();
            }

        	#destroy session
        	WC()->session->__unset('hubtel_wc_hash_key');
            WC()->session->__unset('hubtel_wc_order_id');

        	wp_redirect($redirect_url);
            exit;
		}catch (Exception $e) {
        	$order->add_order_note('Error: ' . $e->getMessage());
            $redirect_url = $order->get_cancel_order_url();
            wp_redirect($redirect_url);
            exit;
		}

        $this->token = $token;
    }
    

    public function get_payment_response($token) {
		if (!class_exists('WC_Hubte_Payment_Utility')){
            require plugin_dir_path(__FILE__) . 'hubtel-class-utility.php';
        }
		$endpoint = get_option("hubtel_response_endpoint", "") . $token;
		$credential = "Basic " . base64_encode(get_option("clientid", "") . ':' . get_option("secret", ""));
		$response = WC_Hubte_Payment_Utility::post_to_url($endpoint, $credential);
		$status = "pending";
		if ( $response ) {
			$response_decoded = json_decode( $response );
			$respond_code     = $response_decoded->response_code;
			if ( $respond_code == "00" ) {
				$status = $response_decoded->status;
			}
		}
		return $status;
	}

}
