<?php

//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once( $parse_uri[0] . 'wp-load.php');
require_once('vendor/beyonic/beyonic-php/lib/Beyonic.php');

global $woocommerce;
$responce = json_decode(file_get_contents("php://input"));
if (!empty($responce)) {
    $data = $responce->data;
    $hook = $responce->hook;
    $event = $hook->event;
    if ($event == 'collection.received') {
        //get order id from collection request
        $wc_beyonic = new WC_Gateway_Beyonic();
        $wc_beyonic->authorize_beyonic();
        $collection_request = Beyonic_Collection_Request::get($data->collection_request);
        $order_id = $collection_request->metadata->order_id;
        $status = $data->status;
        $order = new WC_Order($order_id);
        if ($status == "successful") {
            global $woocommerce;
            $order->update_status('processing');
        } else {
            $order->update_status('cancelled');
        }
    }
}


function pr($data) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
}









