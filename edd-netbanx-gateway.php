<?php
/**
 * Plugin Name:     Easy Digital Downloads - Netbanx Gateway
 * Plugin URI:      https://easydigitaldownloads.com/extensions/netbanx-gateway
 * Description:     Adds a payment gateway for Netbanx to Easy Digital Downloads
 * Version:         1.0.0
 * Author:          Daniel J Griffiths
 * Author URI:      http://section214.com
 * Text Domain:     edd-netbanx-gateway
 *
 * @package         EDD\Gateway\Netbanx
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


if( ! class_exists( 'EDD_Netbanx_Gateway' ) ) {


    /**
     * Main EDD_Netbanx_Gateway class
     *
     * @since       1.0.0
     */
    class EDD_Netbanx_Gateway {


        /**
         * @var         EDD_Netbanx_Gateway $instance The one true EDD_Netbanx_Gateway
         * @since       1.0.0
         */
        private static $instance;


        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      self::$instance The one true EDD_Netbanx_Gateway
         */
        public static function instance() {
            if( ! self::$instance ) {
                self::$instance = new EDD_Netbanx_Gateway();
                self::$instance->setup_constants();
                self::$instance->includes();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'EDD_NETBANX_VER', '1.0.0' );

            // Plugin path
            define( 'EDD_NETBANX_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin URL
            define( 'EDD_NETBANX_URL', plugin_dir_url( __FILE__ ) );
        }


        /**
         * Include necessary files
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function includes() {
            require_once EDD_NETBANX_DIR . 'includes/functions.php';
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Edit plugin metalinks
            add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

            // Handle licensing
            if( class_exists( 'EDD_License' ) ) {
                $license = new EDD_License( __FILE__, 'Netbanx Gateway', EDD_NETBANX_VER, 'Daniel J Griffiths' );
            }

            // Register settings
            add_filter( 'edd_settings_gateways', array( $this, 'settings' ), 1 );

            // Add the gateway
            add_filter( 'edd_payment_gateways', array( $this, 'register_gateway' ) );

            // Process payment
            add_action( 'edd_gateway_netbanx', array( $this, 'process_payment' ) );

            // Display errors
            add_action( 'edd_after_cc_fields', array( $this, 'display_errors' ), 999 );
        }


        /**
         * Internationalization
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for language directory
            $lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            $lang_dir = apply_filters( 'edd_netbanx_gateway_lang_dir', $lang_dir );

            // Traditional WordPress plugin locale filter
            $locale = apply_filters( 'plugin_locale', get_locale(), '' );
            $mofile = sprintf( '%1$s-%2$s.mo', 'edd-netbanx-gateway', $locale );

            // Setup paths to current locale file
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/edd-netbanx-gateway/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Look in global /wp-content/languages/edd-netbanx-gateway/ folder
                load_textdomain( 'edd-netbanx-gateway', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                // Look in local /wp-content/plugins/edd-netbanx-gateway/languages/ folder
                load_textdomain( 'edd-netbanx-gateway', $mofile_local );
            } else {
                // Load the default language files
                load_plugin_textdomain( 'edd-netbanx-gateway', false, $lang_dir );
            }
        }


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.0.0
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="https://easydigitaldownloads.com/support/forum/add-on-plugins/netbanx-gateway" target="_blank">' . __( 'Support Forum', 'edd-netbanx-gateway' ) . '</a>'
                );

                $links = array_merge( $links, $help_link );
            }

            return $links;
        }


        /**
         * Register settings
         *
         * @access      public
         * @since       1.0.0
         * @param       array $settings The existing plugin settings
         * @return      array The modified plugin settings
         */
        public function settings( $settings ) {
            $new_settings = array(
                array(
                    'id'    => 'edd_netbanx_gateway_settings',
                    'name'  => '<strong>' . __( 'Netbanx Gateway Settings', 'edd-netbanx-gateway' ) . '</strong>',
                    'desc'  => '',
                    'type'  => 'header'
                ),
                array(
                    'id'    => 'edd_netbanx_gateway_account_number',
                    'name'  => __( 'Account Number', 'edd-netbanx-gateway' ),
                    'desc'  => __( 'Enter your Netbanx account number.', 'edd-netbanx-gateway' ),
                    'type'  => 'text'
                ),
                array(
                    'id'    => 'edd_netbanx_gateway_api_key',
                    'name'  => __( 'API Key', 'edd-netbanx-gateway' ),
                    'desc'  => __( 'Enter your Netbanx API key.', 'edd-netbanx-gateway' ),
                    'type'  => 'text'
                ),
            );

            return array_merge( $settings, $new_settings );
        }


        /**
         * Register our new gateway
         *
         * @access      public
         * @since       1.0.0
         * @param       array $gateways The current gateway list
         * @return      array $gateways The updated gateway list
         */
        public function register_gateway( $gateways ) {
            $gateways['netbanx'] = array(
                'admin_label'       => 'Netbanx',
                'checkout_label'    => __( 'Credit Card', 'edd-netbanx-gateway' )
            );

            return $gateways;
        }


        /**
         * Process payment submission
         *
         * @access      public
         * @since       1.0.0
         * @param       array $purchase_data The data for a given purchase
         * @return      void
         */
        public function process_payment( $purchase_data ) {
            $errors = edd_get_errors();

            if( ! $errors ) {
                $account_number = edd_get_option( 'edd_netbanx_gateway_account_number', '' );
                $api_key        = edd_get_option( 'edd_netbanx_gateway_api_key', '' );
                $api_url        = ( edd_is_test_mode() ? 'https://api.test.netbanx.com/' : 'https://api.netbanx.com/' );
                $currency       = edd_get_currency();

                try{
                    // Handle errors
                    $err    = false;

                    $required = array(
                        'card_name'     => __( 'Card name is required.', 'edd-netbanx-gateway' ),
                        'card_number'   => __( 'Card number is required.', 'edd-netbanx-gateway' ),
                        'card_exp_month'=> __( 'Card expiration month is required.', 'edd-netbanx-gateway' ),
                        'card_exp_year' => __( 'Card expiration year is required.', 'edd-netbanx-gateway' ),
                        'card_cvc'      => __( 'Card CVC is required.', 'edd-netbanx-gateway' )
                    );

                    foreach( $required as $field => $error ) {
                        if( ! $purchase_data['card_info'][$field] ) {
                            edd_set_error( 'authorize_error', $error );
                            $err = true;
                        }
                    }

                    if( ! edd_netbanx_is_valid_currency( $currency ) ) {
                        edd_set_error( 'authorize_error', __( 'The specified currency is not supported by Netbanx at this time.', 'edd-netbanx-gateway' ) );
                        $err = true;
                    }

                    if( $err ) {
                        edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                    }

                    // Make sure the API is accessible
                    $result = wp_remote_retrieve_body( wp_remote_get( $api_url . 'cardpayments/monitor' ) );
                    $result = json_decode( $result );

                    if( ! is_object( $result ) || $result->status != 'READY' ) {
                        edd_set_error( 'authorize_error', __( 'An error occurred with the Netbanx API. Please try again.', 'edd-netbanx-gateway' ) );
                        edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                    }

                    $params = array(
                        'merchantRefNum'    => $purchase_data['purchase_key'],
                        'amount'            => ( $purchase_data['price'] * 100 ),
                        'settleWithAuth'    => true,
                        'card'              => array(
                            'cardNum'       => $purchase_data['card_info']['card_number'],
                            'cardExpiry'    => array(
                                'month'         => $purchase_data['card_info']['card_exp_month'],
                                'year'          => $purchase_data['card_info']['card_exp_year']
                            ),
                            'cvv'           => $purchase_data['card_info']['card_cvc']
                        ),
                        'billingDetails'    => array(
                            'street'            => $purchase_data['card_info']['card_address'],
                            'city'              => $purchase_data['card_info']['card_city'],
                            'state'             => $purchase_data['card_info']['card_state'],
                            'zip'               => $purchase_data['card_info']['card_zip'],
                            'country'           => $purchase_data['card_info']['card_country']
                        ),
                        'customerIp'        => edd_get_ip(),
                        'currencyCode'      => $currency
                    );

                    $args = array(
                        'headers'   => array(
                            'Authorization' => 'Basic ' . base64_encode( $api_key ),
                            'content-type'  => 'application/json'
                        ),
                        'body'      => json_encode( $params )
                    );

                    $response = wp_remote_retrieve_body( wp_remote_post( $api_url . 'cardpayments/v1/accounts/' . $account_number . '/auths', $args ) );
                    $response = json_decode( $response );

                    if( is_wp_error( $response ) ) {
                        edd_set_error( 'authorize_error', __( 'An error occurred with the Netbanx API. Please try again.', 'edd-netbanx-gateway' ) );
                        edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                    }

                    if( isset( $response->error->code ) ) {
                        $error = edd_netbanx_error_handler( $response->error );

                        edd_set_error( 'authorize_error', $error );
                        edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                    } else {
                        $payment_data = array(
                            'price'         => $purchase_data['price'],
                            'date'          => $purchase_data['date'],
                            'user_email'    => $purchase_data['user_email'],
                            'purchase_key'  => $purchase_data['purchase_key'],
                            'currency'      => $currency,
                            'downloads'     => $purchase_data['downloads'],
                            'cart_details'  => $purchase_data['cart_details'],
                            'user_info'     => $purchase_data['user_info'],
                            'status'        => 'pending'
                        );

                        $payment = edd_insert_payment( $payment_data );

                        if( $payment ) {
                            edd_insert_payment_note( $payment, sprintf( __( 'Netbanx Gateway Transaction ID: %s', 'edd-netbanx-gateway' ), $response->id ) );
                            if( function_exists( 'edd_set_payment_transaction_id' ) ) {
                                edd_set_payment_transaction_id( $payment, $response->id );
                            }
                            edd_update_payment_status( $payment, 'publish' );
                            edd_send_to_success_page();
                        } else {
                            edd_set_error( 'authorize_error', __( 'Your payment could not be recorded. Please try again.', 'edd-netbanx-gateway' ) );
                            edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                        }
                    }
                } catch( Exception $e ) {
                    edd_record_gateway_error( __( 'Netbanx Gateway Error', 'edd-netbanx-gateway' ), print_r( $e, true ), 0 );
                    edd_set_error( 'card_declined', __( 'Your card was declined.', 'edd-netbanx-gateway' ) );
                    edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
                }
            } else {
                edd_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['edd-gateway'] );
            }
        }


        /**
         * Output errors
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function display_errors() {
            echo '<div id="edd-netbanx-errors"></div>';
        }
    }
}


/**
 * The main function responsible for returning the one true EDD_Netbanx_Gateway
 * instance to functions everywhere
 *
 * @since       1.0.0
 * @return      EDD_Netbanx_Gateway The one true EDD_Netbanx_Gateway
 */
function edd_netbanx_gateway_load() {
    if( ! class_exists( 'Easy_Digital_Downloads' ) ) {
        if( ! class_exists( 'EDD_Extension_Activation' ) ) {
            require_once EDD_NETBANX_DIR . 'includes/class.extension-activation.php';
        }

        $activation = new EDD_Extension_Activation( plugin_dir_path( __FILE__ ), basename( __FILE__ ) );
        $activation = $activation->run;

        return EDD_Netbanx_Gateway::instance();
    } else {
        return EDD_Netbanx_Gateway::instance();
    }
}
add_action( 'plugins_loaded', 'edd_netbanx_gateway_load' );
