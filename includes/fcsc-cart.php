<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Cart
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Cart')){
    class FCDS_F_WCS_Cart{
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
            // Add chosen shipping option in cart item object
            add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'addShippingCycleOptionToCartItem'), 100, 3 );
            // Display chosen shipping cycle in cart
            add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'displayChosenShippingCycleInCart'), 100, 2 );
            // Add shipping cycle in order item
            add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'addChosenShippingCycleInOrderItem'), 10, 4 );
            //Add additional fee with the order
            add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'addAdditionalFeeBasedOnShippingOption') );
        }

        /**
         * Add additional fee if required
         *
         * @param $cart object
         * */
        public static function addAdditionalFeeBasedOnShippingOption($cart){
            $i = 1;
            foreach ( $cart->cart_contents as $cart_item_key => $cart_item ) {
                if(isset($cart_item['fcsc_wcs_shipping_cycle_additional_price'])){
                    if($cart_item['fcsc_wcs_shipping_cycle_additional_price'] > 0){
                        $amount = $cart_item['fcsc_wcs_shipping_cycle_additional_price'];
                        $product = $cart_item['data'];
                        $fee_name = esc_html__('Subscription delivery fee: ', FCDS_F_WCS_TEXT_DOMAIN);
                        $fee_name .= $product->get_name();

                        if(isset($cart_item['fcsc_wcs_shipping_cycle_additional_price_name'])){
                            $name = trim($cart_item['fcsc_wcs_shipping_cycle_additional_price_name']);
                            if(!empty($name)){
                                $fee_name = esc_html__($name, FCDS_F_WCS_TEXT_DOMAIN);
                            }
                        }

                        $fee_exists = self::isFeeExists($cart, $fee_name);
                        if($fee_exists){
                            $fee_name = $fee_name." (".$i.")";
                            $i++;
                        }
                        
                        $tax_status = $product->get_tax_status();
                        $is_taxable = apply_filters('fcds_wcs_add_tax_with_additional_fee', true, $cart_item, $cart);
                        if($tax_status === 'taxable' && $is_taxable){
                            $tax_class = $product->get_tax_class();
                            $cart->add_fee($fee_name, $amount, true, $tax_class);
                        } else {
                            $cart->add_fee($fee_name, $amount);
                        }
                    }
                }
            }
        }

        /**
         * Is fee exists
         *
         * @param $cart object
         * @param $fee_name string
         * @return boolean
         * */
        protected static function isFeeExists($cart, $fee_name){
            $fees = $cart->get_fees();
            if(!empty($fees)){
                foreach ($fees as $fee){
                    if(isset($fee->name)){
                        if($fee_name == $fee->name){
                            return true;
                        }
                    }
                }
            }

            return false;
        }

        /**
         * Add shipping cycle option to cart item
         *
         * @param array $cart_item_data
         * @param int   $product_id
         * @param int   $variation_id
         * @return array
         * */
        public static function addShippingCycleOptionToCartItem($cart_item_data, $product_id, $variation_id){
            if($variation_id)
                $product = FCDS_F_WCS_Helper::get_product($variation_id);
            else
                $product = FCDS_F_WCS_Helper::get_product($product_id);

            if(is_object($product)){
                $is_subscription = FCDS_F_WCS_Helper::is_subscription($product);
                if($is_subscription){
                    $enabled_shipping_options = FCDS_F_WCS_Helper::is_enabled_shipping_options($product);
                    if($enabled_shipping_options){
                        $fcsc_wcs_shipping_cycle_index = filter_input( INPUT_POST, 'fcsc_wcs_shipping_cycle' );
                        if($fcsc_wcs_shipping_cycle_index !== ''){
                            $shipping_cycles = FCDS_F_WCS_Helper::getShippingCycles($product);
                            if(isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]) && !empty($shipping_cycles[$fcsc_wcs_shipping_cycle_index])){
                                $cart_item_data['fcsc_wcs_shipping_cycle_index'] = $fcsc_wcs_shipping_cycle_index;
                                $cart_item_data['fcsc_wcs_shipping_cycle_period'] = $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_period'];
                                $cart_item_data['fcsc_wcs_shipping_cycle_period_interval'] = $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_period_interval'];
                                $cart_item_data['fcsc_wcs_shipping_cycle_additional_price'] = isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]['additional_price'])? $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['additional_price']: 0;
                                $cart_item_data['fcsc_wcs_shipping_cycle_additional_price_name'] = isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]['additional_price_name'])? $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['additional_price_name']: '';
                                $cart_item_data['fcsc_wcs_shipping_cycle_sync_week_month'] = isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_week_month'])? $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_week_month']: 0;
                                $cart_item_data['fcsc_wcs_shipping_cycle_sync_year_day'] = isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_year_day'])? $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_year_day']: 0;
                                $cart_item_data['fcsc_wcs_shipping_cycle_sync_year'] = isset($shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_year'])? $shipping_cycles[$fcsc_wcs_shipping_cycle_index]['shipping_sync_year']: 0;
                            }
                        }
                    }
                }
            }

            return $cart_item_data;
        }

        /**
         * To display shipping cycle in cart
         *
         * @param array $item_data
         * @param array $cart_item
         * @return  array
         * */
        public static function displayChosenShippingCycleInCart($item_data, $cart_item){
            if ( empty( $cart_item['fcsc_wcs_shipping_cycle_period'] ) || empty($cart_item['fcsc_wcs_shipping_cycle_period_interval']) ) return $item_data;
            $key_string = __( 'Delivery schedule', FCDS_F_WCS_TEXT_DOMAIN );
            $formatted_shipping_cycle = FCDS_F_WCS_Helper::getFormattedShippingCycle($cart_item);
            $display_string = FCDS_F_WCS_Helper::getShippingCycleText($cart_item['fcsc_wcs_shipping_cycle_period'], $cart_item['fcsc_wcs_shipping_cycle_period_interval'], $formatted_shipping_cycle);
            $item_data[] = array(
                'key'     => $key_string,
                'value'   => 1,
                'display' => $display_string,
            );

            return $item_data;
        }

        /**
         * Add pay upfront option in order item
         *
         * @param WC_Order_Item_Product $item
         * @param string                $cart_item_key
         * @param array                 $values
         * @param WC_Order              $order
         * */
        public static function addChosenShippingCycleInOrderItem($item, $cart_item_key, $values, $order){
            if ( empty( $values['fcsc_wcs_shipping_cycle_period'] ) && empty( $values['fcsc_wcs_shipping_cycle_period_interval'] ) ) return;
            $product_id = $item->get_product_id();
            $variation_id = $item->get_variation_id();
            if(!empty($variation_id)){
                $product_id = $variation_id;
            }
            $order_type = $order->get_type();

            $key_string = __( 'Delivery schedule', FCDS_F_WCS_TEXT_DOMAIN );
            $formatted_shipping_cycle = FCDS_F_WCS_Helper::getFormattedShippingCycle($values);
            $display_string = FCDS_F_WCS_Helper::getShippingCycleText($values['fcsc_wcs_shipping_cycle_period'], $values['fcsc_wcs_shipping_cycle_period_interval'], $formatted_shipping_cycle);
            $item->add_meta_data( $key_string, $display_string );

            $fcsc_wcs_shipping_cycle = array();
            $fcsc_wcs_shipping_cycle['period'] = $values['fcsc_wcs_shipping_cycle_period'];
            $fcsc_wcs_shipping_cycle['period_interval'] = $values['fcsc_wcs_shipping_cycle_period_interval'];
            $fcsc_wcs_shipping_cycle['additional_price'] = $values['fcsc_wcs_shipping_cycle_additional_price'];
            $fcsc_wcs_shipping_cycle['additional_price_name'] = $values['fcsc_wcs_shipping_cycle_additional_price_name'];
            $fcsc_wcs_shipping_cycle['sync_week_month'] = $values['fcsc_wcs_shipping_cycle_sync_week_month'];
            $fcsc_wcs_shipping_cycle['sync_year_day'] = $values['fcsc_wcs_shipping_cycle_sync_year_day'];
            $fcsc_wcs_shipping_cycle['sync_year'] = $values['fcsc_wcs_shipping_cycle_sync_year'];
            $item->add_meta_data( '_fcsc_wcs_shipping_cycle', $fcsc_wcs_shipping_cycle, true );
            if($order_type !== "shop_subscription"){
                $old_fcsc_wcs_shipping_cycle = $order->get_meta('_fcsc_wcs_shipping_cycle', true);
                if(!is_array($old_fcsc_wcs_shipping_cycle)){
                    $old_fcsc_wcs_shipping_cycle = array();
                }
                $new_fcsc_wcs_shipping_cycle = array();
                $new_fcsc_wcs_shipping_cycle[$product_id] = $fcsc_wcs_shipping_cycle;
                $fcsc_wcs_shipping_cycle = array_merge($old_fcsc_wcs_shipping_cycle, $new_fcsc_wcs_shipping_cycle);
            } else {
                $order->add_meta_data('_fcsc_wcs_shipping_product_id', $product_id, true);
            }
            $order->add_meta_data('_fcsc_wcs_shipping_cycle', $fcsc_wcs_shipping_cycle, true);
            $order->add_meta_data('_fcsc_wcs_has_shipping_cycle', 1, true);
        }
    }

    FCDS_F_WCS_Cart::init();
}