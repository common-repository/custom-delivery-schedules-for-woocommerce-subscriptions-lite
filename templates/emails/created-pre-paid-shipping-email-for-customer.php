<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * Delivery Schedule on product page
 *
 * This template can be overridden by copying it to YOURTHEME/custom-delivery-schedules-for-woocommerce-subscriptions/emails/created-pre-paid-shipping-email-for-customer.php
 *
 * HOWEVER, on occasion WooCommerce Subscriptions Up-Front Payment will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author  Flycart
 * @version 1.0.0
 *
 * @var object $subscription
 * @var object $order
 * @var string $subscription_product_name
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p>
    <?php
    printf( esc_html__( 'This is a delivery notice for your subscription product - %1$s. The order details are as follows:', FCDS_F_WCS_TEXT_DOMAIN ), esc_html( $subscription_product_name ) );
    ?>
</p>
<br/>

<?php
do_action( 'woocommerce_subscriptions_email_order_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

do_action( 'woocommerce_email_footer', $email );