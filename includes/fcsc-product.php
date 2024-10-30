<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Product
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Product')){
    class FCDS_F_WCS_Product{
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

            //To change the add to cart button to detail page in product listing page
            add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'changeWooCommerceAddToCartURL'), 30 , 2);
            add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'changeWooCommerceAddToCartText'), 30 , 2);
            add_filter( 'woocommerce_loop_add_to_cart_link', array( __CLASS__, 'changeWooCommerceAddToCartButton'), 30, 2 );

            // To display shipping cycle option in product page
            add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'displayShippingCycleOption'), 30 );
            add_filter( 'woocommerce_available_variation', array( __CLASS__, 'displayShippingCycleOptionForVariants'), 30, 3);
        }

        /**
         * Change WooCommerce add to cart URL
         *
         * @param $url string
         * @param $product object
         * @return string
         * */
        public static function changeWooCommerceAddToCartURL($url, $product){
            if(FCDS_F_WCS_Helper::is_product_list_page()){
                $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
                if($is_subscription){
                    $disable_add_to_cart_in_list_page = self::disableAddToCartInProductListing($product);
                    if($disable_add_to_cart_in_list_page) $url = FCDS_F_WCS_Helper::get_product_url($product);
                }
            }

            return $url;
        }

        /**
         * Change WooCommerce add to cart text
         *
         * @param $text string
         * @param $product object
         * @return string
         * */
        public static function changeWooCommerceAddToCartText($text, $product){
            if(FCDS_F_WCS_Helper::is_product_list_page()) {
                $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
                if ($is_subscription) {
                    $disable_add_to_cart_in_list_page = self::disableAddToCartInProductListing($product);
                    if ($disable_add_to_cart_in_list_page) $text = __('Read more', 'woocommerce');
                }
            }

            return $text;
        }

        /**
         * Change WooCommerce add to cart Button
         *
         * @param $button string
         * @param $product object
         * @return string
         * */
        public static function changeWooCommerceAddToCartButton($button, $product){
            $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
            if($is_subscription){
                $disable_add_to_cart_in_list_page = self::disableAddToCartInProductListing($product);
                if($disable_add_to_cart_in_list_page){
                    $button = str_replace(array('add_to_cart_button', 'ajax_add_to_cart'), '', $button);
                }
            }

            return $button;
        }

        /**
         * Display shipping cycle option in front end for simple subscription
         * */
        public static function displayShippingCycleOption(){
            global $product;
            $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
            if($is_subscription){
                $enabled_shipping_options = FCDS_F_WCS_Helper::is_enabled_shipping_options($product);
                if($enabled_shipping_options){
                    echo self::getShippingOptionHTMLForFrontEnd($product);
                }
            }
        }

        /**
         * Get Up-front option html
         *
         * @param object $product
         * @return string
         * */
        protected static function getShippingOptionHTMLForFrontEnd($product){
            $html = '';
            $delivery_cycles = FCDS_F_WCS_Helper::getShippingCycles($product);
            $args['use_only_shipping_option'] = FCDS_F_WCS_Helper::use_as_shipping_product($product);
            $path_from_template = FCDS_F_WCS_Helper::getTemplateOverride('product-shipping-options.php');
            $path = FCDS_F_WCS_PLUGIN_PATH . '/templates/product-shipping-options.php';
            if($path_from_template) $path = $path_from_template;
            $args['show_additional_price'] = FCDS_F_WCS_Helper::getOption('fcsc_shipping_front_end_display_additional_price', true);
            $args['display_delivery_option'] = FCDS_F_WCS_Helper::getOption('fcsc_shipping_front_end_display_option', 'radio');
            $args['delivery_title'] = FCDS_F_WCS_Helper::getOption('fcsc_shipping_front_end_option_title', '');
            $args['display_option_block'] = FCDS_F_WCS_Helper::getOption('fcsc_shipping_front_end_display_option_block', 'yes');
            if(trim($args['delivery_title']) != ''){
                $args['delivery_title'] = esc_html__(trim($args['delivery_title']), FCDS_F_WCS_TEXT_DOMAIN);
            }
            $args['display_title'] = true;
            if($args['display_option_block'] === 'no'){
                if(!empty($delivery_cycles) && is_array($delivery_cycles)){
                    if(count($delivery_cycles) == 1){
                        $args['display_title'] = false;
                    }
                }
            }

            ob_start();
            if (!file_exists($path)) return false;
            include($path);
            $html = ob_get_contents();
            ob_clean();
            ob_get_clean();

            return $html;
        }

        /**
         * Display shipping cycle option in front end for variable subscription
         * */
        public static function displayShippingCycleOptionForVariants($variations, $product, $variation){
            $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
            if($is_subscription){
                $enabled_shipping_options = FCDS_F_WCS_Helper::is_enabled_shipping_options($variation);
                if($enabled_shipping_options){
                    $html = self::getShippingOptionHTMLForFrontEnd($variation);
                    $variations['availability_html'] .= $html;
                }
            }

            return $variations;
        }

        /**
         * Disable Add to cart button
         *
         * @param object $product
         * @return boolean
         * */
        protected static function disableAddToCartInProductListing($product){
            return FCDS_F_WCS_Helper::is_enabled_shipping_options($product);
        }
    }

    FCDS_F_WCS_Product::init();
}