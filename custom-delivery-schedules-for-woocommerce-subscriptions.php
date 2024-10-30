<?php
/**
 * Plugin Name: Custom Delivery Schedules Lite for WooCommerce Subscriptions
 * Plugin URI: http://www.flycart.org/
 * Description: Custom Delivery Schedules for WooCommerce Subscriptions in your WooCommerce Store.
 * Author: Flycart Technologies LLP
 * Author URI: https://www.flycart.org
 * Version: 1.0.2
 * Slug: custom-delivery-schedules-for-woocommerce-subscriptions-lite
 * Text Domain: custom-delivery-schedules-for-woocommerce-subscriptions-lite
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.6.1
 * WC requires at least: 3.0
 * WC tested up to: 3.8
 * WCS requires at least: 2.0
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!function_exists('get_plugin_data')){
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
if (!defined('FCDS_F_WCS_PRO')) define( 'FCDS_F_WCS_PRO', true );
if (!defined('FCDS_F_WCS_PLUGIN_BASENAME')) define( 'FCDS_F_WCS_PLUGIN_BASENAME', plugin_basename(__FILE__));
if (!defined('FCDS_F_WCS_PLUGIN_DIR')) define('FCDS_F_WCS_PLUGIN_DIR', untrailingslashit(plugin_dir_path(__FILE__)));
if (!defined('FCDS_F_WCS_TEXT_DOMAIN')) define('FCDS_F_WCS_TEXT_DOMAIN', 'custom-delivery-schedules-for-woocommerce-subscriptions-lite');

require_once (dirname(__FILE__).'/includes/admin/admin-notices.php');

/**
 * Check if WooCommerce is active
 **/
if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

    /**
     * Check if WooCommerce Subscriptions is active
     **/
    if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
        require_once __DIR__ . '/vendor/autoload.php';
        require_once (dirname(__FILE__).'/loader.php');

        do_action('flycart_custom_delivery_cycles_loaded');
    } else {
        add_action( 'admin_notices', 'FCDS_F_WCS_AdminNotices::warningOnWooCommerceSubscriptionPluginNotFound');
    }
} else {
    add_action( 'admin_notices', 'FCDS_F_WCS_AdminNotices::warningOnWooCommercePluginNotFound');
}