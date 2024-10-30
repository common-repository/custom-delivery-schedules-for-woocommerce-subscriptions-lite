<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Settings
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Settings')){
    class FCDS_F_WCS_Settings{

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
        }

        /**
         * Load setting page
         * */
        public static function loadSettingsPage(){
            if ( ! empty( $_POST['save'] ) ) { // WPCS: input var ok, sanitization ok.

                if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), 'cscfs-settings' ) ) { // WPCS: input var ok, sanitization ok.
                    echo '<div class="updated error"><p>' . esc_html__( 'Save failed. Please try again.', FCDS_F_WCS_TEXT_DOMAIN ) . '</p></div>';
                } else {
                    self::saveSettings();
                    echo '<div class="updated success"><p>' . esc_html__( 'Your settings have been saved.', FCDS_F_WCS_TEXT_DOMAIN ) . '</p></div>';
                }
            }
            include( FCDS_F_WCS_PLUGIN_PATH.'/templates/admin/settings.php' );
        }

        /**
         * Save settings
         * */
        protected static function saveSettings(){
            $accepted_fields = self::acceptedFields();
            if(is_array($accepted_fields) && !empty($accepted_fields)){
                foreach ($accepted_fields as $field_name){
                    if(isset($_REQUEST[$field_name])){
                        $field_value = sanitize_text_field($_REQUEST[$field_name]);
                        update_option( $field_name, $field_value, true );
                    } else {
                        update_option( $field_name, '', true );
                    }
                }
            }
        }

        /**
         * Get settings accepted fields
         * */
        protected static function acceptedFields(){
            $fields = array(
                'fcsc_shipping_front_end_option_title',
                'fcsc_shipping_front_end_display_option_block',
            );

            return apply_filters('fcds_wcs_settings_accepted_fields', $fields);
        }
    }

    FCDS_F_WCS_Settings::init();
}