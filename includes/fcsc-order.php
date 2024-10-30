<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Order
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Order')){
    class FCDS_F_WCS_Order{
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
            // change template for shipping order
            add_filter('wc_get_template', array(__CLASS__, 'changeTemplateForShippingOrders'), 10, 5);
        }

        /**
         * Change the default template for shipping orders
         *
         * @param $located string
         * @param $template_name string
         * @param $args array
         * @param $template_path string
         * @param $default_path string
         * @return string
         * */
        public static function changeTemplateForShippingOrders($located, $template_name, $args, $template_path, $default_path)
        {
            $process = true;
            $order = array();
            if($template_name == 'order/order-details.php') {
                if(isset($args['order_id'])){
                    $order = wc_get_order($args['order_id']);
                }
            } else if($template_name == 'order/order-details-item.php' || $template_name == 'emails/email-order-items.php' || $template_name == 'emails/email-order-details.php') {
                if(isset($args['order'])){
                    $order = $args['order'];
                }
            } else if($template_name == 'myaccount/related-orders.php') {
                $located = self::getTemplatePath('related-orders.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/myaccount/related-orders.php', 'myaccount');
                $process = false;
            } else if($template_name == 'myaccount/orders.php') {
                $located = self::getTemplatePath('orders.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/myaccount/orders.php', 'myaccount');
                $process = false;
            }
            if (!empty($order) && $process) {
                $is_subscription_shipping_order = self::isSubscriptionShippingOrder($order);
                // only process if it is an non shipping order
                if ($is_subscription_shipping_order === true) {
                    if ($template_name == 'order/order-details-item.php') {
                        $located = self::getTemplatePath('order-details-item.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/order/order-details-item.php', 'order');
                    } else if ($template_name == 'order/order-details.php') {
                        $located = self::getTemplatePath('order-details.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/order/order-details.php', 'order');
                    } else if ($template_name == 'emails/email-order-items.php') {
                        $located = self::getTemplatePath('email-order-items.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/emails/email-order-items.php', 'emails');
                    } else if ($template_name == 'emails/email-order-details.php') {
                        $located = self::getTemplatePath('email-order-details.php', FCDS_F_WCS_PLUGIN_DIR . '/templates/emails/email-order-details.php', 'emails');
                    }
                }
            }

            return $located;
        }

        /**
         * Get template path
         *
         * @param $template_name string
         * @param $default_path string
         * @param $folder string
         * @return string
         * */
        protected static function getTemplatePath($template_name, $default_path, $folder = ''){
            $path_from_template = FCDS_F_WCS_Helper::getTemplateOverride($template_name, $folder);
            if($path_from_template) $default_path = $path_from_template;

            return $default_path;
        }

        /**
         * Is subscription shipping order
         *
         * @param $order object
         * @return boolean
         * */
        public static function isSubscriptionShippingOrder($order){
            if(!empty($order)){
                $is_subscription_shipping_order = $order->get_meta('_fcsc_subscription_shipping', true);
                // only process if it is an non shipping order
                if(!empty($is_subscription_shipping_order) && $is_subscription_shipping_order){
                    return true;
                }
            }

            return false;
        }
    }

    FCDS_F_WCS_Order::init();
}