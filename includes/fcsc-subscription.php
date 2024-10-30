<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Subscription
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Subscription')){
    class FCDS_F_WCS_Subscription{

        // strtotime() only handles English, so can't use $wp_locale->weekday in some places
        protected static $weekdays = array(
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        );

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
            // on create subscription
            add_action( 'woocommerce_checkout_create_subscription', array( __CLASS__, 'createShippingCycleSchedulesForSubscription'), 10, 2);
            //On subscription date updated
            add_action( 'woocommerce_subscription_date_updated', array( __CLASS__, 'createShippingCycleSchedulesOnSubscriptionDateChanged'), 10, 3);

            //Add shipping order type in filter(in order list) drop down
            add_filter('woocommerce_subscriptions_order_type_dropdown', array( __CLASS__, 'addShippingOrderTypeInFilterDropDown'));
            //Change shipping order type meta for filter order list
            add_filter('woocommerce_subscriptions_admin_order_type_filter_meta_key', array( __CLASS__, 'changeShippingOrderTypeMetaForFilterOrderList'), 10, 2);
            //Create shipping order on subscription/renewal payment complete
            add_action( 'woocommerce_subscription_payment_complete', array( __CLASS__, 'createShippingOrderOnPaymentComplete'), 10);

            //To change the order relationship in related order list
            add_filter( 'woocommerce_subscriptions_admin_related_orders_to_display', array( __CLASS__, 'changeOrderRelationshipTextForShippingOrders'), 10, 3 );

            //Delete the scheduled jobs when subscription is deleted
            add_action('woocommerce_subscription_deleted', array( __CLASS__, 'onAfterDeleteSubscription'));
        }

        /**
         * Delete jobs after subscription deleted
         * */
        public static function onAfterDeleteSubscription($post_id){
            $post_status = array('trash', 'publish');
            foreach ($post_status as $status){
                $args = array('post_type' => 'scheduled-action', 'numberposts' => '-1',
                    'meta_key'         => '_fcsc_subscription_id',
                    'meta_value'       => $post_id,
                    'post_status'       => $status,
                );

                $posts = get_posts($args);
                if(!empty($posts) && is_array($posts)){
                    foreach ($posts as $post){
                        if(!empty($post->ID)){
                            wp_delete_post($post->ID, true);
                        }
                    }
                }
            }
        }

        /**
         * Change the order relationship text for shipping orders
         *
         * @param $orders array
         * @param $subscriptions object
         * @param $post object
         * @return array
         * */
        public static function changeOrderRelationshipTextForShippingOrders($orders, $subscriptions, $post){
            foreach ( $orders  as $key => $order) {
                $is_subscription_shipping_order = $order->get_meta('_fcsc_subscription_shipping', true);
                // only process if it is an non shipping order
                if(!empty($is_subscription_shipping_order) && $is_subscription_shipping_order){
                    wcs_set_objects_property( $order, 'relationship', _x( 'Delivery Order', 'relation to order', FCDS_F_WCS_TEXT_DOMAIN ), 'set_prop_only' );
                    $orders[$key] = $order;
                }

            }

            return $orders;
        }

        /**
         * Create shipping order on subscription/renewal payment complete
         *
         * @param $subscription object
         * */
        public static function createShippingOrderOnPaymentComplete($subscription){
            $is_manual = $subscription->is_manual();
            if($is_manual === false){
                $last_order = $subscription->get_last_order( 'all', 'any' );
                if ( false !== $last_order) {
                    $create_shipping_order = apply_filters('fcds_wcs_create_delivery_schedules_on_subscription_order_complete', true, $subscription, $last_order);
                    if($create_shipping_order === true){
                        $is_subscription_shipping_order = $last_order->get_meta('_fcsc_subscription_shipping', true);
                        // only process if it is an non shipping order
                        if(empty($is_subscription_shipping_order)){
                            $has_shipping_cycle = FCDS_F_WCS_Helper::hasSubscriptionShippingCycle($subscription);
                            if($has_shipping_cycle === true){
                                $created_initial_shipping_order = $subscription->get_meta('_fcsc_wcs_created_initial_shipping_order_'.$last_order->get_id(), true);
                                if((int)$created_initial_shipping_order !== 1){
                                    $shipping_cycle = $subscription->get_meta('_fcsc_wcs_shipping_cycle', true);
                                    if(isset($shipping_cycle['period']) && isset($shipping_cycle['period_interval'])){
                                        $renewal = false;
                                        if(wcs_order_contains_renewal( $last_order )){
                                            $renewal = true;
                                        }
                                        $period = $shipping_cycle['period'];
                                        if($renewal){
                                            $start_on = $subscription->get_meta('_fcsc_wcs_shipping_cycle_last_scheduled_for_end_date', true);
                                            if(empty($start_on)){
                                                $start_on = $subscription->get_meta('_fcsc_wcs_shipping_cycle_scheduled_until_date', true);
                                            }
                                        } else {
                                            $start_on = $subscription->get_date('start');
                                        }
                                        $start_on = apply_filters('fcds_wcs_create_schedule_start_from', $start_on, $subscription, $renewal);
                                        $last_payment_time = max( wcs_date_to_time( $subscription->get_date('last_order_date_created') ), wcs_date_to_time( $subscription->get_date('last_order_date_paid') ) );
                                        if($last_payment_time > wcs_date_to_time($start_on)){
                                            $start_on = gmdate( 'Y-m-d H:i:s', $last_payment_time );
                                        }
                                        $has_synchronise = self::hasSynchronise($shipping_cycle);
                                        if($has_synchronise){
                                            $first_shipping_date = self::calculateFirstShippingDate($period, $start_on, $shipping_cycle);
                                            $first_shipping_date_only_date = gmdate( 'Y-m-d', wcs_date_to_time($first_shipping_date));
                                            $shipping_cycle_from_date = gmdate( 'Y-m-d', wcs_date_to_time($start_on));
                                            if($shipping_cycle_from_date === $first_shipping_date_only_date){
                                                //schedule an shipping order
                                                self::addScheduledActionForShippingCycle($subscription, $last_payment_time, true);
                                            }
                                        } else {
                                            //schedule an shipping order
                                            self::addScheduledActionForShippingCycle($subscription, $last_payment_time, true);
                                        }

                                        update_post_meta($subscription->get_id(), '_fcsc_wcs_created_initial_shipping_order_'.$last_order->get_id(), 1, true);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Change shipping order type meta for filter the order list
         *
         * @param $meta_key string
         * @param $shop_order_subtype string
         * @return string
         * */
        public static function changeShippingOrderTypeMetaForFilterOrderList($meta_key, $shop_order_subtype){
            if($shop_order_subtype === 'fcsc_subscription_shipping'){
                $meta_key = '_fcsc_subscription_shipping';
            }

            return $meta_key;
        }

        /**
         * Add shipping order type in order list drop down
         *
         * @param $order_types array
         * @return array
         * */
        public static function addShippingOrderTypeInFilterDropDown($order_types){
            $order_types['fcsc_subscription_shipping'] = esc_html__('Subscription shipping', FCDS_F_WCS_TEXT_DOMAIN);

            return $order_types;
        }

        /**
         * Create sipping cycle on subscription date changed
         *
         * @param $subscription object
         * @param $date_type string
         * @param $datetime string
         * */
        public static function createShippingCycleSchedulesOnSubscriptionDateChanged($subscription, $date_type, $datetime){
            if(in_array($date_type, array('next_payment', 'end'))){
                $has_shipping_cycle = FCDS_F_WCS_Helper::hasSubscriptionShippingCycle($subscription);
                if($has_shipping_cycle === true){
                    $subscription_id = $subscription->get_id();
                    $last_scheduled_for_end_date = $subscription->get_meta('_fcsc_wcs_shipping_cycle_last_scheduled_for_end_date', true);
                    $next_shipping_cycle_ends = $subscription->get_date('next_payment');
                    if(empty($next_shipping_cycle_ends)){
                        $next_shipping_cycle_ends = $subscription->get_date('end');
                    }
                    if(!empty($next_shipping_cycle_ends)){
                        $next_shipping_cycle_ends_datetime = wcs_date_to_time($next_shipping_cycle_ends);
                        $last_scheduled_for_end_datetime = wcs_date_to_time($last_scheduled_for_end_date);
                        if(gmdate( 'Y-m-d', $next_shipping_cycle_ends_datetime ) != gmdate( 'Y-m-d', $last_scheduled_for_end_datetime )){
                            if($next_shipping_cycle_ends_datetime > $last_scheduled_for_end_datetime){
                                $shipping_cycle = $subscription->get_meta('_fcsc_wcs_shipping_cycle', true);
                                if(isset($shipping_cycle['period']) && isset($shipping_cycle['period_interval'])){
                                    $period = $shipping_cycle['period'];
                                    $period_interval = $shipping_cycle['period_interval'];
                                    self::createShippingCycleSchedules($subscription, $period, $period_interval, $shipping_cycle, true);
                                }
                            } else {
                                self::cancelSchedulesCreatedHigherThanEndDate($subscription);
                                update_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_last_scheduled_for_end_date', $next_shipping_cycle_ends);
                                $last_scheduled_date = self::getLastScheduleDate($subscription);
                                if(!empty($last_scheduled_date)){
                                    update_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_scheduled_until_date', gmdate( 'Y-m-d H:i:s', wcs_date_to_time($last_scheduled_date)));
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * Get last schedule date
         *
         * @param $subscription object
         * @return string
         * */
        protected static function getLastScheduleDate($subscription){
            $args = array('subscription_id' => $subscription->get_id());
            $job_id = FCDS_F_WCS_Helper::getLastScheduleJobId($args);
            if(!empty($job_id)){
                $post = get_post($job_id);
                if(!empty($post) && !empty($post->post_date)){
                    return $post->post_date;
                }
            }

            return '';
        }

        /**
         * Cancel the schedules created higher than end date
         *
         * @param $subscription object
         * */
        public static function cancelSchedulesCreatedHigherThanEndDate($subscription){
            $next_shipping_cycle_ends = $subscription->get_date('next_payment');
            if(empty($next_shipping_cycle_ends)){
                $next_shipping_cycle_ends = $subscription->get_date('end');
            }
            if(!empty($next_shipping_cycle_ends)){
                $args = array('subscription_id' => $subscription->get_id());
                $schedules = FCDS_F_WCS_Helper::getSchedulesFromDate($next_shipping_cycle_ends, $args);
                if(!empty($schedules) && is_array($schedules)){
                    foreach ($schedules as $job_id){
                        if(!empty($job_id)){
                            FCDS_F_WCS_Helper::cancelScheduledJob($job_id);
                        }
                    }
                }
            }
        }

        /**
         * Create shipping cycle schedules for the subscription
         *
         * @param object $subscription
         * @param array $posted_data
         * */
        public static function createShippingCycleSchedulesForSubscription($subscription, $posted_data){
            $has_shipping_cycle = FCDS_F_WCS_Helper::hasSubscriptionShippingCycle($subscription);
            if($has_shipping_cycle === true){
                $shipping_cycle = $subscription->get_meta('_fcsc_wcs_shipping_cycle', true);
                if(isset($shipping_cycle['period']) && isset($shipping_cycle['period_interval'])){
                    $period = $shipping_cycle['period'];
                    $period_interval = $shipping_cycle['period_interval'];
                    self::createShippingCycleSchedules($subscription, $period, $period_interval, $shipping_cycle);
                }
            }
        }

        /**
         * Create shipping cycle schedules for the subscription
         *
         * @param $subscription object
         * @param $period string
         * @param $period_interval integer
         * @param $shipping_cycle array
         * @param $renewal boolean
         * */
        protected static function createShippingCycleSchedules($subscription, $period, $period_interval, $shipping_cycle = array(), $renewal = false){
            $subscription_duplicate = null;
            $subscription_duplicate = clone $subscription;
            $subscription_duplicate->set_object_read(false);
            $next_shipping_cycle_ends = $subscription->get_date('next_payment');
            if($renewal){
                $start_on = $subscription->get_meta('_fcsc_wcs_shipping_cycle_last_scheduled_for_end_date', true);
                if(empty($start_on)){
                    $start_on = $subscription->get_meta('_fcsc_wcs_shipping_cycle_scheduled_until_date', true);
                }
            } else {
                $start_on = $subscription->get_date('start');
            }
            $start_on = apply_filters('fcds_wcs_create_schedule_start_from', $start_on, $subscription, $renewal);
            $last_payment_time = max( wcs_date_to_time( $subscription->get_date('last_order_date_created') ), wcs_date_to_time( $subscription->get_date('last_order_date_paid') ) );
            if($last_payment_time > wcs_date_to_time($start_on)){
                $start_on = gmdate( 'Y-m-d H:i:s', $last_payment_time );
            }
            if(empty($next_shipping_cycle_ends)){
                $next_shipping_cycle_ends = $subscription->get_date('end');
            }
            if(!empty($next_shipping_cycle_ends)){
                $i = 1;
                $next_shipping_date = '';
                $shipping_cycle_from_date = gmdate( 'Y-m-d', wcs_date_to_time($start_on));
                $has_synchronise = self::hasSynchronise($shipping_cycle);
                if($has_synchronise === true){
                    $start_on = $first_shipping_date = self::calculateFirstShippingDate($period, $start_on, $shipping_cycle);
                    $first_shipping_date_only_date = gmdate( 'Y-m-d', wcs_date_to_time($first_shipping_date));
                    if($shipping_cycle_from_date !== $first_shipping_date_only_date){
                        $next_shipping_date_in_time = wcs_date_to_time($start_on);
                        self::addScheduledActionForShippingCycle($subscription, $next_shipping_date_in_time);
                    }
                }
                while ( $next_shipping_date !== 0 ) {
                    $period_interval_new = $period_interval*$i;
                    $next_shipping_date = self::calculateNextShippingDate($period, $period_interval_new, $next_shipping_cycle_ends, $start_on);
                    if($next_shipping_date !== 0){
                        $next_shipping_date_in_time = wcs_date_to_time( $next_shipping_date );
                        self::addScheduledActionForShippingCycle($subscription, $next_shipping_date_in_time);
                    }
                    $i++;
                }
                $subscription_id = $subscription->get_id();
                update_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_last_scheduled_for_start_date', $start_on);
                update_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_last_scheduled_for_end_date', $next_shipping_cycle_ends);
            }
        }

        /**
         * Add scheduled action for shipping cycle
         *
         * @param $subscription object
         * @param $next_shipping_date_in_time string
         * @return integer
         * */
        protected static function addScheduledActionForShippingCycle($subscription, $next_shipping_date_in_time, $first_order = false){
            $subscription_id = $subscription->get_id();
            $schedule_exists = self::checkAnyActiveScheduleExists($subscription_id, $next_shipping_date_in_time);
            if($schedule_exists === false){
                $job_id = as_schedule_single_action($next_shipping_date_in_time, 'fcsc_wcs_scheduled_shipping_cycle', array('subscription_id' => $subscription_id));
                if($job_id){
                    if($first_order !== true) update_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_scheduled_until_date', gmdate( 'Y-m-d H:i:s', $next_shipping_date_in_time));
                    self::addScheduledShippingCycleJobIdForSubscription($subscription_id, $job_id);
                    self::addSubscriptionDetailsForTheSchedule($job_id, $subscription, $next_shipping_date_in_time);
                }
            } else {
                $job_id = $schedule_exists;
            }

            return $job_id;
        }

        /**
         * Check Any active schedules already exists
         *
         * @param $subscription_id integer
         * @param $date_and_time string
         *
         * @return boolean
         * */
        protected static function checkAnyActiveScheduleExists($subscription_id, $date_and_time){
            $schedule_date = date( 'Y-m-d', $date_and_time);
            $data = array(
                'post_title' => 'fcsc_wcs_scheduled_shipping_cycle',
                'post_status' => 'pending',
                'post_type' => 'scheduled-action',
                'date_query' => array(
                    'after' => $schedule_date,
                    'before' => $schedule_date,
                    'inclusive' => true,
                ),
                'meta_key' => '_fcsc_subscription_id',
                'meta_value' => $subscription_id,
            );
            $posts = get_posts($data);
            if(!empty($posts)){
                if(count($posts) > 0){
                    if(isset($posts[0]['ID']) && !empty($posts[0]['ID'])){
                        return $posts[0]['ID'];
                    }
                }
            }

            return false;
        }

        /**
         * Has synchronise option
         *
         * @param $shipping_cycle array
         * @return boolean
         * */
        protected static function hasSynchronise($shipping_cycle){
            if(isset($shipping_cycle['period'])){
                if(in_array($shipping_cycle['period'], array('week', 'month', 'year'))){
                    if($shipping_cycle['period'] === 'year'){
                        if(isset($shipping_cycle['sync_year_day']) && isset($shipping_cycle['sync_year'])){
                            if(((int)$shipping_cycle['sync_year_day']) > 0){
                                return true;
                            }
                        }
                    } else {
                        if(isset($shipping_cycle['sync_week_month'])){
                            if(((int)$shipping_cycle['sync_week_month']) > 0){
                                return true;
                            }
                        }
                    }
                }
            }

            return false;
        }

        /**
         * Add scheduled shipping cycle Job id for subscription
         *
         * @param integer $subscription_id
         * @param integer $job_id
         * */
        protected static function addScheduledShippingCycleJobIdForSubscription($subscription_id, $job_id){
            add_post_meta( $subscription_id, '_fcsc_wcs_shipping_cycle_scheduled_job_id', $job_id);
        }

        /**
         * Add subscription details for the schedule
         *
         * @param integer $job_id
         * @param object $subscription
         * */
        protected static function addSubscriptionDetailsForTheSchedule($job_id, $subscription, $next_shipping_date_in_time){
            add_post_meta( $job_id, '_fcsc_subscription_id', $subscription->get_id(), true);
            add_post_meta( $job_id, '_shipping_date_in_time', $next_shipping_date_in_time, true);
            $product_id = $subscription->get_meta( '_fcsc_wcs_shipping_product_id', true);
            $email = $subscription->get_billing_email();
            $user_id = $subscription->get_user_id();
            add_post_meta( $job_id, '_product_id', $product_id, true);
            add_post_meta( $job_id, '_billing_email', $email, true);
            add_post_meta( $job_id, '_user_id', $user_id, true);
        }

        /**
         * Calculate next delivery date
         *
         * @param $period string
         * @param $period_interval integer
         * @param $next_shipping_cycle_ends string
         * @param $start_on string
         * @return mixed
         * */
        protected static function calculateNextShippingDate($period, $period_interval, $next_shipping_cycle_ends, $start_on){
            $next_shipping_date = 0;
            $start_time = wcs_date_to_time( $start_on );
            $end_time = wcs_date_to_time( $next_shipping_cycle_ends );
            $from_timestamp = $start_time;
            $next_shipping_timestamp = wcs_add_time( $period_interval, $period, $from_timestamp );
            // If the subscription has an end date and the next billing period comes after that, return 0
            if ( 0 != $end_time && ( $next_shipping_timestamp + 23 * HOUR_IN_SECONDS ) > $end_time ) {
                $next_shipping_timestamp = 0;
            }

            if ( $next_shipping_timestamp > 0 ) {
                $next_shipping_date = gmdate( 'Y-m-d H:i:s', $next_shipping_timestamp );
            }

            return $next_shipping_date;
        }

        /**
         * Calculate next delivery date
         *
         * @param $period string
         * @param $start_on string
         * @param $shipping_cycle array
         * @param $type string
         * @return mixed
         * */
        protected static function calculateFirstShippingDate($period, $start_on, $shipping_cycle, $type = 'mysql'){
            $from_date = $start_on;
            $from_timestamp = wcs_date_to_time( $from_date ) + ( get_option( 'gmt_offset' ) * 3600 ); // Site time
            if(in_array($period, array('week', 'month'))){
                $payment_day = isset($shipping_cycle['sync_week_month'])? $shipping_cycle['sync_week_month']: 0;
            } else {
                $payment_day['day'] = isset($shipping_cycle['sync_year_day'])? $shipping_cycle['sync_year_day']: 0;
                $payment_day['month'] = isset($shipping_cycle['sync_year'])? $shipping_cycle['sync_year']: 1;
            }

            if ( 'week' == $period ) {

                // strtotime() will figure out if the day is in the future or today (see: https://gist.github.com/thenbrent/9698083)
                $first_payment_timestamp = wcs_strtotime_dark_knight( self::$weekdays[ $payment_day ], $from_timestamp );

            } elseif ( 'month' == $period ) {

                // strtotime() needs to know the month, so we need to determine if the specified day has occured this month yet or if we want the last day of the month (see: https://gist.github.com/thenbrent/9698083)
                if ( $payment_day > 27 ) { // we actually want the last day of the month

                    $payment_day = gmdate( 't', $from_timestamp );
                    $month       = gmdate( 'F', $from_timestamp );

                } elseif ( gmdate( 'j', $from_timestamp ) > $payment_day ) { // today is later than specified day in the from date, we need the next month

                    $month = gmdate( 'F', wcs_add_months( $from_timestamp, 1 ) );

                } else { // specified day is either today or still to come in the month of the from date

                    $month = gmdate( 'F', $from_timestamp );

                }

                $first_payment_timestamp = wcs_strtotime_dark_knight( "{$payment_day} {$month}", $from_timestamp );

            } elseif ( 'year' == $period ) {

                // We can't use $wp_locale here because it is translated
                switch ( $payment_day['month'] ) {
                    case 1 :
                        $month = 'January';
                        break;
                    case 2 :
                        $month = 'February';
                        break;
                    case 3 :
                        $month = 'March';
                        break;
                    case 4 :
                        $month = 'April';
                        break;
                    case 5 :
                        $month = 'May';
                        break;
                    case 6 :
                        $month = 'June';
                        break;
                    case 7 :
                        $month = 'July';
                        break;
                    case 8 :
                        $month = 'August';
                        break;
                    case 9 :
                        $month = 'September';
                        break;
                    case 10 :
                        $month = 'October';
                        break;
                    case 11 :
                        $month = 'November';
                        break;
                    case 12 :
                        $month = 'December';
                        break;
                }

                $first_payment_timestamp = wcs_strtotime_dark_knight( "{$payment_day['day']} {$month}", $from_timestamp );
            }

            // Make sure the next payment is in the future and after the $from_date, as strtotime() will return the date this year for any day in the past when adding months or years (see: https://gist.github.com/thenbrent/9698083)
            if ( 'year' == $period || 'month' == $period ) {

                // First make sure the day is in the past so that we don't end up jumping a month or year because of a few hours difference between now and the billing date
                if ( gmdate( 'Ymd', $first_payment_timestamp ) < gmdate( 'Ymd', $from_timestamp ) || gmdate( 'Ymd', $first_payment_timestamp ) < gmdate( 'Ymd', current_time( 'timestamp' ) ) ) {
                    $i = 1;
                    // Then make sure the date and time of the payment is in the future
                    while ( ( $first_payment_timestamp < gmdate( 'U' ) || $first_payment_timestamp < $from_timestamp ) && $i < 30 ) {
                        $first_payment_timestamp = wcs_add_time( 1, $period, $first_payment_timestamp );
                        $i = $i + 1;
                    }
                }
            }

            // We calculated a timestamp for midnight on the specific day in the site's timezone, let's push it to 3am to account for any daylight savings changes
            $first_payment_timestamp += apply_filters( 'fcds_wcs_synchronise_first_delivery_date_additional_hours', 3 * HOUR_IN_SECONDS, $period, $start_on, $shipping_cycle, $type );

            // And convert it to the UTC equivalent of 3am on that day
            //$first_payment_timestamp -= ( get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );

            $first_payment = ( 'mysql' == $type && 0 != $first_payment_timestamp ) ? gmdate( 'Y-m-d H:i:s', $first_payment_timestamp ) : $first_payment_timestamp;

            return apply_filters( 'fcds_wcs_synchronise_first_delivery_date', $first_payment, $period, $start_on, $shipping_cycle, $type );
        }
    }

    FCDS_F_WCS_Subscription::init();
}