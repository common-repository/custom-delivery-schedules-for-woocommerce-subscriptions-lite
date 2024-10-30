<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_AdminNotices
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_AdminNotices')){
    class FCDS_F_WCS_AdminNotices{

        /**
         * Initialize.
         */
        public static function init() {
            self::add_hooks();
        }

        /**
         * Add hooks
         * */
        protected static function add_hooks(){
            if(is_admin()){
                add_action( 'wp_loaded', array( __CLASS__, 'hide_notices' ));
            }
        }

        /**
         * To display error message on WooCommerce plugin is inactive or folder name is changed
         * */
        public static function warningOnWooCommercePluginNotFound() {
            $htmlPrefix = '<div class="error"><p>';
            $htmlSuffix = '</p></div>';
            $message = __('<strong>Custom Delivery Schedules for WooCommerce Subscriptions is inactive. </strong> The WooCommerce plugin must be active and folder name should be <b>woocommerce</b> for Custom Delivery Schedules to work. Please do a check.', FCDS_F_WCS_TEXT_DOMAIN);
            echo $htmlPrefix.$message.$htmlSuffix;
        }

        /**
         * To display error message on WooCommerce Subscription plugin is inactive or folder name is changed
         * */
        public static function warningOnWooCommerceSubscriptionPluginNotFound() {
            $htmlPrefix = '<div class="error"><p>';
            $htmlSuffix = '</p></div>';
            $message = __('<strong>Custom Delivery Schedules for WooCommerce Subscriptions is inactive. </strong> The WooCommerce Subscriptions plugin must be active and folder name should be <b>woocommerce-subscriptions</b> for Custom Delivery Schedules to work. Please do a check.', FCDS_F_WCS_TEXT_DOMAIN);
            echo $htmlPrefix.$message.$htmlSuffix;
        }

        /**
         * Hide notices
         * */
        public static function hide_notices(){
            if ( isset( $_GET['fcscfwcs-hide-notice'] ) && isset( $_GET['_fcscfwcs_notice_nonce'] ) ) { // WPCS: input var ok, CSRF ok.
                if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_fcscfwcs_notice_nonce'] ) ), 'custom_shipping_cycles_for_woocommerce_subscription_hide_notices_nonce' ) ) { // WPCS: input var ok, CSRF ok.
                    wp_die( esc_html__( 'Action failed. Please refresh the page and retry.', FCDS_F_WCS_TEXT_DOMAIN ) );
                }

                $hide_notice = sanitize_text_field( wp_unslash( $_GET['fcscfwcs-hide-notice'] ) ); // WPCS: input var ok, CSRF ok.

                update_user_meta( get_current_user_id(), 'dismissed_custom_shipping_cycles_for_woocommerce_subscription_admin_' . $hide_notice . '_notice', true );

                do_action( 'custom_shipping_cycles_for_woocommerce_subscription_hide_' . $hide_notice . '_notice' );
            }
        }
    }

    FCDS_F_WCS_AdminNotices::init();
}