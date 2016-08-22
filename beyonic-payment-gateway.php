<?php
/**
 * Plugin Name: WooCommerce Beyonic Gateway
 * Plugin URI: http://techmarbles.com/
 * Description: Receive payments using the Beyonic.
 * Author: Manish gautam
 * Author URI: http://woothemes.com/
 * Version: 1.3.1
 *
 * 
 * 
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

add_action('plugins_loaded', 'beyonic', 0);
require_once('vendor/beyonic/beyonic-php/lib/Beyonic.php');

function beyonic() {
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class is not available, do nothing
    if (class_exists('WC_Gateway_Beyonic'))
        return;
    
    class WC_Gateway_Beyonic extends WC_Payment_Gateway {

        public function __construct() {
            $plugin_dir = plugin_dir_url(__FILE__);
            global $woocommerce;
            $this->id = 'beyonic';
            $this->has_fields = true;
            // Load the settings
            $this->init_form_fields();
            $this->init_settings();
            // Define user set variables
            $this->title = "Beyonic Payments";
            $this->test_api_key = $this->get_option('test_api_key');
            $this->live_api_key = $this->get_option('live_api_key');
            $this->description = $this->get_option('description');
            $this->test_mode = $this->get_option('test_mode');
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
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
                    'label' => __('Enable the Start gateway', 'woocommerce'),
                    'default' => 'yes'
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('This is the description the user sees during checkout.', 'woocommerce'),
                    'default' => __('Pay for your items with beyonic', 'woocommerce')
                ),
                'test_api_key' => array(
                    'title' => __('Test Api Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your test open key (you can get it from your Beyonic).', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'placeholder' => ''
                ),
                'live_api_key' => array(
                    'title' => __('Live Api Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your live open key (you can get it from your Beyonic).', 'woocommerce'),
                    'default' => '',
                    'desc_tip' => true,
                    'placeholder' => ''
                ),
                'test_mode' => array(
                    'title' => __('Test mode', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Test mode', 'woocommerce'),
                    'default' => 'no'
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
            ?>
            <h3><?php _e('Beyonic', 'woocommerce'); ?></h3>
            <p><?php _e('Please fill in the below section to start accepting payments on your site! You can find all the required information in your Beyonic Dashboard'); ?> </p>
                <table class="form-table">
                    <?php
                    // Generate the HTML For the settings form.
                    $this->generate_settings_html();
                    ?>
                </table><!--/.form-table-->

            <?php 
        }

        function process_payment($order_id) {
            global $woocommerce, $wpdb;
            $order = new WC_Order($order_id);

            $this->authorize_beyonic();

            if (!empty($_POST['billing_first_name'])) {
                $first_name = $_POST['billing_first_name'];
                $last_name = $_POST['billing_last_name'];
                $phon = $_POST['billing_phone'];
            }
           
            $Webhook = $wpdb->get_var("
		SELECT option_value FROM wp_options
                WHERE option_name = 'Webhook'");

            if (empty($Webhook)) {
                try {
                    $hooks = Beyonic_Webhook::create(array(
                                "event" => "collection.received",
                                "target" => plugin_dir_url(__FILE__)."reciver_beyonic_ipn.php"
                    ));
                    $wpdb->insert('wp_options', array('option_name' => 'Webhook', 'option_value' => 'Collection_recived'));
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
            }

            try {
                $request = Beyonic_Collection_Request::create(array(
                            "phonenumber" => $phon,
                            "first_name" => $first_name,
                            "last_name" => $last_name,
                            "amount" => $order->get_total(),
                            "success_message" => 'Thank you for your payment!',
                            "send_instructions" => true,
                            "currency" => "BXC",
                            "metadata" => array("order_id" => $order_id)
                ));
                $order->payment_complete();
                $order->update_status('pending');
                return array(
                    'result' => 'success',
                    'redirect' => $this->get_return_url($order)
                );
            } catch (Exception $exc) {
                print_r($exc);
                die;
                echo $exc->getTraceAsString();
            }
        }

        /**
         * Generate the credit card payment form
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
            if ($this->description) {
                echo "<p>" . $this->description . "</p>";
            }
            // Are we in test mode?
            if ($this->test_mode == 'yes') {
                ?>
                <div style="background-color:yellow;">
                    You're in <strong>test mode</strong>   
                </div>
                <?php
            }
        }
        
        /**
         * Set authorization to Beyonic API
         */
        function authorize_beyonic()
        {
            if ($this->test_mode) {
                Beyonic::setApiKey($this->test_api_key);
            } else {
                Beyonic::setApiKey($this->live_api_key);
            }
        }

    }

    /**
     * Add the gateway to WooCommerce
     * */
    function add_beyonic_gateway($methods) {
        $methods[] = 'WC_Gateway_Beyonic';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_beyonic_gateway');
}
