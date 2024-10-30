<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FlycartCustomShippingCycles
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // disable direct access
}

if(!class_exists('FlycartCustomShippingCycles')) {
    class FlycartCustomShippingCycles
    {
        private static $_instance = null;

        /**
         * Get the single instance
         */
        public static function instance() {
            if ( is_null( self::$_instance ) ) {
                self::$_instance = new self();
            }
            return self::$_instance;
        }

        /**
         * Constructor
         */
        private function __construct() {
            add_action( 'flycart_custom_delivery_cycles_loaded', array( $this, 'bootstrap' ), 9 );
        }

        /**
         * Bootstrap
         */
        public function bootstrap() {
            $this->define_constants();
            $this->init_hooks();
            $this->includes();
            if(FCDS_F_WCS_Purchase::is_pro()) $this->runUpdater();
        }

        /**
         * Define constants
         * */
        protected function define_constants(){
            if (!defined('FCDS_F_WCS_PLUGIN_PATH')) define( 'FCDS_F_WCS_PLUGIN_PATH', untrailingslashit(plugin_dir_path( __FILE__ )) );
            if (!defined('FCDS_F_WCS_PLUGIN_URL')) define( 'FCDS_F_WCS_PLUGIN_URL', plugins_url( '', __FILE__ ));

            if(!function_exists('get_plugin_data')){
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            $pluginDetails = get_plugin_data(plugin_dir_path(__FILE__).'custom-delivery-schedules-for-woocommerce-subscriptions.php');
            if (!defined('FCDS_F_WCS_VERSION')){
                define('FCDS_F_WCS_VERSION', $pluginDetails['Version']);
            }
        }

        /**
         * To include the files
         * */
        protected function includes(){
            require_once (dirname(__FILE__).'/includes/fcsc-purchase.php');
            require_once (dirname(__FILE__).'/includes/fcsc-helper.php');
            require_once (dirname(__FILE__).'/includes/fcsc-product.php');
            require_once (dirname(__FILE__).'/includes/fcsc-cart.php');
            require_once (dirname(__FILE__).'/includes/fcsc-order.php');
            require_once (dirname(__FILE__).'/includes/fcsc-shipping.php');
            require_once (dirname(__FILE__).'/includes/fcsc-subscription.php');
            require_once (dirname(__FILE__).'/includes/admin/product-fields.php');
            require_once (dirname(__FILE__).'/includes/admin/action-scheduler/action-scheduler-abstract-list-table.php');
            require_once (dirname(__FILE__).'/includes/admin/action-scheduler/action-scheduler-list-table.php');
            require_once (dirname(__FILE__).'/includes/admin/settings.php');
            require_once (dirname(__FILE__).'/includes/admin/admin-page.php');
            require_once (dirname(__FILE__).'/includes/admin/subscription-page.php');
            require_once (dirname(__FILE__).'/includes/emails/fcsc-email-manager.php');
            if(FCDS_F_WCS_Purchase::is_pro()){
                require_once (dirname(__FILE__).'/advanced/includes/admin/settings.php');
            }
        }

        /**
         * To run the updater
         * */
        protected function runUpdater()
        {
            $update_url = $this->getUpdateURL();
            if ($update_url != '') {
                try{
                $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
                    $update_url,
                    plugin_dir_path(__FILE__) . 'custom-delivery-schedules-for-woocommerce-subscriptions.php',
                    'custom-delivery-schedules-for-woocommerce-subscriptions'
                );
                } catch (Exception $e){}
            }
        }

        /**
         * To get update URL
         *
         * @return string
         * */
        protected function getUpdateURL(){
            $update_url = 'https://www.flycart.org/?wpaction=updatecheck&wpslug=custom-delivery-schedules&pro=0&dlid=';
            $licenceKey = FCDS_F_WCS_Helper::getOption('fcsc_shipping_licence_key', '');
            $update_url .= $licenceKey;
            if($licenceKey == ''){
                add_action( 'admin_notices', array($this, 'errorNoticeInAdminPagesToEnterLicenceKey'));
                return '';
            }
            return $update_url;
        }

        /**
         * To display error message in admin page while there is no licence key
         * */
        public function errorNoticeInAdminPagesToEnterLicenceKey(){
            $notice_on_for_user = get_user_meta( get_current_user_id(), 'dismissed_custom_shipping_cycles_for_woocommerce_subscription_admin_installed_notice', true );
            if(!$notice_on_for_user){
                $htmlPrefix = '<div class="updated woocommerce-message"><p>';
                $htmlSuffix = '</p></div>';
                $msg = "<strong>";
                $msg .= __("Custom Delivery Schedules for WooCommerce Subscriptions installed", FCDS_F_WCS_TEXT_DOMAIN);
                $msg .= "</strong>";
                $msg .= __(" - Make sure to activate your copy of the plugin to receive updates, support and security fixes!", FCDS_F_WCS_TEXT_DOMAIN);
                $msg .= '<p>';
                $msg .= '<a href="'.FCDS_F_WCS_Helper::getSettingsURL().'" class="button-primary">';
                $msg .= __('Settings', FCDS_F_WCS_TEXT_DOMAIN);
                $msg .= '</a></p>';
                $msg .= '<a href="'.esc_url( wp_nonce_url( add_query_arg( 'fcscfwcs-hide-notice', 'installed' ), 'custom_shipping_cycles_for_woocommerce_subscription_hide_notices_nonce', '_fcscfwcs_notice_nonce' ) ).'" class="fcscfwcs-notice-a notice-dismiss"><span class="screen-reader-text">'.__('Dismiss this notice.', FCDS_F_WCS_TEXT_DOMAIN).'</span></a>';
                echo $htmlPrefix.$msg.$htmlSuffix;
            }
        }

        /**
         * To include the styles
         * */
        public function includeScriptAndStyles(){
            wp_register_style('flycart_csc_wcs_style', FCDS_F_WCS_PLUGIN_URL . '/assets/css/custom_shipping_cycle.css', array(), FCDS_F_WCS_VERSION);
            wp_enqueue_style('flycart_csc_wcs_style');
            wp_register_script('flycart_csc_wcs_script', FCDS_F_WCS_PLUGIN_URL . '/assets/js/custom-shipping-cycles.js', array('jquery'), FCDS_F_WCS_VERSION, true);
            wp_enqueue_script('flycart_csc_wcs_script');
        }

        /**
         * To include the styles for admin
         * */
        public function includeScriptAndStylesForAdmin(){
            wp_register_script('flycart_csc_wcs_admin_script', FCDS_F_WCS_PLUGIN_URL . '/assets/js/admin.js', array('jquery'), FCDS_F_WCS_VERSION, true);
            wp_enqueue_script('flycart_csc_wcs_admin_script');
            $localization_data = FCDS_F_WCS_Helper::getLocalizationData();
            wp_localize_script( 'flycart_csc_wcs_admin_script', 'flycart_csc_wcs_localization', $localization_data);
            wp_register_style('flycart_csc_wcs_style_admin', FCDS_F_WCS_PLUGIN_URL . '/assets/css/custom_shipping_cycle_admin.css', array(), FCDS_F_WCS_VERSION);

            wp_enqueue_script( 'woocommerce_admin' );
            wp_enqueue_script( 'wc-enhanced-select' );

            wp_enqueue_style('flycart_csc_wcs_style_admin');
            //To load woocommerce product select
            wp_enqueue_style( 'woocommerce_admin_styles' );
        }

        /**
         * Add additional links in plugin page
         *
         * @param $links array
         * @return array
         * */
        public function addActionLinksInPluginPage($links){
            $additional_links = array(
                '<a href="' . FCDS_F_WCS_Helper::getSettingsURL() . '">'.esc_html__('Settings', FCDS_F_WCS_TEXT_DOMAIN).'</a>',
            );

            return array_merge( $additional_links, $links );
        }

        /**
         * To Init hooks
         * */
        protected function init_hooks(){
            add_action( 'wp_enqueue_scripts', array($this, 'includeScriptAndStyles') );
            add_action( 'admin_enqueue_scripts', array($this, 'includeScriptAndStylesForAdmin') );

            add_filter('woocommerce_screen_ids', function($screen_ids){
                $screen_ids[] = 'woocommerce_page_fcsc-custom-shipping';
                return $screen_ids;
            });

            add_filter( 'plugin_action_links_' . FCDS_F_WCS_PLUGIN_BASENAME, array($this, 'addActionLinksInPluginPage') );
        }
    }

    FlycartCustomShippingCycles::instance();
}