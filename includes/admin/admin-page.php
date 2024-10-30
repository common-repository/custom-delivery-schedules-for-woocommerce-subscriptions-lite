<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Admin_Page
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Admin_Page')){
    class FCDS_F_WCS_Admin_Page{

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
                add_action( 'admin_menu', array(__CLASS__, 'adminMenu') );
            }
        }

        /**
         * For adding admin menew
         * */
        public static function adminMenu(){
            global $submenu;
            if (isset($submenu['woocommerce'])) {
                add_submenu_page(
                    'woocommerce',
                    FCDS_F_WCS_Purchase::get_title(),
                    FCDS_F_WCS_Purchase::get_title(),
                    'edit_posts',
                    'fcsc-custom-shipping',
                    array(__CLASS__, 'loadCustomShippingDashboard')
                );
            }
        }

        public static function loadCustomShippingDashboard(){
            $url = 'admin.php?page=fcsc-custom-shipping';
            $tab = isset($_REQUEST['tab'])? $_REQUEST['tab']: 'general';
            if(empty($tab)){
                $tab = 'general';
            }
            ?>
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="<?php echo admin_url($url); ?>" class="nav-tab<?php echo ($tab == 'general')? ' nav-tab-active': ''; ?>"><?php esc_html_e('Delivery schedules', FCDS_F_WCS_TEXT_DOMAIN); ?></a>
                <a href="<?php echo admin_url($url.'&tab=settings'); ?>" class="nav-tab<?php echo ($tab == 'settings')? ' nav-tab-active': ''; ?>"><?php esc_html_e('Settings', FCDS_F_WCS_TEXT_DOMAIN); ?></a>
                <span class="fcds-version-text"><?php echo "v".FCDS_F_WCS_VERSION; ?></span>
            </nav>
            <?php
            if($tab == 'general'){
                self::loadScheduledActionList();
            } else {
                FCDS_F_WCS_Settings::loadSettingsPage();
            }
        }

        /**
         * Load scheduled actions list
         * */
        public static function loadScheduledActionList(){
            $table = new FCDS_F_WCS_ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner(), 'fcsc_wcs_scheduled_shipping_cycle');
            $table->display_page();
        }
    }

    FCDS_F_WCS_Admin_Page::init();
}