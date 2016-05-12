<?php
/**
 * Plugin Name:     Easy Digital Downloads - Netbanx Gateway
 * Plugin URI:      http://wordpress.org/plugins/edd-netbanx-gateway
 * Description:     Adds a payment gateway for Netbanx to Easy Digital Downloads
 * Version:         1.0.1
 * Author:          Daniel J Griffiths
 * Author URI:      https://section214.com
 * Text Domain:     edd-netbanx-gateway
 *
 * @package         EDD\Gateway\Netbanx
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


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
            define( 'EDD_NETBANX_VER', '1.0.1' );

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
            require_once EDD_NETBANX_DIR . 'includes/gateway.php';
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
