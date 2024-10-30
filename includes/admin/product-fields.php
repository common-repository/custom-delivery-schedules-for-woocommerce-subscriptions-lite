<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Admin_ProductFields
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Admin_ProductFields')){
    class FCDS_F_WCS_Admin_ProductFields{

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
                add_action( 'woocommerce_product_options_general_product_data', array(__CLASS__, 'loadCustomFieldsInSubscriptionProductPage') );
                add_action( 'woocommerce_variable_subscription_pricing', array(__CLASS__, 'loadCustomFieldsInSubscriptionVariantProductPage'), 30, 3 );
                add_action( 'woocommerce_process_product_meta', array( __CLASS__, 'saveCustomFieldsWhichLoadsInSubscription') );
                add_action( 'woocommerce_save_product_variation', array( __CLASS__, 'saveCustomFieldsForVariableSubscription'), 10, 2 );

                add_action('wp_ajax_load_fcsc_shipping_option', array( __CLASS__, 'loadShippingOptionFieldThroughAjax'));
            }
        }

        /**
         * Load shipping option in product page
         * */
        public static function loadCustomFieldsInSubscriptionProductPage(){
            global $post;
            $fcsc_wcs_shipping_options = get_post_meta( $post->ID, 'fcsc_wcs_shipping_options', true );
            $fcsc_wcs_shipping_options_index = get_post_meta( $post->ID, 'fcsc_wcs_shipping_options_index', true );
            $fcsc_wcs_enable_shipping_options = get_post_meta( $post->ID, 'fcsc_wcs_enable_shipping_options', true );
            $fcsc_wcs_use_only_shipping_cycle = get_post_meta( $post->ID, 'fcsc_wcs_use_only_shipping_cycle', true );

            self::loadShippingCycleFields($fcsc_wcs_shipping_options, $fcsc_wcs_shipping_options_index, $fcsc_wcs_enable_shipping_options, $fcsc_wcs_use_only_shipping_cycle);
        }

        /**
         * Load field in subscription variant page
         * @param $loop
         * @param $variation_data
         * @param object $variation
         * */
        public function loadCustomFieldsInSubscriptionVariantProductPage($loop, $variation_data, $variation){
            $fcsc_wcs_shipping_options = get_post_meta( $variation->ID, 'fcsc_wcs_shipping_options', true );
            $fcsc_wcs_shipping_options_index = get_post_meta( $variation->ID, 'fcsc_wcs_shipping_options_index', true );
            $fcsc_wcs_enable_shipping_options = get_post_meta( $variation->ID, 'fcsc_wcs_enable_shipping_options', true );
            $fcsc_wcs_use_only_shipping_cycle = get_post_meta( $variation->ID, 'fcsc_wcs_use_only_shipping_cycle', true );

            self::loadShippingCycleFields($fcsc_wcs_shipping_options, $fcsc_wcs_shipping_options_index, $fcsc_wcs_enable_shipping_options, $fcsc_wcs_use_only_shipping_cycle, $loop);
        }

        /**
         * Display shipping option fields
         *
         * @param array $fields
         * @param integer $fcsc_wcs_shipping_options_index
         * @param array $fcsc_wcs_enable_shipping_options
         * */
        protected static function loadShippingCycleFields($fields, $fcsc_wcs_shipping_options_index, $fcsc_wcs_enable_shipping_options, $fcsc_wcs_use_only_shipping_cycle, $loop = ''){
            if(!(FCDS_F_WCS_Purchase::is_pro())) {
                if(!empty($fields) && is_array($fields)){
                    $fields = array_values($fields);
                    $fields = array($fields[0]);
                }
            }
            include( FCDS_F_WCS_PLUGIN_PATH.'/templates/admin/product_shipping.php' );
        }

        /**
         * Display shipping option blocks
         *
         * @param integer $index
         * @param array $data
         * */
        protected static function loadShippingCycleBlockFields($index, $data, $loop = ''){
            include( FCDS_F_WCS_PLUGIN_PATH.'/templates/admin/product_shipping_option.php' );
        }

        /**
         * Load shipping option block through ajax
         * */
        public static function loadShippingOptionFieldThroughAjax(){
            $field_index = isset($_POST['field_index'])? sanitize_text_field($_POST['field_index']): '';
            $loop = isset($_POST['loop'])? sanitize_text_field($_POST['loop']): '';
            self::loadShippingCycleBlockFields($field_index, array(), $loop);
            exit;
        }

        /**
         * To save custom fields which loads in subscription product page
         *
         * @param int $post_id
         * @return boolean
         * */
        public function saveCustomFieldsWhichLoadsInSubscription($post_id){
            $shipping_options = 'fcsc_wcs_shipping_options';
            if ( /*! Flycart_WCS_PayUpFrontHelper::is_subscription( $post_id ) || */! ( isset( $_POST['woocommerce_meta_nonce'] ) || wp_verify_nonce( sanitize_key( $_POST['woocommerce_meta_nonce'] ), 'woocommerce_save_data' ) ) ) {
                return false;
            }
            $product_shipping_options = isset($_POST[ $shipping_options ])? $_POST[ $shipping_options ]: '';
            $fcsc_wcs_shipping_options_index = isset($_POST[ 'fcsc_wcs_shipping_options_index' ])? sanitize_text_field($_POST[ 'fcsc_wcs_shipping_options_index' ]): 1;
            $fcsc_wcs_enable_shipping_options = isset($_POST[ 'fcsc_wcs_enable_shipping_options' ])? sanitize_text_field($_POST[ 'fcsc_wcs_enable_shipping_options' ]): '';
            $fcsc_wcs_use_only_shipping_cycle = isset($_POST[ 'fcsc_wcs_use_only_shipping_cycle' ])? sanitize_text_field($_POST[ 'fcsc_wcs_use_only_shipping_cycle' ]): '';
            update_post_meta( $post_id, $shipping_options, $product_shipping_options );
            update_post_meta( $post_id, 'fcsc_wcs_shipping_options_index', $fcsc_wcs_shipping_options_index );
            update_post_meta( $post_id, 'fcsc_wcs_enable_shipping_options', $fcsc_wcs_enable_shipping_options );
            update_post_meta( $post_id, 'fcsc_wcs_use_only_shipping_cycle', $fcsc_wcs_use_only_shipping_cycle );
        }

        /**
         * To save custom fields which loads in variable subscription product page
         *
         * @param int $variation_id
         * @param int $index
         * @return boolean
         * */
        public function saveCustomFieldsForVariableSubscription($variation_id, $index){
            $shipping_options = 'fcsc_wcs_shipping_options';
            if ( /*! Flycart_WCS_PayUpFrontHelper::is_subscription( $variation_id ) ||*/ empty( $_POST['_wcsnonce_save_variations'] ) || ! wp_verify_nonce( $_POST['_wcsnonce_save_variations'], 'wcs_subscription_variations' ) ) {
                return false;
            }

            if(!isset($_POST[$shipping_options][ $index ])) return false;

            $product_shipping_options = isset($_POST[ $shipping_options ][ $index ])? $_POST[ $shipping_options ][ $index ]: '';
            $fcsc_wcs_shipping_options_index = isset($_POST[ 'fcsc_wcs_shipping_options_index' ][ $index ])? sanitize_text_field($_POST[ 'fcsc_wcs_shipping_options_index' ][ $index ]): 1;
            $fcsc_wcs_enable_shipping_options = isset($_POST[ 'fcsc_wcs_enable_shipping_options' ][ $index ])? sanitize_text_field($_POST[ 'fcsc_wcs_enable_shipping_options' ][ $index ]): '';
            $fcsc_wcs_use_only_shipping_cycle = isset($_POST[ 'fcsc_wcs_use_only_shipping_cycle' ][ $index ])? sanitize_text_field($_POST[ 'fcsc_wcs_use_only_shipping_cycle' ][ $index ]): '';
            update_post_meta( $variation_id, $shipping_options, $product_shipping_options );
            update_post_meta( $variation_id, 'fcsc_wcs_shipping_options_index', $fcsc_wcs_shipping_options_index );
            update_post_meta( $variation_id, 'fcsc_wcs_enable_shipping_options', $fcsc_wcs_enable_shipping_options );
            update_post_meta( $variation_id, 'fcsc_wcs_use_only_shipping_cycle', $fcsc_wcs_use_only_shipping_cycle );
        }
    }

    FCDS_F_WCS_Admin_ProductFields::init();
}