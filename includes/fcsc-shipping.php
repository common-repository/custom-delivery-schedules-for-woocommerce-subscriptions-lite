<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Shipping
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Shipping')){
    class FCDS_F_WCS_Shipping{
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
            // Add shipping cycle in order item
            add_action( 'fcsc_wcs_scheduled_shipping_cycle', array( __CLASS__, 'processShippingCycleOrder'), 10);
        }

        /**
         * Process shipping cycle orders
         *
         * @param $subscription_id integer
         * @return boolean
         * */
        public static function processShippingCycleOrder($subscription_id){
            $process_shipping_order = apply_filters('fcds_wcs_process_delivery_order', true, $subscription_id);
            if($process_shipping_order){
                $order_note = __( 'Processing order for delivery', FCDS_F_WCS_TEXT_DOMAIN );
                $required_status = apply_filters('fcds_wcs_required_subscription_status_for_processing_delivery_order', array('active', 'pending-cancel'), $subscription_id);
                $renewal_order = self::processShippingOrder( $subscription_id, $required_status, $order_note );

                // Backward compatibility with Subscriptions < 2.2.12 where we returned false for an unknown reason
                if ( false === $renewal_order ) {
                    return $renewal_order;
                }
            }
        }

        /**
         * Process renewal/shipping order for a subscription.
         *
         * @param $subscription_id integer
         * @param $required_status array/string
         * @param $order_note string
         * @return mixed
         */
        protected static function processShippingOrder( $subscription_id, $required_status, $order_note ) {

            $subscription = wcs_get_subscription( $subscription_id );

            // If the subscription is using manual payments, the gateway isn't active or it manages scheduled payments
            if ( ! empty( $subscription ) && $subscription->has_status( $required_status ) ) {
                $subscription->add_order_note($order_note);


                remove_filter( 'wcs_renewal_order_created', array( 'WC_Subscriptions_Renewal_Order', 'add_order_note' ), 10, 2 );

                // Generate a renewal order for payment gateways to use to record the payment (and determine how much is due)
                $shipping_order = wcs_create_renewal_order( $subscription );

                if ( is_wp_error( $shipping_order ) ) {
                    // let's try this again
                    $shipping_order = wcs_create_renewal_order( $subscription );

                    if ( is_wp_error( $shipping_order ) ) {
                        throw new Exception( sprintf( __( 'Error: Unable to create delivery order with note "%s"', FCDS_F_WCS_TEXT_DOMAIN ), $order_note ) );
                    }
                }

                $order_number = sprintf( _x( '#%s', 'hash before order number', FCDS_F_WCS_TEXT_DOMAIN ), $shipping_order->get_order_number() );

                // translators: placeholder is order ID
                $subscription->add_order_note( sprintf( __( 'Order %s created to record shipping.', FCDS_F_WCS_TEXT_DOMAIN ), sprintf( '<a href="%s">%s</a> ', esc_url( wcs_get_edit_post_link( wcs_get_objects_property( $shipping_order, 'id' ) ) ), $order_number ) ) );

                add_filter( 'wcs_renewal_order_created', array( 'WC_Subscriptions_Renewal_Order', 'add_order_note' ), 10, 2 );

                self::changeTheOrderAsSubscriptionShipping($shipping_order, $subscription);
                self::setOrderPriceAsZero($shipping_order);

                do_action('fcsc_wcs_before_change_shipping_order_status_to_complete', $shipping_order, $subscription);
//                $renewal_order->payment_complete();
                $change_order_status_to_complete = apply_filters('fcds_wcs_change_delivery_order_status_to_complete_on_process_delivery_cycles', true, $shipping_order);
                if($change_order_status_to_complete){
                    $shipping_order->set_status( apply_filters( 'woocommerce_payment_complete_order_status', $shipping_order->needs_processing() ? 'processing' : 'completed', $shipping_order->get_id(), $shipping_order ) );
                    $shipping_order->save();
                }

                //Send emails
                do_action( 'created_prepaid_shipping_order_email', $subscription, $shipping_order);
                do_action( 'created_prepaid_shipping_order_email_for_customer', $subscription, $shipping_order);

                do_action('fcds_wcs_after_change_delivery_order_status_to_complete', $shipping_order, $subscription);
            } else {
                $shipping_order = false;
            }

            return $shipping_order;
        }

        /**
         * Change the order as subscription shipping orders
         *
         * @param $shipping_order object
         * @param $subscription object
         * */
        protected static function changeTheOrderAsSubscriptionShipping($shipping_order, $subscription){
            delete_post_meta( $shipping_order->get_id(), '_fcsc_subscription_shipping');
            update_post_meta( $shipping_order->get_id(), '_fcsc_subscription_shipping', $subscription->get_id() );
        }

        /**
         * Set order price as zero
         *
         * @param $order object
         * */
        protected static function setOrderPriceAsZero($order){
            $total = 0;
            $set_item_price_for_all_orders = apply_filters('fcds_wcs_set_delivery_order_item_price_as_zero', true, $order);
            //If we set false order item price is as original price.
            if($set_item_price_for_all_orders){
                //For setting the item price as multiplied price based on recurring interval
                foreach( $order->get_items() as $item_id => $item ){
                    // Set the new price
                    $item->set_subtotal($total);
                    $item->set_total($total);
                    // Make new taxes calculations
                    $item->calculate_taxes();
                    $item->save(); // Save line item data
                }
            }

            $order->set_total($total);

            // UPM FIX, we enforce legacy total that is used in report pages
            $order->legacy_set_total($total, 'total');
            $order->legacy_set_total($total, 'tax');
            $order->legacy_set_total($total, 'shipping_tax');
            $order->legacy_set_total($total, 'shipping');
            $order->legacy_set_total($total, 'cart_discount_tax');
            $order->save();
        }

        /**
         * Get next delivery date
         *
         * @param $subscription_id int
         * @return string
         * */
        public static function getNextShippingDate($subscription_id){
            $pending_status = 'pending';
            if(class_exists('ActionScheduler_Store')) $pending_status = ActionScheduler_Store::STATUS_PENDING;
            $schedules = null;
            if(function_exists('as_get_scheduled_actions')){
                $schedules = as_get_scheduled_actions( array('hook' => 'fcsc_wcs_scheduled_shipping_cycle', 'args' => array('subscription_id' => $subscription_id), 'status' => $pending_status));
            } else if(function_exists('wc_get_scheduled_actions')){
                $schedules = wc_get_scheduled_actions( array('hook' => 'fcsc_wcs_scheduled_shipping_cycle', 'args' => array('subscription_id' => $subscription_id), 'status' => $pending_status));
            }
            $next_fulfillment_date = '-';
            if(!empty($schedules)){
                foreach ($schedules as $schedule){
                    $dates = $schedule->get_schedule()->next();
                    $timestamp = $dates->getTimestamp();
                    if(!empty($timestamp)){
                        $next_fulfillment_date = gmdate( 'Y-m-d H:i:s', $timestamp);
                        break;
                    }
                }
            }

            return $next_fulfillment_date;
        }

        /**
         * Get next delivery date
         *
         * @param $subscription_id int
         * @return string
         * */
        public static function getLastShippingDate($subscription_id){
            $pending_status = 'complete';
            if(class_exists('ActionScheduler_Store')) $pending_status = ActionScheduler_Store::STATUS_COMPLETE;//'orderby' => 'date'
            $schedules = null;
            if(function_exists('as_get_scheduled_actions')){
                $schedules = as_get_scheduled_actions( array('hook' => 'fcsc_wcs_scheduled_shipping_cycle', 'args' => array('subscription_id' => $subscription_id), 'status' => $pending_status, 'orderby' => 'date', 'order' => 'DESC'));
            } else if(function_exists('wc_get_scheduled_actions')){
                $schedules = wc_get_scheduled_actions( array('hook' => 'fcsc_wcs_scheduled_shipping_cycle', 'args' => array('subscription_id' => $subscription_id), 'status' => $pending_status, 'orderby' => 'date', 'order' => 'DESC'));
            }
            $next_fulfillment_date = '-';
            if(!empty($schedules)){
                foreach ($schedules as $schedule){
                    $dates = $schedule->get_schedule()->next();
                    $timestamp = $dates->getTimestamp();
                    if(!empty($timestamp)){
                        $next_fulfillment_date = gmdate( 'Y-m-d H:i:s', $timestamp);
                        break;
                    }
                }
            }

            return $next_fulfillment_date;
        }
    }

    FCDS_F_WCS_Shipping::init();
}