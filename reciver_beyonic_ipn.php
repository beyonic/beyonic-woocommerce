<?php

//error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once( $parse_uri[0] . 'wp-load.php');
require_once('vendor/beyonic/beyonic-php/lib/Beyonic.php');

global $woocommerce;
$responce = $_REQUEST;
if (!empty($responce)) {
    $data = $responce['data'];
    $hook = $responce['hook'];
    $finalData = stripslashes($data);
    $finalHook = stripslashes($hook);
    $data_array = json_decode($finalData);
    $hook_array = json_decode($finalHook);
    $event = $hook_array->event;
    if ($event == 'collection.recieved') {
        $order_id = $data_array->metadata->order_id;
        $state = $data_array->state;
        $order = new WC_Order($order_id);
        if ($state == "completed") {
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









