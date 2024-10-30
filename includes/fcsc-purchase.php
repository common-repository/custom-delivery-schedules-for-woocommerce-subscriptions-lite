<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Purchase
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Purchase')){
    class FCDS_F_WCS_Purchase {

        /**
         * Check is pro version
         *
         * @return boolean
         */
        public static function is_pro(){
            return false;
        }

        /**
         * get menu title based on plugin type
         *
         * @return boolean
         */
        public static function get_title(){
            if(self::is_pro()){
                return __( 'Custom Delivery Schedules', FCDS_F_WCS_TEXT_DOMAIN );
            } else {
                return __( 'Custom Delivery Schedules Lite', FCDS_F_WCS_TEXT_DOMAIN );
            }
        }
    }
}