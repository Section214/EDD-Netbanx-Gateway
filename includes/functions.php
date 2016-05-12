<?php
/**
 * Helper functions
 *
 * @package         EDD\Gateway\Netbanx\Functions
 * @since           1.0.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


/**
 * Check if a given currency is supported by Netbanx
 *
 * @since       1.0.0
 * @param       string $currency The currency to check
 * @return      bool $supported True if supported, false otherwise
 */
function edd_netbanx_is_valid_currency( $currency ) {
    $types = array(
        'CAD',
        'EUR',
        'GBP',
        'USD'
    );

    if( in_array( $currency, $types ) ) {
        $supported = true;
    } else {
        $supported = false;
    }

    return $supported;
}


/**
 * Error handler
 *
 * @since       1.0.0
 * @param       object $error The error returned by the gateway
 * @return      string $message The error message to display to the user
 */
function edd_netbanx_error_handler( $error ) {
    switch( $error->error_number ) {
        case '1000':
        case '1002':
        case '1003':
        case '1007':
            $message = __( 'An internal error occurred.', 'edd-netbanx-gateway' );
            break;
        case '5010':
            $message = __( 'The submitted country code is invalid.', 'edd-netbanx-gateway' );
            break;
        case '5016':
            $message = __( 'The account you provided cannot bei found.', 'edd-netbanx-gateway' );
            break;
        case '5017':
            $message = __( 'The account you provided is disabled.', 'edd-netbanx-gateway' );
            break;
        case '5023':
            $message = __( 'The request is not parseable.', 'edd-netbanx-gateway' );
            break;
        case '5042':
        case '5068':
            $message = __( 'Incomplete data was passed to Netbanx.', 'edd-netbanx-gateway' );
            break;
        case '5269':
            $message = __( 'The merchant ID is invalid.', 'edd-netbanx-gateway' );
            break;
        case '5270':
            $message = __( 'The specified API credentials are invalid.', 'edd-netbanx-gateway' );
            break;
        case '5271':
        case '5272':
            $message = __( 'Requested response format not supported.', 'edd-netbanx-gateway' );
            break;
        case '5273':
            $message = __( 'Invalid API URL specified.', 'edd-netbanx-gateway' );
            break;
        case '5375':
            $message = __( 'The authentication credentials provided have expired.', 'edd-netbanx-gateway' );
            break;
        case '5276':
            $message = __( 'The authentication credentials provided have been disabled.', 'edd-netbanx-gateway' );
            break;
        case '5277':
            $message = __( 'The authentication credentials provided have been locked out.', 'edd-netbanx-gateway' );
            break;
        case '5278':
        case '5279':
        case '5280':
            $message = __( 'The authentication credentials provided were not accepted.', 'edd-netbanx-gateway' );
            break;
        case '3002':
        case '3017':
            $message = __( 'Invalid card number.', 'edd-netbanx-gateway' );
            break;
        case '3004':
            $message = __( 'Postal code is required.', 'edd-netbanx-gateway' );
            break;
        case '3005':
        case '3019':
            $message = __( 'Invalid CVV value.', 'edd-netbanx-gateway' );
            break;
        case '3006':
        case '3012':
            $message = __( 'Credit card has expired.', 'edd-netbanx-gateway' );
            break;
        case '3007':
            $message = __( 'AVS check failed.', 'edd-netbanx-gateway' );
            break;
        case '3008':
            $message = __( 'The merchant account is not configured for this card type.', 'edd-netbanx-gateway' );
            break;
        case '3009':
        case '3011':
        case '3013':
        case '3014':
        case '3015':
        case '3016':
        case '3018':
        case '3020':
        case '3022':
        case '3023':
        case '3024':
        case '3029':
        case '3030':
        case '3032':
            $message = __( 'Transaction declined.', 'edd-netbanx-gateway' );
            break;
        case '3021':
            $message = __( 'Confirmation number could not be found.', 'edd-netbanx-gateway' );
            break;
        case '3025':
            $message = __( 'Processing gateway reported invalid data.', 'edd-netbanx-gateway' );
            break;
        case '3026':
            $message = __( 'Account type is invalid.', 'edd-netbanx-gateway' );
            break;
        case '3027':
            $message = __( 'Processing gateway reported your account limit has been exceeded.', 'edd-netbanx-gateway' );
            break;
        case '3028':
            $message = __( 'Processing gateway reported a system error.', 'edd-netbanx-gateway' );
            break;
        case '3031':
            $message = __( 'The requested transaction is not on hold.', 'edd-netbanx-gateway' );
            break;
        default:
            $message = $error->message;
            break;
    }


    return apply_filters( 'edd_netbanx_gateway_error', $message, $error );
}
