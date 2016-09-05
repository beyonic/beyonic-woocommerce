<?php
/**
 * Plugin Name: WooCommerce Beyonic Gateway
 * Plugin URI: http://beyonic.com/
 * Description: Receive payments using the Beyonic.
 * Author: beyonic
 * Author URI: http://beyonic.com/
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

define('WPSP_NAME', 'beyonic-payment-gateway');

add_action('init', 'beyonic');
require_once('vendor/beyonic/beyonic-php/lib/Beyonic.php');

function beyonic() {

    if (!empty($_GET['beyonic_ipn']) && $_GET['beyonic_ipn'] == 1) {
        require_once 'reciver_beyonic_ipn.php';
        return;
    }

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
            $this->api_key = $this->get_option('api_key');
            $this->description = $this->get_option('description');
            $this->beyonic_api_version = 'v1';
            $this->ipn_url = site_url() . "?beyonic_ipn=1";
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
                    'label' => __('Enable the Beyonic gateway', 'woocommerce'),
                    'default' => 'yes'
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('', 'woocommerce'),
                    'default' => __('Pay for your items with beyonic', 'woocommerce')
                ),
                'api_key' => array(
                    'title' => __('Api Key', 'woocommerce'),
                    'type' => 'text',
                    'description' => __('Please enter your test open key (you can get it from your Beyonic).', 'woocommerce'),
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
                $first_name = sanitize_text_field($_POST['billing_first_name']);
                $last_name = sanitize_text_field($_POST['billing_last_name']);
                $phon = intval($_POST['billing_phone']);
                $order_total = intval($order->get_total());
            }


            $Webhook = $wpdb->get_var("
		SELECT option_value FROM wp_options
                WHERE option_name = 'Webhook'");

            if (empty($Webhook)) {
                try {
                    $hooks = Beyonic_Webhook::create(array(
                                "event" => "collection.received",
                                "target" => $this->ipn_url
                    ));
                    $wpdb->insert('wp_options', array('option_name' => 'Webhook', 'option_value' => $this->ipn_url));
                } catch (Exception $exc) {
                    echo $exc->getTraceAsString();
                }
            }

            try {

                $request = Beyonic_Collection_Request::create(array(
                            "phonenumber" => $phon,
                            "first_name" => $first_name,
                            "last_name" => $last_name,
                            "amount" => $order_total,
                            "success_message" => 'Thank you for your payment!',
                            "send_instructions" => true,
                            "currency" => "BXC",
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
                echo $exc->getTraceAsString();
            }
        }

        function authorize_beyonic() {
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
