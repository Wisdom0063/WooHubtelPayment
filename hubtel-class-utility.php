<?php

if (!defined('ABSPATH'))
	exit("No script kiddies");

class WC_Hubte_Payment_Utility{

	static function post_to_url($url, $credential, $data = false) {
        if($data){
	        if (version_compare(PHP_VERSION, '5.4.0') >= 0)
		        $json = json_encode($data, JSON_UNESCAPED_SLASHES);
	        else
		        $json = str_replace('\\/', '/', json_encode($data));
        }

        $response = wp_remote_post($url, array(
            'method' => isset($json) ? 'POST' : 'GET',
            'headers' => array(
                "Authorization" => $credential,
                "Cache-Control" => "no-cache",
                "Content-Type" => "application/json"
            ),
            'body' => isset($json) ? $json : ''
            )
        );

        if (!is_wp_error($response)) {
            $r = wp_remote_retrieve_body($response);
            // echo "<script>console.log(" . $r . " );</script>";
            return $r;
        }
        return false;
    }

}