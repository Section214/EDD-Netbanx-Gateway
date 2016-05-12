<?php
/**
 * Gateway Functions
 *
 * @package         EDD\Gateway\Netbanx\Gateway
 * @author          Daniel J Griffiths <dgriffiths@section214.com>
 * @copyright       Copyright (c) 2014, Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
    exit;
}


/**
 * Add settings section
 *
 * @since       1.0.3
 * @param       array $sections The existing extensions sections
 * @return      array The modified extensions settings
 */
function edd_netbanx_add_settings_section( $sections ) {
    $sections['netbanx'] = __( 'Netbanx', 'edd-netbanx' );

    return $sections;
}
add_filter( 'edd_settings_sections_gateways', 'edd_netbanx_add_settings_section' );


/**
 * Register settings
 *
 * @since        1.0.0
 * @param        array $settings The existing plugin settings
 * @param        array The modified plugin settings array
 */
function edd_netbanx_register_settings( $settings ) {
    $new_settings = array(
        'netbanx' => array(
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
            )
        )
    );

    return array_merge( $settings, $new_settings );
}
add_filter( 'edd_settings_gateways', 'edd_netbanx_register_settings', 1 );


/**
 * Register our new gateway
 *
 * @since        1.0.0
 * @param        array $gateways The current gateway list
 * @return        array $gateways The updated gateway list
 */
function edd_netbanx_register_gateway( $gateways ) {
    $gateways['netbanx'] = array(
        'admin_label'    => 'Netbanx',
        'checkout_label' => __( 'Credit Card', 'edd-netbanx' )
    );

    return $gateways;
}
add_filter( 'edd_payment_gateways', 'edd_netbanx_register_gateway' );


/**
 * Process payment submission
 *
 * @since        1.0.0
 * @param        array $purchase_data The data for a specific purchase
 * @return        void
 */
function edd_netbanx_process_payment( $purchase_data ) {
    $errors = edd_get_errors();

    if( ! $errors ) {
        $account_number = edd_get_option( 'edd_netbanx_gateway_account_number', '' );
        $api_key        = edd_get_option( 'edd_netbanx_gateway_api_key', '' );
        $api_url        = ( edd_is_test_mode() ? 'https://api.test.netbanx.com/' : 'https://api.netbanx.com/' );
        $currency       = edd_get_currency();

        try{
            // Handle errors
            $err = false;

            $required = array(
                'card_name'      => __( 'Card name is required.', 'edd-netbanx-gateway' ),
                'card_number'    => __( 'Card number is required.', 'edd-netbanx-gateway' ),
                'card_exp_month' => __( 'Card expiration month is required.', 'edd-netbanx-gateway' ),
                'card_exp_year'  => __( 'Card expiration year is required.', 'edd-netbanx-gateway' ),
                'card_cvc'       => __( 'Card CVC is required.', 'edd-netbanx-gateway' )
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
                'merchantRefNum' => $purchase_data['purchase_key'],
                'amount'         => ( $purchase_data['price'] * 100 ),
                'settleWithAuth' => true,
                'card'           => array(
                    'cardNum'    => $purchase_data['card_info']['card_number'],
                    'cardExpiry' => array(
                        'month' => $purchase_data['card_info']['card_exp_month'],
                        'year'  => $purchase_data['card_info']['card_exp_year']
                    ),
                    'cvv' => $purchase_data['card_info']['card_cvc']
                ),
                'billingDetails' => array(
                    'street'  => $purchase_data['card_info']['card_address'],
                    'city'    => $purchase_data['card_info']['card_city'],
                    'state'   => $purchase_data['card_info']['card_state'],
                    'zip'     => $purchase_data['card_info']['card_zip'],
                    'country' => $purchase_data['card_info']['card_country']
                ),
                'customerIp'   => edd_get_ip(),
                'currencyCode' => $currency
            );

            $args = array(
                'headers' => array(
                    'Authorization' => 'Basic ' . base64_encode( $api_key ),
                    'content-type'  => 'application/json'
                ),
                'body' => json_encode( $params )
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
                    'price'        => $purchase_data['price'],
                    'date'         => $purchase_data['date'],
                    'user_email'   => $purchase_data['user_email'],
                    'purchase_key' => $purchase_data['purchase_key'],
                    'currency'     => $currency,
                    'downloads'    => $purchase_data['downloads'],
                    'cart_details' => $purchase_data['cart_details'],
                    'user_info'    => $purchase_data['user_info'],
                    'status'       => 'pending'
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
add_action( 'edd_gateway_simplify', 'edd_netbanx_process_payment' );


/**
 * Output form errors
 *
 * @since        1.0.0
 * @return        void
 */
function edd_netbanx_errors_div() {
    echo '<div id="edd-netbanx-errors"></div>';
}
add_action( 'edd_after_cc_fields', 'edd_netbanx_errors_div', 999 );