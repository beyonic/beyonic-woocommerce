<?php
/**
 * Plugin Name: WooCommerce Beyonic Gateway
 * Plugin URI: http://beyonic.com/
 * Description: Receive payments using the Beyonic.
 * Author: beyonic
 * Author URI: http://beyonic.com/
 * Version: 2.0.12
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

define('BEYONIC_WPSP_NAME', 'beyonic-payment-gateway');

add_action('init', 'beyonic_woo_gw_init');

require_once('vendor/beyonic/beyonic-php/lib/Beyonic.php');

register_deactivation_hook(__FILE__, 'beyonic_woo_gw_deactivate');

function beyonic_woo_gw_deactivate() {
    global $wpdb;
    $strQuery = "DELETE FROM {$wpdb->options} WHERE option_name= %s";
    $wpdb->query($wpdb->prepare($strQuery, "Beyonic_Webhook"));
}

add_action( 'woocommerce_thankyou_order_received_text', 'woocommerce_thankyou_cheque_payment', 10, 2 );

function woocommerce_thankyou_cheque_payment( $message,$order ){
    $payment_method = $order->get_payment_method();
	if($payment_method == 'beyonic'){
		$phone = $order->get_billing_phone();

		// SET your message below
		echo $message.'<br><br><p style="color:red; font-weight:bold;">'.__( 'Note: Payment instructions have been sent to your phone "'.$phone.'". Please check your phone to complete the payment. Your order cannot be delivered until you complete the payment on your phone.', 'woocommerce' ).'</p>';
	}
}

function beyonic_woo_gw_init() {

    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class is not available, do nothing
    if (class_exists('Beyonic_Woo_Gw'))
        return;

    class Beyonic_Woo_Gw extends WC_Payment_Gateway { 

        public $allowed_currency = array(
            'BXC',
            'KES',
            'UGX',
            'TZS',
            'RWF',
            'TEST',
        );

        public function __construct() { 
            $plugin_dir = plugin_dir_url(__FILE__);
            global $woocommerce;
            $this->id = 'beyonic';
            $this->has_fields = true;
            // Load the settings
            $this->init_form_fields();
            $this->init_settings();
            // Define user set variables
            $this->title = "Pay with Mobile Money"; 
            $this->api_key = $this->get_option('api_key');
            $this->description = $this->get_option('description');
            $this->beyonic_api_version = 'v1';
            $this->ipn_url = site_url() . "?beyonic_ipn=1";
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            add_action('admin_notices', array($this, 'beyonic_admin_notices'));

        }

        /**
         * Initialise Gateway Settings Form Fields
         *
         * @access public
         * @return void
         */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable the Beyonic gateway', 'woocommerce'),
                    'default' => 'yes'
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'textarea',
                    'description' => __('This description will be shown on your checkout page.', 'woocommerce'),
                    'default' => __('Use mobile money from MPESA, MTN, AIRTEL or other networks to pay for your order! <a href="https://beyonic.com" target="_blank">Powered by Beyonic Payments</a>.', 'using Beyonic', 'woocommerce'),  
                    'desc_tip' => true,
                ),
                'notification_url' => array(
                    'title' => __('Callback notification URL', 'woocommerce'),
                    'type' => 'text',  
                    'description' => __("This is the notification URL that will be used to send payment notifications to your website. You do not need to change it. NOTE : It must start with 'https'. If it doesn't start with https, then that means that your website doesen't have a secure HTTPS certificate.", 'woocommerce'),
                    'default' => get_site_url() . '?beyonic_ipn=1',
                    'desc_tip' => true,
                    'custom_attributes' => array('readonly' => 'readonly')
                ),
                'api_key' => array(
                    'title' => __('Api Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your api key (you can get it from your Beyonic Profile).', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'placeholder' => ''
                )
            );
        }

        /**
         * Admin Panel Options
         * - Options for bits like 'api keys' and availability on a country-by-country basis
         *
         * @since 1.0.0
         */
        public function admin_options() {
            $store_currency = get_option('woocommerce_currency');
            if (in_array($store_currency, $this->allowed_currency)) {
                ?>
                <h3><?php _e('Beyonic', 'woocommerce'); ?></h3>
                <p><?php _e('Please fill in the section below to start accepting payments on your site. You must first sign up for a Beyonic account at <a target="_blank" href = "https://app.beyonic.com/signup/" > https://beyonic.com/signup </a>. After that, you will find all the required information in your Beyonic Profile.'); ?> </p>
                <table class="form-table">
                    <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    ?>
                </table><!--/.form-table-->

                <?php
            } else {
                ?>
                <div class="inline error below-h2"><p><strong>Gateway Disabled</strong>: Beyonic does not support your store currency.</p></div>
                <?php
            }
        }

        function process_payment($order_id) {
         global $woocommerce, $wpdb;
         $order = new WC_Order($order_id);
         $this->authorize_beyonic_gw();

            // Phone number validation
         if (!preg_match('/^\+\d{6,12}$/', $order->get_billing_phone())) {     
            $notice = 'Please make sure your phone number is in international format, starting with a + sign';

            if (function_exists("wc_add_notice")) {
                    // Use the new version of the add_error method
                wc_add_notice($notice, 'error');
            } else {
                    // Use the old version
                $woocommerce->add_error($notice);
            }
            return;
        }

        $meta_key = 'Beyonic_Webhook';
       
        $webhook = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $meta_key));

        if (empty($webhook)) {

            $url = str_replace("http:", "https:", $this->ipn_url);   

            try {  
                $hooks = Beyonic_Webhook::create(array(
                    "event" => "collection.received",
                    "target" => "$url"
                ));

                $wpdb->insert($wpdb->options, array('option_name' => 'Beyonic_Webhook', 'option_value' => 'Collection_received'));

            } catch (Exception $exc) {
             
                $notice = json_decode($exc->responseBody);  

                if (function_exists("wc_add_notice")) {
                        // Use the new version of the add_error method
                    wc_add_notice($notice->detail, 'error');
                } else {
                        // Use the old version
                    $woocommerce->add_error($notice->detail); 
                }
                return;
            }
        }    

        try {

            $request = Beyonic_Collection_Request::create(array(
                "phonenumber" => $order->get_billing_phone(),
                "first_name" => $order->get_billing_first_name(),
                "last_name" => $order->get_billing_last_name(),
                "amount" => $order->get_total(),
                "success_message" => 'Thank you for your payment!',
                "send_instructions" => true,
                "currency" => $order->get_currency(),
                "metadata" => array("order_id" => $order_id)
            ));

            $beyonic_collection_id = intval($request->id);

            if (!empty($beyonic_collection_id)) { 
                $order->payment_complete($beyonic_collection_id);
            }

            $order->update_status('pending');
            return array(
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            );
        } catch (Exception $exc) { 

          $notice = json_decode($exc->responseBody);

                // If function should we use?
          if (function_exists("wc_add_notice")) {
                    // Use the new version of the add_error method
            wc_add_notice($notice->detail, 'error');
        } else { 
                    // Use the old version
            $woocommerce->add_error($notice->detail);   
        }
    }
}

        /**
         * Authorize beyonic gateway
         */
        function authorize_beyonic_gw() {
            Beyonic::setApiVersion($this->beyonic_api_version);
            Beyonic::setApiKey($this->api_key);
        } 

        /**
         * Generate payment form
         *
         * @access public
         * @param none
         * @return string
         */
        function payment_fields() {
            // Access the global object
            global $woocommerce;
            $plugin_dir = plugin_dir_url(__FILE__);
            // Description of payment method from settingsp
            if (!empty($this->description)) {
                echo "<p>" . $this->description . "</p>";
            }
        } 

        /**
         * Generate admin notice
         */
        public function beyonic_admin_notices() {
            ?>
            <div id="message" class="notice notice-error is-dismissible">
                <p>Https must be enabled to use beyonic payments. If you are testing, please see the testing section of the Beyonic api documentation at https://apidocs.beyonic.com for how to use test https certificates for instant payment notifications.</p>
            </div>
            <?php

            if(empty($this->api_key)) { ?>
            <div id="message" class="notice notice-warning is-dismissible">
                <p> Beyonic is almost ready. To get started, <a target="_blank" href = "https://app.beyonic.com/signup/"> set your Beyonic account key </a> </p>
            </div>
            <?php } 
        }

    }

    /**
     * Add the gateway to WooCommerce
     * */
    function add_beyonic_gw($methods) {
        $methods[] = 'Beyonic_Woo_Gw';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_beyonic_gw');
    
    if (!empty($_GET['beyonic_ipn']) && $_GET['beyonic_ipn'] == 1) {
       
        require_once __DIR__ .'/beyonic-ipn-receiver.php';    
        return;
    } 
}
