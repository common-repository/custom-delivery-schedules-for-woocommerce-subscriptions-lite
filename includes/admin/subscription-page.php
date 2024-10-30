<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Subscription_Page
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Subscription_Page')){
    class FCDS_F_WCS_Subscription_Page{
        protected static $action_scheduler;
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
            //For subscription list page
            add_filter('manage_edit-shop_subscription_columns', array(__CLASS__, 'subscriptionListTableHeader'), 100);
            add_action('manage_shop_subscription_posts_custom_column', array(__CLASS__, 'subscriptionListColumnContent'), 10, 2);

            add_action( 'add_meta_boxes', array(__CLASS__, 'metaBoxForShippingSchedule'), 26 );
        }

        /**
         * Subscription detail page meta box
         *
         * */
        public static function metaBoxForShippingSchedule(){
            add_meta_box( 'fcsc-wcs-subscription-schedule', _x( 'Delivery Schedule', 'meta box title', FCDS_F_WCS_TEXT_DOMAIN ), array(__CLASS__, 'metaBoxInSubscriptionPage'), 'shop_subscription', 'side', 'default' );
        }

        /**
         * Subscription detail page meta box
         *
         * @param $post int
         * */
        public static function metaBoxInSubscriptionPage($post){
            global $post, $the_subscription;

            if ( empty( $the_subscription ) ) {
                $the_subscription = wcs_get_subscription( $post->ID );
            }
            ?>
            <div class="fcsc-meta-con">
                <?php
                $has_shipping_cycle = FCDS_F_WCS_Helper::hasSubscriptionShippingCycle($the_subscription);
                if($has_shipping_cycle === true){
                    $shipping_cycle = $the_subscription->get_meta('_fcsc_wcs_shipping_cycle', true);
                    if(isset($shipping_cycle['period']) && isset($shipping_cycle['period_interval'])){
                        ?>
                        <div class="fcsc-meta-fields">
                            <strong><?php esc_html_e('Delivery schedule', FCDS_F_WCS_TEXT_DOMAIN); ?>:</strong>
                            <?php echo FCDS_F_WCS_Helper::getShippingCycleText($shipping_cycle['period'], $shipping_cycle['period_interval'], $shipping_cycle); ?>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="fcsc-meta-fields">
                        <strong><?php esc_html_e('Last delivery date', FCDS_F_WCS_TEXT_DOMAIN); ?>:</strong>
                        <?php self::subscriptionListColumnContent('last_shipping_cycle', $the_subscription->get_id()); ?>
                    </div>
                    <div class="fcsc-meta-fields">
                        <strong><?php esc_html_e('Next delivery date', FCDS_F_WCS_TEXT_DOMAIN); ?>:</strong>
                        <?php self::subscriptionListColumnContent('next_shipping_cycle', $the_subscription->get_id()); ?>
                    </div>
                    <div class="fcsc-meta-fields">
                        <a href="<?php echo FCDS_F_WCS_Helper::getScheduleListURL($the_subscription->get_id()); ?>" target="_blank"><?php esc_html_e('More info', FCDS_F_WCS_TEXT_DOMAIN); ?></a>
                    </div>
                    <?php
                } else {
                    ?>
                    <div class="fcsc-meta-fields">
                        <?php esc_html_e('This subscription doesn\'t contains delivery schedules', FCDS_F_WCS_TEXT_DOMAIN); ?></strong>
                    </div>
                    <?php
                }
                ?>
            </div>
            <?php
        }

        /**
         * For adding column titles in subscription list
         *
         * @param $defaults array
         * @return array
         * */
        public static function subscriptionListTableHeader($defaults){
            $defaults['last_shipping_cycle'] = esc_html__('Last delivery date', FCDS_F_WCS_TEXT_DOMAIN);
            $defaults['next_shipping_cycle'] = esc_html__('Next delivery date', FCDS_F_WCS_TEXT_DOMAIN);

            return $defaults;
        }

        /**
         * For adding column values in subscription list
         *
         * @param $column_name string
         * @param $post_id int
         * */
        public static function subscriptionListColumnContent($column_name, $post_id){
            if($column_name == 'last_shipping_cycle'){
                $next_shipping = FCDS_F_WCS_Shipping::getLastShippingDate($post_id);
                echo $next_shipping;
                if($next_shipping != '-'){
                    $next_timestamp = wcs_date_to_time($next_shipping);
                    if(empty(self::$action_scheduler)){
                        self::$action_scheduler = new FCDS_F_WCS_ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner());
                    }
                    $schedule_display_string = sprintf( __( ' (%s) ago', FCDS_F_WCS_TEXT_DOMAIN ), FCDS_F_WCS_ActionScheduler_ListTable::human_interval( gmdate( 'U' ) - $next_timestamp ) );
                    echo "<br>";
                    echo $schedule_display_string;
                }

            }
            if($column_name == 'next_shipping_cycle'){
                $next_shipping = FCDS_F_WCS_Shipping::getNextShippingDate($post_id);
                echo $next_shipping;
                if($next_shipping != '-'){
                    $next_timestamp = wcs_date_to_time($next_shipping);
                    if(empty(self::$action_scheduler)){
                        self::$action_scheduler = new FCDS_F_WCS_ActionScheduler_ListTable( ActionScheduler::store(), ActionScheduler::logger(), ActionScheduler::runner());
                    }
                    $schedule_display_string = sprintf( __( ' (%s)', FCDS_F_WCS_TEXT_DOMAIN ), FCDS_F_WCS_ActionScheduler_ListTable::human_interval( $next_timestamp - gmdate( 'U' ) ) );
                    echo "<br>";
                    echo $schedule_display_string;
                }

            }

        }
    }

    FCDS_F_WCS_Subscription_Page::init();
}