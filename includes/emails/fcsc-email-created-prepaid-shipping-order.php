<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @class       FCDS_F_WCS_EmailCreatedPrePaidShippingOrder
 * @author 		Flycart
 * @package 	Custom Delivery Schedules for WooCommerce Subscriptions
 * @version     1.0.0
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

class FCDS_F_WCS_EmailCreatedPrePaidShippingOrder extends WC_Email {

    public $order;
    public $subscription_product_name;

    function __construct() {
        // Add email ID, title, description, heading, subject
        $this->id                   = 'created_pre_paid_shipping_order';
        $this->title                = __( 'Scheduled Delivery notification for admin', FCDS_F_WCS_TEXT_DOMAIN);
        $this->description          = __( 'This email is received when an subscription delivery order is created.', FCDS_F_WCS_TEXT_DOMAIN);
        $this->heading              = $this->get_option( 'heading', __( 'Delivery notification', FCDS_F_WCS_TEXT_DOMAIN));
        $this->subject              = $this->get_option( 'subject', __( '[{site_title}] Delivery notification for (Subscription {order_number}) - {subscription_product_name}', FCDS_F_WCS_TEXT_DOMAIN));

        // email template path
        $this->template_html    = 'emails/created-pre-paid-shipping-email-for-admin.php';
        $this->template_plain   = 'emails/created-pre-paid-shipping-email-for-admin.php';

        // default the email recipient to the admin's email address
        $this->recipient     = $this->get_option( 'recipient', get_option( 'admin_email' ) );

        // Triggers for this email
        add_action( 'created_prepaid_shipping_order_email_notification', array( $this, 'trigger' ), 10, 2 );

        // Call parent constructor
        parent::__construct();

        // Other settings
        $this->template_base = FCDS_F_WCS_PLUGIN_TEMPLATE_PATH;
    }

    /**
     * Trigger the sending of this email.
     *
     * @param object  $subscription.
     * @param object  $order.
     */
    public function trigger( $subscription, $order) {
        $this->setup_locale();

        $this->object                         = $subscription;
        $this->order                          = $order;
        $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
        $this->placeholders['{order_number}'] = $this->object->get_order_number();
        $product_name = '';
        // Iterating through subscription items
        foreach( $subscription->get_items() as $item_id => $product_subscription ){
            // Get the name
            $product_name = $product_subscription->get_name();
            break;
        }
        $this->subscription_product_name = $this->placeholders['{subscription_product_name}'] = $product_name;

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    // return the html content
    function get_content_html() {
        ob_start();
        wc_get_template( $this->template_html, array(
            'subscription'  => $this->object,
            'order'         => $this->order,
            'subscription_product_name' => $this->subscription_product_name,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ), FCDS_F_WCS_PLUGIN_DIR.'/', $this->template_base );
        return ob_get_clean();
    }

    // return the plain content
    function get_content_plain() {
        ob_start();
        wc_get_template( $this->template_plain, array(
            'subscription'  => $this->object,
            'order'         => $this->order,
            'subscription_product_name' => $this->subscription_product_name,
            'email_heading' => $this->get_heading(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ), FCDS_F_WCS_PLUGIN_DIR.'/', $this->template_base );
        return ob_get_clean();
    }

    // return the subject
    function get_subject() {
        return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
    }

    // return the email heading
    public function get_heading() {
        return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
    }

    // form fields that are displayed in WooCommerce->Settings->Emails
    function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' 		=> __( 'Enable/Disable', FCDS_F_WCS_TEXT_DOMAIN ),
                'type' 			=> 'checkbox',
                'label' 		=> __( 'Enable this email notification', FCDS_F_WCS_TEXT_DOMAIN ),
                'default' 		=> 'no'
            ),
            'recipient' => array(
                'title'         => __( 'Recipient', FCDS_F_WCS_TEXT_DOMAIN ),
                'type'          => 'text',
                'description'   => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s', FCDS_F_WCS_TEXT_DOMAIN ), get_option( 'admin_email' ) ),
                'default'       => get_option( 'admin_email' )
            ),
            'subject' => array(
                'title' 		=> __( 'Subject', FCDS_F_WCS_TEXT_DOMAIN ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', FCDS_F_WCS_TEXT_DOMAIN ), $this->subject ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'heading' => array(
                'title' 		=> __( 'Email Heading', FCDS_F_WCS_TEXT_DOMAIN ),
                'type' 			=> 'text',
                'description' 	=> sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.', FCDS_F_WCS_TEXT_DOMAIN ), $this->heading ),
                'placeholder' 	=> '',
                'default' 		=> ''
            ),
            'email_type' => array(
                'title' 		=> __( 'Email type', FCDS_F_WCS_TEXT_DOMAIN ),
                'type' 			=> 'select',
                'description' 	=> __( 'Choose which format of email to send.', FCDS_F_WCS_TEXT_DOMAIN ),
                'default' 		=> 'html',
                'class'			=> 'email_type',
                'options'		=> array(
                    'plain'		 	=> __( 'Plain text', FCDS_F_WCS_TEXT_DOMAIN ),
                    'html' 			=> __( 'HTML', FCDS_F_WCS_TEXT_DOMAIN ),
                    'multipart' 	=> __( 'Multipart', FCDS_F_WCS_TEXT_DOMAIN ),
                )
            )
        );
    }

}
return new FCDS_F_WCS_EmailCreatedPrePaidShippingOrder();