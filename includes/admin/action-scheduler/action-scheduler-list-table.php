<?php
if (!defined('ABSPATH')) exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}
/**
 * Implements the admin view of the actions.
 * @codeCoverageIgnore
 */
class FCDS_F_WCS_ActionScheduler_ListTable extends FCDS_F_WCS_ActionScheduler_Abstract_ListTable {

    const POST_TYPE = 'scheduled-action';

    /**
     * The package name.
     *
     * @var string
     */
    protected $package = 'action-scheduler';

    /**
     * Columns to show (name => label).
     *
     * @var array
     */
    protected $columns = array();

    /**
     * Actions (name => label).
     *
     * @var array
     */
    protected $row_actions = array();

    /**
     * Hook
     */
    protected $hook = '';

    /**
     * The active data stores
     *
     * @var ActionScheduler_Store
     */
    protected $store;

    /**
     * A logger to use for getting action logs to display
     *
     * @var ActionScheduler_Logger
     */
    protected $logger;

    /**
     * A ActionScheduler_QueueRunner runner instance (or child class)
     *
     * @var ActionScheduler_QueueRunner
     */
    protected $runner;

    /**
     * Bulk actions. The key of the array is the method name of the implementation:
     *
     *     bulk_<key>(array $ids, string $sql_in).
     *
     * See the comments in the parent class for further details
     *
     * @var array
     */
    protected $bulk_actions = array();

    /**
     * Flag variable to render our notifications, if any, once.
     *
     * @var bool
     */
    protected static $did_notification = false;

    /**
     * Array of seconds for common time periods, like week or month, alongside an internationalised string representation, i.e. "Day" or "Days"
     *
     * @var array
     */
    private static $time_periods;

    /**
     * Sets the current data store object into `store->action` and initialises the object.
     *
     * @param ActionScheduler_Store $store
     * @param ActionScheduler_Logger $logger
     * @param ActionScheduler_QueueRunner $runner
     */
    public function __construct( ActionScheduler_Store $store, ActionScheduler_Logger $logger, ActionScheduler_QueueRunner $runner, $hook = '') {

        $this->store  = $store;
        $this->logger = $logger;
        $this->runner = $runner;
        $this->hook = $hook;

        $this->table_header = __( 'Delivery Schedules', FCDS_F_WCS_TEXT_DOMAIN );

        $this->bulk_actions = array(
            'delete' => __( 'Delete', FCDS_F_WCS_TEXT_DOMAIN ),
        );

        $this->columns = array(
            'subscription_id'        => __( 'Subscription ID', FCDS_F_WCS_TEXT_DOMAIN ),
            'customer'        => __( 'Customer', FCDS_F_WCS_TEXT_DOMAIN ),
            'product_title' => __( 'Product', FCDS_F_WCS_TEXT_DOMAIN ),
//            'customer' => __( 'Customer', FCDS_F_WCS_TEXT_DOMAIN ),
            'status'      => __( 'Delivery Status', FCDS_F_WCS_TEXT_DOMAIN ),
            'schedule'    => __( 'Scheduled Date', FCDS_F_WCS_TEXT_DOMAIN ),
            'log_entries' => __( 'Log', FCDS_F_WCS_TEXT_DOMAIN ),
        );

        $this->sort_by = array(
            'schedule',
            'hook',
            'group',
        );

        $this->search_by = array(
            'hook',
            'args',
            'claim_id',
        );

        $request_status = $this->get_request_status();

        if ( empty( $request_status ) ) {
            $this->sort_by[] = 'status';
        } elseif ( in_array( $request_status, array( 'in-progress', 'failed' ) ) ) {
            $this->columns  += array( 'claim_id' => __( 'Claim ID', FCDS_F_WCS_TEXT_DOMAIN ) );
            $this->sort_by[] = 'claim_id';
        }

        $this->row_actions = array(
            'subscription_id' => array(
                'run' => array(
                    'name'  => __( 'Run', FCDS_F_WCS_TEXT_DOMAIN ),
                    'desc'  => __( 'Process the action now as if it were run as part of a queue', FCDS_F_WCS_TEXT_DOMAIN ),
                ),
                'cancel' => array(
                    'name'  => __( 'Cancel', FCDS_F_WCS_TEXT_DOMAIN ),
                    'desc'  => __( 'Cancel the action now to avoid it being run in future', FCDS_F_WCS_TEXT_DOMAIN ),
                    'class' => 'cancel trash',
                ),
                'reschedule' => array(
                    'name'  => __( 'Re-Schedule', FCDS_F_WCS_TEXT_DOMAIN ),
                    'desc'  => __( 'Re-schedule the schedule to process', FCDS_F_WCS_TEXT_DOMAIN ),
                    'class' => 'reschedule',
                ),
            ),
        );

        self::$time_periods = array(
            array(
                'seconds' => YEAR_IN_SECONDS,
                'names'   => _n_noop( '%s year', '%s years', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => MONTH_IN_SECONDS,
                'names'   => _n_noop( '%s month', '%s months', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => WEEK_IN_SECONDS,
                'names'   => _n_noop( '%s week', '%s weeks', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => DAY_IN_SECONDS,
                'names'   => _n_noop( '%s day', '%s days', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => HOUR_IN_SECONDS,
                'names'   => _n_noop( '%s hour', '%s hours', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => MINUTE_IN_SECONDS,
                'names'   => _n_noop( '%s minute', '%s minutes', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
            array(
                'seconds' => 1,
                'names'   => _n_noop( '%s second', '%s seconds', FCDS_F_WCS_TEXT_DOMAIN ),
            ),
        );

        parent::__construct( array(
            'singular' => 'action-scheduler',
            'plural'   => 'action-scheduler',
            'ajax'     => false,
        ) );
    }

    /**
     * Convert an interval of seconds into a two part human friendly string.
     *
     * The WordPress human_time_diff() function only calculates the time difference to one degree, meaning
     * even if an action is 1 day and 11 hours away, it will display "1 day". This function goes one step
     * further to display two degrees of accuracy.
     *
     * Inspired by the Crontrol::interval() function by Edward Dale: https://wordpress.org/plugins/wp-crontrol/
     *
     * @param int $interval A interval in seconds.
     * @param int $periods_to_include Depth of time periods to include, e.g. for an interval of 70, and $periods_to_include of 2, both minutes and seconds would be included. With a value of 1, only minutes would be included.
     * @return string A human friendly string representation of the interval.
     */
    public static function human_interval( $interval, $periods_to_include = 2 ) {

        if ( $interval <= 0 ) {
            return __( 'Now!', FCDS_F_WCS_TEXT_DOMAIN );
        }

        $output = '';

        for ( $time_period_index = 0, $periods_included = 0, $seconds_remaining = $interval; $time_period_index < count( self::$time_periods ) && $seconds_remaining > 0 && $periods_included < $periods_to_include; $time_period_index++ ) {

            $periods_in_interval = floor( $seconds_remaining / self::$time_periods[ $time_period_index ]['seconds'] );

            if ( $periods_in_interval > 0 ) {
                if ( ! empty( $output ) ) {
                    $output .= ' ';
                }
                $output .= sprintf( _n( self::$time_periods[ $time_period_index ]['names'][0], self::$time_periods[ $time_period_index ]['names'][1], $periods_in_interval, FCDS_F_WCS_TEXT_DOMAIN ), $periods_in_interval );
                $seconds_remaining -= $periods_in_interval * self::$time_periods[ $time_period_index ]['seconds'];
                $periods_included++;
            }
        }

        return $output;
    }

    /**
     * Returns the recurrence of an action or 'Non-repeating'. The output is human readable.
     *
     * @param ActionScheduler_Action $action
     *
     * @return string
     */
    protected function get_recurrence( $action ) {
        $recurrence = $action->get_schedule();
        if ( $recurrence->is_recurring() ) {
            if ( method_exists( $recurrence, 'interval_in_seconds' ) ) {
                return sprintf( __( 'Every %s', FCDS_F_WCS_TEXT_DOMAIN ), self::human_interval( $recurrence->interval_in_seconds() ) );
            }

            if ( method_exists( $recurrence, 'get_recurrence' ) ) {
                return sprintf( __( 'Cron %s', FCDS_F_WCS_TEXT_DOMAIN ), $recurrence->get_recurrence() );
            }
        }

        return __( 'Non-repeating', FCDS_F_WCS_TEXT_DOMAIN );
    }

    /**
     * Get product title
     *
     * @param $action_id int
     * @param $meta array
     * @return string
     * */
    protected function get_product_title($action_id, $meta){
        $product_string = '';
        if(!empty($meta)){
            if(!empty($meta['_product_id']) && !empty($meta['_product_id'][0])){
                $product_id = $meta['_product_id'][0];
                $product_string = wc_get_product( $product_id )->get_formatted_name();
                $product_string = strip_tags($product_string);
            }
        }

        return $product_string;
    }

    /**
     * Get status text html
     *
     * @param $action_id int
     * @param $status_labels array
     * @return string
     * */
    protected function get_status_text($action_id, $status_labels){
        $status = $this->store->get_status( $action_id );
        $label = $status_labels[ $status ];
        $html = '<span class="fcsc-status-label '.$status.'">'.$label.'</span>';
        return $html;
    }

    /**
     * Get customer detail
     *
     * @param $action_id int
     * @param $meta array
     * @return string
     * */
    protected function get_customer($action_id, $meta){
        $user_string = '';
        if(!empty($meta)){
            if(!empty($meta['_product_id']) && !empty($meta['_user_id'][0])){
                $user_id = $meta['_user_id'][0];
                $user    = get_user_by( 'id', $user_id );

                $user_string = sprintf(
                /* translators: 1: user display name 2: user ID 3: user email */
                    esc_html__( '%1$s (#%2$s &ndash; %3$s)', FCDS_F_WCS_TEXT_DOMAIN ),
                    $user->display_name,
                    absint( $user->ID ),
                    $user->user_email
                );
            }
        }

        return $user_string;
    }

    /**
     * Get subscription id
     * */
    protected function get_subscription_id($meta){
        $subscription_id = '';
        if(!empty($meta)){
            if(!empty($meta['_fcsc_subscription_id']) && !empty($meta['_fcsc_subscription_id'][0])){
                $subscription_id = $meta['_fcsc_subscription_id'][0];
            }
        }

        return $subscription_id;
    }

    protected function get_subscription_id_with_url($action_id, $id, $meta){
        $subscription_url =  admin_url('post.php?post='.$id.'&action=edit');
        $html = '<a href="'.$subscription_url.'">'.$id.'</a>';
        return $html;
    }

    //get_subscription_id

    /**
     * Serializes the argument of an action to render it in a human friendly format.
     *
     * @param array $row The array representation of the current row of the table
     *
     * @return string
     */
    public function column_args( array $row ) {
        if ( empty( $row['args'] ) ) {
            return '';
        }

        $row_html = '<ul>';
        foreach ( $row['args'] as $key => $value ) {
            $row_html .= sprintf( '<li><code>%s => %s</code></li>', esc_html( var_export( $key, true ) ), esc_html( var_export( $value, true ) ) );
        }
        $row_html .= '</ul>';

        return apply_filters( 'action_scheduler_list_table_column_args', $row_html, $row );
    }

    /**
     * Prints the logs entries inline. We do so to avoid loading Javascript and other hacks to show it in a modal.
     *
     * @param array $row Action array.
     * @return string
     */
    public function column_log_entries( array $row ) {

        $log_entries_html = '<ol>';

        $timezone = new DateTimezone( 'UTC' );

        foreach ( $row['log_entries'] as $log_entry ) {
            $log_entries_html .= $this->get_log_entry_html( $log_entry, $timezone );
        }

        $log_entries_html .= '</ol>';

        return $log_entries_html;
    }

    /**
     * Prints the logs entries inline. We do so to avoid loading Javascript and other hacks to show it in a modal.
     *
     * @param ActionScheduler_LogEntry $log_entry
     * @param DateTimezone $timezone
     * @return string
     */
    protected function get_log_entry_html( ActionScheduler_LogEntry $log_entry, DateTimezone $timezone ) {
        $date = $log_entry->get_date();
        $date->setTimezone( $timezone );
        return sprintf( '<li><strong>%s</strong><br/>%s</li>', esc_html( $date->format( 'Y-m-d H:i:s O' ) ), esc_html( $log_entry->get_message() ) );
    }

    /**
     * Only display row actions for pending actions.
     *
     * @param array  $row         Row to render
     * @param string $column_name Current row
     *
     * @return string
     */
    protected function maybe_render_actions( $row, $column_name ) {
        if ( 'upcoming' === strtolower( strip_tags($row['status']) ) || 'canceled' === strtolower( strip_tags($row['status']) ) ) {
            return parent::maybe_render_actions( $row, $column_name );
        }

        return '';
    }

    /**
     * Renders admin notifications
     *
     * Notifications:
     *  1. When the maximum number of tasks are being executed simultaneously
     *  2. Notifications when a task us manually executed
     */
    public function display_admin_notices() {

        if ( $this->store->get_claim_count() >= $this->runner->get_allowed_concurrent_batches() ) {
            $this->admin_notices[] = array(
                'class'   => 'updated',
                'message' => sprintf( __( 'Maximum simultaneous batches already in progress (%s queues). No actions will be processed until the current batches are complete.', FCDS_F_WCS_TEXT_DOMAIN ), $this->store->get_claim_count() ),
            );
        }

        $notification = get_transient( 'action_scheduler_admin_notice' );

        if ( is_array( $notification ) ) {
            delete_transient( 'action_scheduler_admin_notice' );

            $action = $this->store->fetch_action( $notification['action_id'] );
            $action_hook_html = '<strong><code>' . $action->get_hook() . '</code></strong>';
            if ( 1 == $notification['success'] ) {
                $class = 'updated';
                switch ( $notification['row_action_type'] ) {
                    case 'run' :
                        $action_message_html = sprintf( __( 'Successfully executed action: %s', FCDS_F_WCS_TEXT_DOMAIN ), $action_hook_html );
                        break;
                    case 'cancel' :
                        $action_message_html = sprintf( __( 'Successfully canceled action: %s', FCDS_F_WCS_TEXT_DOMAIN ), $action_hook_html );
                        break;
                    default :
                        $action_message_html = sprintf( __( 'Successfully processed change for action: %s', FCDS_F_WCS_TEXT_DOMAIN ), $action_hook_html );
                        break;
                }
            } else {
                $class = 'error';
                $action_message_html = sprintf( __( 'Could not process change for action: "%s" (ID: %d). Error: %s', FCDS_F_WCS_TEXT_DOMAIN ), $action_hook_html, esc_html( $notification['action_id'] ), esc_html( $notification['error_message'] ) );
            }

            $action_message_html = apply_filters( 'action_scheduler_admin_notice_html', $action_message_html, $action, $notification );

            $this->admin_notices[] = array(
                'class'   => $class,
                'message' => $action_message_html,
            );
        }

        parent::display_admin_notices();
    }

    /**
     * Prints the scheduled date in a human friendly format.
     *
     * @param array $row The array representation of the current row of the table
     *
     * @return string
     */
    public function column_schedule( $row ) {
        if('canceled' === strtolower( strip_tags($row['status']) )){
            return $this->get_schedule_display_string_for_canceled( $row );
        }
        return $this->get_schedule_display_string( $row['schedule'] );
    }

    /**
     * Get the scheduled date in a human friendly format.
     *
     * @param ActionScheduler_Schedule $schedule
     * @return string
     */
    protected function get_schedule_display_string_for_canceled( $row ) {

        $schedule_display_string = '';

        $post = get_post($row['ID']);
        if(!empty($post)){
            $post->post_date_gmt;
            $next_timestamp = strtotime($post->post_date_gmt);
            $schedule_display_string .= date('Y-m-d H:i:s O', $next_timestamp);//$schedule->next()->format( 'Y-m-d H:i:s O' );
            $schedule_display_string .= '<br/>';

            if ( gmdate( 'U' ) > $next_timestamp ) {
                $schedule_display_string .= sprintf( __( ' (%s ago)', FCDS_F_WCS_TEXT_DOMAIN ), self::human_interval( gmdate( 'U' ) - $next_timestamp ) );
            } else {
                $schedule_display_string .= sprintf( __( ' (%s)', FCDS_F_WCS_TEXT_DOMAIN ), self::human_interval( $next_timestamp - gmdate( 'U' ) ) );
            }
        }
        return $schedule_display_string;
    }

    /**
     * Get the scheduled date in a human friendly format.
     *
     * @param ActionScheduler_Schedule $schedule
     * @return string
     */
    protected function get_schedule_display_string( ActionScheduler_Schedule $schedule ) {

        $schedule_display_string = '';

        if ( ! $schedule->next() ) {
            return $schedule_display_string;
        }

        $next_timestamp = $schedule->next()->getTimestamp();

        $schedule_display_string .= $schedule->next()->format( 'Y-m-d H:i:s O' );
        $schedule_display_string .= '<br/>';

        if ( gmdate( 'U' ) > $next_timestamp ) {
            $schedule_display_string .= sprintf( __( ' (%s ago)', FCDS_F_WCS_TEXT_DOMAIN ), self::human_interval( gmdate( 'U' ) - $next_timestamp ) );
        } else {
            $schedule_display_string .= sprintf( __( ' (%s)', FCDS_F_WCS_TEXT_DOMAIN ), self::human_interval( $next_timestamp - gmdate( 'U' ) ) );
        }

        return $schedule_display_string;
    }

    /**
     * Bulk delete
     *
     * Deletes actions based on their ID. This is the handler for the bulk delete. It assumes the data
     * properly validated by the callee and it will delete the actions without any extra validation.
     *
     * @param array $ids
     * @param string $ids_sql Inherited and unused
     */
    protected function bulk_delete( array $ids, $ids_sql ) {
        foreach ( $ids as $id ) {
            $this->store->delete_action( $id );
        }
    }

    /**
     * Implements the logic behind running an action. ActionScheduler_Abstract_ListTable validates the request and their
     * parameters are valid.
     *
     * @param int $action_id
     */
    protected function row_action_cancel( $action_id ) {
        $this->process_row_action( $action_id, 'cancel' );
    }

    /**
     * Implements the logic behind running an action. ActionScheduler_Abstract_ListTable validates the request and their
     * parameters are valid.
     *
     * @param int $action_id
     */
    protected function row_action_run( $action_id ) {
        $this->process_row_action( $action_id, 'run' );
    }

    /**
     * Implements the logic behind processing an action once an action link is clicked on the list table.
     *
     * @param int $action_id
     * @param string $row_action_type The type of action to perform on the action.
     */
    protected function process_row_action( $action_id, $row_action_type ) {
        try {
            switch ( $row_action_type ) {
                case 'run' :
                    $this->runner->process_action( $action_id );
                    break;
                case 'cancel' :
                    $this->store->cancel_action( $action_id );
                    break;
            }
            $success = 1;
            $error_message = '';
        } catch ( Exception $e ) {
            $success = 0;
            $error_message = $e->getMessage();
        }

        set_transient( 'action_scheduler_admin_notice', compact( 'action_id', 'success', 'error_message', 'row_action_type' ), 30 );
    }

    /**
     * {@inheritDoc}
     */
    public function prepare_items() {
        $this->process_bulk_action();

        $this->process_row_actions();

        if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
            // _wp_http_referer is used only on bulk actions, we remove it to keep the $_GET shorter
            wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
            exit;
        }

        $this->prepare_column_headers();

        $per_page = $this->get_items_per_page( $this->package . '_items_per_page', $this->items_per_page );
        $query = array(
            'per_page' => $per_page,
            'offset'   => $this->get_items_offset(),
            'status'   => $this->get_request_status(),
            'orderby'  => $this->get_request_orderby(),
            'order'    => $this->get_request_order(),
            'search'   => $this->get_request_search_query(),
            'hook'     => $this->hook,
        );

        if(!empty($_REQUEST['_wcs_product'])){
            $query['meta_query'][] = array(
                                        'key' => '_product_id',
                                        'value' => $_REQUEST['_wcs_product'],
                                        'compare' => '=',
                                    );
        }
        if(!empty($_REQUEST['_customer_user'])){
            $query['meta_query'][] = array(
                'key' => '_user_id',
                'value' => $_REQUEST['_customer_user'],
                'compare' => '=',
            );
        }

        $this->items = array();

        if(!empty($_REQUEST['start_date'])){
            $start_date = trim(sanitize_text_field($_REQUEST['start_date']));
            $query['date'] = as_get_datetime_object($start_date);
            $query['date_compare'] = '>=';
        }

        if(!empty($_REQUEST['end_date'])){
            $end_date = trim(sanitize_text_field($_REQUEST['end_date']));
            $query['date_end'] = as_get_datetime_object($end_date);
        }

        $total_items = $this->query_actions( $query, 'count' );

        $status_labels = $this->get_status_labels();

        foreach ( $this->query_actions( $query ) as $action_id ) {
            try {
                $action = $this->store->fetch_action( $action_id );
                $meta = get_post_meta($action_id);
            } catch ( Exception $e ) {
                continue;
            }
            $subscription_id = $this->get_subscription_id($meta);
            $this->items[ $action_id ] = array(
                'ID'          => $action_id,
                'status'      => $this->get_status_text($action_id, $status_labels),
                'log_entries' => $this->logger->get_logs( $action_id ),
                'product_title' => $this->get_product_title($action_id, $meta),
                'subscription_id' => $this->get_subscription_id_with_url($action_id, $subscription_id, $meta),
                'customer' => $this->get_customer($action_id, $meta),
                'schedule'    => $action->get_schedule(),
            );
        }

        $this->set_pagination_args( array(
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil( $total_items / $per_page ),
        ) );
    }

    /**
     * @return array
     */
    public function get_status_labels() {
        return array(
            ActionScheduler_Store::STATUS_PENDING  => __( 'Upcoming', FCDS_F_WCS_TEXT_DOMAIN ),
            ActionScheduler_Store::STATUS_COMPLETE => __( 'Completed', FCDS_F_WCS_TEXT_DOMAIN ),
            ActionScheduler_Store::STATUS_RUNNING  => __( 'In-progress', FCDS_F_WCS_TEXT_DOMAIN ),
            ActionScheduler_Store::STATUS_FAILED   => __( 'Failed', FCDS_F_WCS_TEXT_DOMAIN ),
            ActionScheduler_Store::STATUS_CANCELED => __( 'Canceled', FCDS_F_WCS_TEXT_DOMAIN ),
        );
    }

    /**
     * Prints the available statuses so the user can click to filter.
     */
    protected function display_filter_by_status() {
//        $this->status_counts = $this->store->action_counts();
        $this->status_counts = $this->action_counts();
        parent::display_filter_by_status();
    }

    /**
     * Get a count of all actions in the store, grouped by status
     *
     * @return array
     */
    public function action_counts() {

        $action_counts_by_status = array();
//        $action_stati_and_labels = $this->store->get_status_labels();
        $action_stati_and_labels = $this->get_status_labels();
//        $posts_count_by_status   = (array) wp_count_posts( self::POST_TYPE, 'readable' );
        $posts_count_by_status   = (array) $this->wp_count_posts( self::POST_TYPE, 'readable' );

        foreach ( $posts_count_by_status as $post_status_name => $count ) {

            try {
                $action_status_name = $this->get_action_status_by_post_status( $post_status_name );
            } catch ( Exception $e ) {
                // Ignore any post statuses that aren't for actions
                continue;
            }
            if ( array_key_exists( $action_status_name, $action_stati_and_labels ) ) {
                $action_counts_by_status[ $action_status_name ] = $count;
            }
        }

        return $action_counts_by_status;
    }

    /**
     * @param string $post_status
     *
     * @throws InvalidArgumentException if $post_status not in known status fields returned by $this->get_status_labels()
     * @return string
     */
    protected function get_action_status_by_post_status( $post_status ) {

        switch ( $post_status ) {
            case 'publish' :
                $action_status = ActionScheduler_wpPostStore::STATUS_COMPLETE;
                break;
            case 'trash' :
                $action_status = ActionScheduler_wpPostStore::STATUS_CANCELED;
                break;
            default :
                if ( ! array_key_exists( $post_status, $this->store->get_status_labels() ) ) {
                    throw new InvalidArgumentException( sprintf( 'Invalid post status: "%s". No matching action status available.', $post_status ) );
                }
                $action_status = $post_status;
                break;
        }

        return $action_status;
    }

    /**
     * @param string $post_status
     *
     * @throws InvalidArgumentException if $post_status not in known status fields returned by $this->get_status_labels()
     * @return string
     */
    protected function get_action_status_by_post_status_for_filter( $post_status ) {

        switch ( $post_status ) {
            case ActionScheduler_wpPostStore::STATUS_COMPLETE :
                $action_status = 'publish';
                break;
            case ActionScheduler_wpPostStore::STATUS_CANCELED :
                $action_status = 'trash';;
                break;
            default :
                if ( ! array_key_exists( $post_status, $this->store->get_status_labels() ) ) {
                    throw new InvalidArgumentException( sprintf( 'Invalid post status: "%s". No matching action status available.', $post_status ) );
                }
                $action_status = $post_status;
                break;
        }

        return $action_status;
    }

    function wp_count_posts( $type = 'post', $perm = '' ) {
        global $wpdb;

        if ( ! post_type_exists( $type ) ) {
            return new stdClass;
        }

        $cache_key = _count_posts_cache_key( $type, $perm );

        $counts = wp_cache_get( $cache_key, 'counts' );
        if ( false !== $counts ) {
            /** This filter is documented in wp-includes/post.php */
            return apply_filters( 'wp_count_posts', $counts, $type, $perm );
        }

        $query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} WHERE post_type = %s";
        if ( 'readable' == $perm && is_user_logged_in() ) {
            $post_type_object = get_post_type_object( $type );
            if ( ! current_user_can( $post_type_object->cap->read_private_posts ) ) {
                $query .= $wpdb->prepare(
                    " AND (post_status != 'private' OR ( post_author = %d AND post_status = 'private' ))",
                    get_current_user_id()
                );
            }
        }
        if(!empty($this->hook)){
            $query .= " AND post_title = '".$this->hook."'";
        }
        $query .= ' GROUP BY post_status';

        $results = (array) $wpdb->get_results( $wpdb->prepare( $query, $type ), ARRAY_A );
        $counts  = array_fill_keys( get_post_stati(), 0 );

        foreach ( $results as $row ) {
            $counts[ $row['post_status'] ] = $row['num_posts'];
        }

        $counts = (object) $counts;
        wp_cache_set( $cache_key, $counts, 'counts' );

        /**
         * Modify returned post counts by status for the current post type.
         *
         * @since 3.7.0
         *
         * @param object $counts An object containing the current post_type's post
         *                       counts by status.
         * @param string $type   Post type.
         * @param string $perm   The permission to determine if the posts are 'readable'
         *                       by the current user.
         */
        return apply_filters( 'wp_count_posts', $counts, $type, $perm );
    }

    /**
     * Get the text to display in the search box on the list table.
     */
    protected function get_search_box_button_text() {
        return __( 'Filter', FCDS_F_WCS_TEXT_DOMAIN );
    }

    /**
     * @param array $query
     * @param string $query_type Whether to select or count the results. Default, select.
     * @return string|array The IDs of actions matching the query
     */
    public function query_actions( $query = array(), $query_type = 'select' ) {
        /** @var wpdb $wpdb */
        global $wpdb;

        $sql = $this->get_query_actions_sql( $query, $query_type );

        return ( 'count' === $query_type ) ? $wpdb->get_var( $sql ) : $wpdb->get_col( $sql );
    }

    /**
     * Returns the SQL statement to query (or count) actions.
     *
     * @param array $query Filtering options
     * @param string $select_or_count  Whether the SQL should select and return the IDs or just the row count
     * @throws InvalidArgumentException if $select_or_count not count or select
     * @return string SQL statement. The returned SQL is already properly escaped.
     */
    protected function get_query_actions_sql( array $query, $select_or_count = 'select' ) {

        if ( ! in_array( $select_or_count, array( 'select', 'count' ) ) ) {
            throw new InvalidArgumentException(__('Invalid schedule. Cannot save action.', 'action-scheduler'));
        }

        $query = wp_parse_args( $query, array(
            'hook' => '',
            'args' => NULL,
            'date' => NULL,
            'date_compare' => '<=',
            'modified' => NULL,
            'modified_compare' => '<=',
            'group' => '',
            'status' => '',
            'claimed' => NULL,
            'per_page' => 5,
            'offset' => 0,
            'orderby' => 'date',
            'order' => 'ASC',
            'search' => '',
        ) );

        /** @var wpdb $wpdb */
        global $wpdb;
        $sql  = ( 'count' === $select_or_count ) ? 'SELECT count(p.ID)' : 'SELECT p.ID ';
        $sql .= "FROM {$wpdb->posts} p";

        $sql_params = array();
        if ( ! empty( $query['group'] ) || 'group' === $query['orderby'] ) {
            $sql .= " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID";
            $sql .= " INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id=tt.term_taxonomy_id";
            $sql .= " INNER JOIN {$wpdb->terms} t ON tt.term_id=t.term_id";

            if ( ! empty( $query['group'] ) ) {
                $sql .= " AND t.slug=%s";
                $sql_params[] = $query['group'];
            }
        }

        if(!empty($query['meta_query'])){
            $sql_m = get_meta_sql( $query['meta_query'], 'post','p', 'ID' );
            if(!empty($sql_m)){
                if(!empty($sql_m['join'])){
                    $sql .= " ".$sql_m['join'];
                }
            }
        }

        $sql .= " WHERE post_type=%s";

        if(!empty($query['meta_query'])){
            if(!empty($sql_m)){
                if(!empty($sql_m['where'])){
                    $sql .= " ".$sql_m['where'];
                }
            }
        }

        $sql_params[] = self::POST_TYPE;
        if ( $query['hook'] ) {
            $sql .= " AND p.post_title=%s";
            $sql_params[] = $query['hook'];
        }
        if ( !is_null($query['args']) ) {
            $sql .= " AND p.post_content=%s";
            $sql_params[] = json_encode($query['args']);
        }

        if ( ! empty( $query['status'] ) ) {
            $sql .= " AND p.post_status=%s";
            $sql_params[] = $this->get_action_status_by_post_status_for_filter( $query['status'] );
        }
        if(isset($query['date'])) {
            if ( $query['date'] instanceof DateTime ) {
                $date = clone $query['date'];
                $date->setTimezone( new DateTimeZone('UTC') );
                $date_string = $date->format('Y-m-d H:i:s');
                $comparator = $this->validate_sql_comparator($query['date_compare']);
                $sql .= " AND p.post_date_gmt $comparator %s";
                $sql_params[] = $date_string;
            }
        }

        if(isset($query['date_end'])) {
            if ($query['date_end'] instanceof DateTime) {
                $date_end = clone $query['date_end'];
                $date_end->setTimezone(new DateTimeZone('UTC'));
                $date_string = $date_end->format('Y-m-d H:i:s');
                $comparator = '<=';
                $sql .= " AND p.post_date_gmt $comparator %s";
                $sql_params[] = $date_string;
            }
        }

        if(isset($query['modified'])) {
            if ( $query['modified'] instanceof DateTime ) {
                $modified = clone $query['modified'];
                $modified->setTimezone( new DateTimeZone('UTC') );
                $date_string = $modified->format('Y-m-d H:i:s');
                $comparator = $this->validate_sql_comparator($query['modified_compare']);
                $sql .= " AND p.post_modified_gmt $comparator %s";
                $sql_params[] = $date_string;
            }
        }

        if(isset($query['claimed'])){
            if ( $query['claimed'] === TRUE ) {
                $sql .= " AND p.post_password != ''";
            } elseif ( $query['claimed'] === FALSE ) {
                $sql .= " AND p.post_password = ''";
            } elseif ( !is_null($query['claimed']) ) {
                $sql .= " AND p.post_password = %s";
                $sql_params[] = $query['claimed'];
            }
        }

        if ( ! empty( $query['search'] ) ) {
            $sql .= " AND (p.post_title LIKE %s OR p.post_content LIKE %s OR p.post_password LIKE %s)";
            for( $i = 0; $i < 3; $i++ ) {
                $sql_params[] = sprintf( '%%%s%%', $query['search'] );
            }
        }

        if ( 'select' === $select_or_count ) {
            switch ( $query['orderby'] ) {
                case 'hook':
                    $orderby = 'p.post_title';
                    break;
                case 'group':
                    $orderby = 't.name';
                    break;
                case 'status':
                    $orderby = 'p.post_status';
                    break;
                case 'modified':
                    $orderby = 'p.post_modified';
                    break;
                case 'claim_id':
                    $orderby = 'p.post_password';
                    break;
                case 'schedule':
                case 'date':
                default:
                    $orderby = 'p.post_date_gmt';
                    break;
            }
            if ( 'ASC' === strtoupper( $query['order'] ) ) {
                $order = 'ASC';
            } else {
                $order = 'DESC';
            }
            $sql .= " ORDER BY $orderby $order";
            if ( $query['per_page'] > 0 ) {
                $sql .= " LIMIT %d, %d";
                $sql_params[] = $query['offset'];
                $sql_params[] = $query['per_page'];
            }
        }

        return $wpdb->prepare( $sql, $sql_params );
    }

    /**
     * @param string $comparison_operator
     * @return string
     */
    protected function validate_sql_comparator( $comparison_operator ) {
        if ( in_array( $comparison_operator, array('!=', '>', '>=', '<', '<=', '=') ) ) {
            return $comparison_operator;
        }
        return '=';
    }

}
