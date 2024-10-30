<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Email_Manager
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FCDS_F_WCS_Email_Manager{
    /**
     * Constructor sets up actions
     */
    public function __construct() {

        // template path
        if (!defined('FCDS_F_WCS_PLUGIN_TEMPLATE_PATH')) define( 'FCDS_F_WCS_PLUGIN_TEMPLATE_PATH', FCDS_F_WCS_PLUGIN_DIR . '/templates/' );
        // include the email class files
        add_filter( 'woocommerce_email_classes', array( &$this, 'init_emails' ) );

        // Email Actions - Triggers
        $email_actions = array(
            'created_prepaid_shipping_order_email',
            'created_prepaid_shipping_order_email_for_customer',
        );
        foreach ( $email_actions as $action ) {
            add_action( $action, array( 'WC_Emails', 'send_transactional_email' ), 10, 10 );
        }

        add_filter( 'woocommerce_template_directory', array( $this, 'get_template_directory' ), 10, 2 );

    }

    public function init_emails( $emails ) {
        // Include the email class file if it's not included already
        if ( ! isset( $emails[ 'FCDS_F_WCS_EmailCreatedPrePaidShippingOrder' ] ) ) {
            $emails[ 'FCDS_F_WCS_EmailCreatedPrePaidShippingOrder' ] = include_once('fcsc-email-created-prepaid-shipping-order.php');
        }
        if ( ! isset( $emails[ 'FCDS_F_WCS_EmailCreatedPrePaidShippingOrderForCustomer' ] ) ) {
            $emails[ 'FCDS_F_WCS_EmailCreatedPrePaidShippingOrderForCustomer' ] = include_once('fcsc-email-created-prepaid-shipping-order-for-customer.php');
        }

        return $emails;
    }

    public function get_template_directory( $directory, $template ) {
        return dirname(FCDS_F_WCS_PLUGIN_BASENAME);
    }

}

new FCDS_F_WCS_Email_Manager();