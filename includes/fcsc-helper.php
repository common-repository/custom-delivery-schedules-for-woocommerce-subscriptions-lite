<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_Helper
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(!class_exists('FCDS_F_WCS_Helper')){
    class FCDS_F_WCS_Helper {

        protected static $delivery_option_details_tmp_path = '';

        /**
         * Check is subscription product
         *
         * @param $product object
         * @return boolean
         */
        public static function is_subscription($product) {
            return WC_Subscriptions_Product::is_subscription($product);
        }

        /**
         * Check if payment syncing is enabled on the store.
         *
         * @since 1.5
         */
        public static function is_syncing_enabled() {
            return 'yes' === get_option( 'woocommerce_subscriptions_sync_payments', 'no' );
        }

        /**
         * Check the page is an product list page
         *
         * @return boolean
         */
        public static function is_product_list_page(){
            return (is_product())? false: true;
        }

        /**
         * Get Product
         *
         * @param $product_id int
         * @return object
         */
        public static function get_product($product_id){
            return wc_get_product($product_id);
        }

        /**
         * Get Product URL
         *
         * @param $product object
         * @return string
         */
        public static function get_product_url($product){
            return get_permalink( $product->get_id() );
        }

        /**
         * Is shipping option enabled for subscription product
         *
         * @param $product object
         * @return boolean
         */
        public static function is_enabled_shipping_options($product){
            $enabled_shipping_options = $product->get_meta('fcsc_wcs_enable_shipping_options');
            return ($enabled_shipping_options === "1")? true: false;
        }

        /**
         * Use only shipping option for subscription product
         *
         * @param $product object
         * @return boolean
         */
        public static function use_as_shipping_product($product){
            $use_only_shipping_options = $product->get_meta('fcsc_wcs_use_only_shipping_cycle');
            return ($use_only_shipping_options === "1")? true: false;
        }

        /**
         * Get shipping cycle from subscription product
         *
         * @param $product object
         * @return boolean
         */
        public static function getShippingCycles($product){
            $delivery_options = $product->get_meta('fcsc_wcs_shipping_options');
            if(!(FCDS_F_WCS_Purchase::is_pro())) {
                if(!empty($delivery_options) && is_array($delivery_options)){
                    $delivery_options = array_values($delivery_options);
                    $delivery_options = array($delivery_options[0]);
                }
            }

            return $delivery_options;
        }

        /**
         * has shipping cycle for subscription
         *
         * @param $subscription object
         * @return boolean
         */
        public static function hasSubscriptionShippingCycle($subscription){
            $has_shipping_cycle = $subscription->get_meta('_fcsc_wcs_has_shipping_cycle', true);
            if(((int)$has_shipping_cycle) === 1){
                return true;
            }

            return false;
        }

        /**
         * Get template override
         * @param string $template_name
         * @param string $folder
         * @return string
         * */
        public static function getTemplateOverride($template_name, $folder = ''){
            if(!empty($folder)){
                $path = trailingslashit( dirname(FCDS_F_WCS_PLUGIN_BASENAME) ) .$folder."/".$template_name;
            } else {
                $path = trailingslashit( dirname(FCDS_F_WCS_PLUGIN_BASENAME) ) . $template_name;
            }
            $template = locate_template(
                array(
                    $path,
                    $template_name,
                )
            );

            return $template;
        }

        /**
         * Get localisation script
         * */
        public static function getLocalizationData(){
            return array(
                'day' => esc_html__('Day', FCDS_F_WCS_TEXT_DOMAIN),
                'week' => esc_html__('Week', FCDS_F_WCS_TEXT_DOMAIN),
                'month' => esc_html__('Month', FCDS_F_WCS_TEXT_DOMAIN),
                'year' => esc_html__('Year', FCDS_F_WCS_TEXT_DOMAIN),
                'every' => esc_html__('every', FCDS_F_WCS_TEXT_DOMAIN),
                'st' => esc_html__('st', FCDS_F_WCS_TEXT_DOMAIN),
                'nd' => esc_html__('nd', FCDS_F_WCS_TEXT_DOMAIN),
                'rd' => esc_html__('rd', FCDS_F_WCS_TEXT_DOMAIN),
                'th' => esc_html__('th', FCDS_F_WCS_TEXT_DOMAIN),
                'monday_each_week' => esc_html__('Monday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'tuesday_each_week' => esc_html__('Tuesday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'wednesday_each_week' => esc_html__('Wednesday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'thursday_each_week' => esc_html__('Thursday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'friday_each_week' => esc_html__('Friday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'saturday_each_week' => esc_html__('Saturday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'sunday_each_week' => esc_html__('Sunday each week', FCDS_F_WCS_TEXT_DOMAIN),
                'day_of_the_month' => esc_html__('day of the month', FCDS_F_WCS_TEXT_DOMAIN),
            );
        }
        
        public static function getShippingFormattedPrice($shipping_cycle){
            $text = $additional_price_name = '';
            $additional_price = 0;
            if(isset($shipping_cycle['additional_price'])){
                $additional_price = trim($shipping_cycle['additional_price']);
            }
            if(isset($shipping_cycle['additional_price_name'])){
                $additional_price_name = trim($shipping_cycle['additional_price_name']);
            }
            if(!empty($additional_price)){
                $text = esc_html__('subscription delivery fee', FCDS_F_WCS_TEXT_DOMAIN);
                if(!empty($additional_price_name)){
                    $text = sprintf(esc_html__('%s as %s', FCDS_F_WCS_TEXT_DOMAIN), wc_price($additional_price), $additional_price_name);
                } else {
                    $text = sprintf(esc_html__('%s as %s', FCDS_F_WCS_TEXT_DOMAIN), wc_price($additional_price), $text);
                }
            }

            return apply_filters('fcds_wcs_additional_price_text', $text, $additional_price, $additional_price_name);
        }

        /**
         * Get generated text for shipping cycle
         * */
        public static function getShippingCycleText($period, $period_interval, $shipping_cycle = array()){
            if($period == 'day'){
                $period_text = __('day', FCDS_F_WCS_TEXT_DOMAIN);
            } else if($period == 'week'){
                $period_text = __('week', FCDS_F_WCS_TEXT_DOMAIN);
            } else if($period == 'month'){
                $period_text = __('month', FCDS_F_WCS_TEXT_DOMAIN);
            } else {
                $period_text = __('year', FCDS_F_WCS_TEXT_DOMAIN);
            }

            if($period_interval == 1){
                $period_interval_text = '';
            } else {
                $period_interval_text = self::getPeriodIntervalCount($period_interval);
            }

            $sync_text = '';
            $sync_option = self::get_sync_option($shipping_cycle);
            if($sync_option !== false){
                if($sync_option == 'month'){
                    if(isset($shipping_cycle['sync_week_month'])){
                        if($shipping_cycle['period'] == 'month'){
                            $month_interval_text = self::getPeriodIntervalCount($shipping_cycle['sync_week_month']);
                            $sync_text = sprintf(__('On %s of ', FCDS_F_WCS_TEXT_DOMAIN), $month_interval_text);
                        } else {
                            $week_text = self::getWeekToDisplay($shipping_cycle['sync_week_month']);
                            $sync_text = sprintf(__('On %s of ', FCDS_F_WCS_TEXT_DOMAIN), $week_text);
                        }

                    }
                } else if($sync_option == 'year'){
                    if(isset($shipping_cycle['sync_year']) && isset($shipping_cycle['sync_year_day'])){
                        $year_interval_text = self::getPeriodIntervalCount($shipping_cycle['sync_year_day']);
                        $year_text = self::getYearToDisplay($shipping_cycle['sync_year']);
                        $sync_text = sprintf(__('On %s %s of ', FCDS_F_WCS_TEXT_DOMAIN), $year_text, $year_interval_text);
                    }
                }
            }

            $string_to_display = sprintf(__('Every %s %s', FCDS_F_WCS_TEXT_DOMAIN), $period_interval_text, $period_text);

            if($period_interval == 1){
                $string_to_display = sprintf(__('Every %s', FCDS_F_WCS_TEXT_DOMAIN), $period_text);
                if(!empty($sync_text)){
                    $string_to_display = sprintf(__('%s every %s', FCDS_F_WCS_TEXT_DOMAIN), $sync_text, $period_text);
                }

            }
            if(!empty($sync_text)){
                $string_to_display = sprintf(__('%s every %s %s', FCDS_F_WCS_TEXT_DOMAIN), $sync_text, $period_interval_text, $period_text);
            }

            return apply_filters('fcds_wcs_delivery_cycle_text', $string_to_display, $period, $period_interval, $shipping_cycle);
        }

        /**
         * Get year from number
         *
         * @param $month int
         * @return string
         * */
        public static function getYearToDisplay($month){
            $string = '';
            switch($month){
                case 1:
                    $string = esc_html__('January', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 2:
                    $string = esc_html__('February', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 3:
                    $string = esc_html__('March', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 4:
                    $string = esc_html__('April', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 5:
                    $string = esc_html__('May', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 6:
                    $string = esc_html__('June', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 7:
                    $string = esc_html__('July', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 8:
                    $string = esc_html__('August', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 9:
                    $string = esc_html__('September', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 10:
                    $string = esc_html__('October', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 11:
                    $string = esc_html__('November', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 12:
                    $string = esc_html__('December', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
            }

            return $string;
        }

        /**
         * Get week from number
         *
         * @param $week int
         * @return string
         * */
        public static function getWeekToDisplay($week){
            $string = '';
            switch($week){
                case 1:
                    $string = esc_html__('Monday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 2:
                    $string = esc_html__('Tuesday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 3:
                    $string = esc_html__('Wednesday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 4:
                    $string = esc_html__('Thursday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 5:
                    $string = esc_html__('Friday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 6:
                    $string = esc_html__('Saturday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
                case 7:
                    $string = esc_html__('Sunday', FCDS_F_WCS_TEXT_DOMAIN);
                    break;
            }

            return $string;
        }

        /**
         * Get Sync option
         *
         * @param $shipping_cycle array
         * @return mixed
         * */
        public static function get_sync_option($shipping_cycle){
            if(isset($shipping_cycle['sync_week_month'])){
                if(((int)$shipping_cycle['sync_week_month']) > 0){
                    return 'month';
                }
            }
            if(isset($shipping_cycle['sync_year']) && isset($shipping_cycle['sync_year_day'])){
                if(((int)$shipping_cycle['sync_year_day']) > 0){
                    return 'year';
                }
            }

            return false;
        }

        public static function getPeriodIntervalCount($period_interval){
            $last_digit = substr($period_interval, -1);
            if($last_digit == 1 && $period_interval != 11){
                $period_interval_text = sprintf(__('%sst', FCDS_F_WCS_TEXT_DOMAIN), $period_interval);
            } else if($last_digit == 2){
                $period_interval_text = sprintf(__('%snd', FCDS_F_WCS_TEXT_DOMAIN), $period_interval);
            } else if($last_digit == 3){
                $period_interval_text = sprintf(__('%srd', FCDS_F_WCS_TEXT_DOMAIN), $period_interval);
            } else {
                $period_interval_text = sprintf(__('%sth', FCDS_F_WCS_TEXT_DOMAIN), $period_interval);
            }

            return $period_interval_text;
        }

        /**
         * Load schedules from date
         *
         * @param $date_from string
         * @param $args array
         * @param $hook string
         * @param $date_compare string
         * @param $per_page integer
         * @return mixed
         * */
        public static function getSchedulesFromDate($date_from, $args, $hook = 'fcsc_wcs_scheduled_shipping_cycle', $date_compare = '>=', $per_page = -1){
            if(class_exists('ActionScheduler')){
                $date_from_object = as_get_datetime_object($date_from);
                $query = array(
                    'hook' => $hook,
                    'date' => $date_from_object,
                    'date_compare' => $date_compare,
                    'args' => $args,
                    'per_page' => $per_page,
                    'status' => 'pending',
                );
                return ActionScheduler::store()->query_actions($query);
            }

            return array();
        }

        /**
         * Get last schedule date
         *
         * @param $args array
         * @param $hook string
         * @return mixed
         * */
        public static function getLastScheduleJobId($args, $hook = 'fcsc_wcs_scheduled_shipping_cycle'){
            if(class_exists('ActionScheduler')){
                $query = array(
                    'hook' => $hook,
                    'args' => $args,
                    'per_page' => 1,
                    'status' => 'pending',
                    'orderby' => 'date',
                    'order' => 'DESC',
                );
                $last_schedule_job_ids = ActionScheduler::store()->query_actions($query);
                if(!empty($last_schedule_job_ids)){
                    if(is_array($last_schedule_job_ids)){
                        if(isset($last_schedule_job_ids['0'])){
                            return $last_schedule_job_ids['0'];
                        }
                    }
                }
            }

            return '';
        }

        /**
         * Cancel scheduled Job
         *
         * @param $job_id integer
         * */
        public static function cancelScheduledJob($job_id){
            if(!empty($job_id)){
                if(class_exists('ActionScheduler')){
                    ActionScheduler::store()->cancel_action($job_id);
                }
            }
        }

        /**
         * Get schedule list URL
         *
         * @param $subscription_id int
         * @return string
         * */
        public static function getScheduleListURL($subscription_id = 0){
            $url = 'admin.php?page=fcsc-custom-shipping';
            if(!empty($subscription_id)){
                $url .= '&s='.((int)$subscription_id);
            }
            return admin_url($url);
        }

        /**
         * Get settings page URL
         *
         * @return string
         * */
        public static function getSettingsURL(){
            $url = 'admin.php?page=fcsc-custom-shipping&tab=settings';
            return admin_url($url);
        }

        /**
         * Get shipping cycle in formatted array
         *
         * @param $cycle array
         * @return array
         * */
        public static function getFormattedShippingCycle($cycle){
            $shipping_cycle = array();
            if(!empty($cycle) && is_array($cycle)){
                foreach ($cycle as $key => $value){
                    if(substr( $key, 0, 9 ) === "shipping_"){
                        $shipping_cycle[substr($key,9)] = $value;
                    } else if(substr( $key, 0, 24 ) === "fcsc_wcs_shipping_cycle_"){
                        $shipping_cycle[substr($key,24)] = $value;
                    } else {
                        $shipping_cycle[$key] = $value;
                    }
                }
            }

            return $shipping_cycle;
        }

        /**
         * Get option
         *
         * @param $name string
         * @param $default mixed
         * @return mixed
         * */
        public static function getOption($name, $default = null){
            return get_option($name, $default);
        }

        /**
         * load additional delivery option
         * */
        public static function loadAdditionalDeliveryOptions($product, $delivery_cycles, $args){
            $html = '';
            if(FCDS_F_WCS_Purchase::is_pro()){
                if(!empty($delivery_cycles) && is_array($delivery_cycles)) {
                    if (count($delivery_cycles)) {
                        $path_from_template = FCDS_F_WCS_Helper::getTemplateOverride('product-shipping-options.php');
                        $path = FCDS_F_WCS_PLUGIN_PATH . '/advanced/templates/product-shipping-option-details.php';
                        if($path_from_template) $path = $path_from_template;
                        ob_start();
                        foreach ($delivery_cycles as $key => $delivery_cycle) {
                            self::loadAdditionalDeliveryOption($product, $delivery_cycle, $key, $args, $path);
                        }
                        $html = ob_get_contents();
                        ob_clean();
                        ob_get_clean();
                    }
                }
            }
            
            return $html;
        }

        /**
         * load additional delivery option
         * */
        public static function loadAdditionalDeliveryOption($product, $delivery_cycle, $key, $args, $path = ''){
            if(FCDS_F_WCS_Purchase::is_pro()){
                if(empty($path)){
                    if(empty(self::$delivery_option_details_tmp_path)){
                        $path_from_template = FCDS_F_WCS_Helper::getTemplateOverride('product-shipping-option-details.php');
                        $path = FCDS_F_WCS_PLUGIN_PATH . '/advanced/templates/product-shipping-option-details.php';
                        if($path_from_template) $path = $path_from_template;
                        self::$delivery_option_details_tmp_path = $path;
                    } else {
                        $path = self::$delivery_option_details_tmp_path;
                    }
                }

                if (!file_exists($path)) return false;
                include($path);
            }
        }
    }
}