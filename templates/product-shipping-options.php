<?php
/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * Delivery Schedule on product page
 *
 * This template can be overridden by copying it to YOURTHEME/custom-delivery-schedules-for-woocommerce-subscriptions/product-shipping-options.php.
 *
 * HOWEVER, on occasion Custom Delivery Schedules for WooCommerce Subscriptions will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @author  Flycart
 * @version 1.0.0
 *
 * @var array $delivery_cycles
 * @var array $args
 * @var object $product
 */

if (!defined('ABSPATH')) exit;
?>
<div class="fcds_wcs_delivery_cycle_con">
    <?php
    if($args['delivery_title'] != '' && $args['display_title']){
        ?>
        <h4><?php echo $args['delivery_title']; ?></h4>
    <?php
    }
    if(!empty($delivery_cycles) && is_array($delivery_cycles)){
        if(count($delivery_cycles)){
            if($args['display_option_block'] === 'no' && count($delivery_cycles) == 1){
                ?>
                <input type="hidden" name="fcsc_wcs_shipping_cycle" id="fcsc_wcs_shipping_cycle_0" value="0" />
                <?php
            } else {
                if(count($delivery_cycles) == 1){
                    foreach ($delivery_cycles as $key => $delivery_cycle){
                        if(isset($delivery_cycle['shipping_period'])){
                            $formatted_delivery_cycle = FCDS_F_WCS_Helper::getFormattedShippingCycle($delivery_cycle);
                            ?>
                            <div class="fcds_wcs_delivery_cycle_block">
                                <input type="hidden" name="fcsc_wcs_shipping_cycle" id="fcsc_wcs_shipping_cycle_<?php echo esc_attr($key); ?>" value="<?php echo esc_attr($key); ?>" />
                                <label for="fcsc_wcs_shipping_cycle_<?php echo esc_attr($key); ?>">
                                    <?php echo FCDS_F_WCS_Helper::getShippingCycleText($delivery_cycle['shipping_period'], $delivery_cycle['shipping_period_interval'], $formatted_delivery_cycle); ?>
                                </label>
                                <?php
                                FCDS_F_WCS_Helper::loadAdditionalDeliveryOption($product, $delivery_cycle, $key, $args);
                                ?>
                            </div>
                            <?php
                        }
                    }
                } else {
                    if($args['display_delivery_option'] == 'list'){
                        $select_box_option_html = '<select name="fcsc_wcs_shipping_cycle" class="fcds_wcs_delivery_cycle_select">';
                        if(!$args['use_only_shipping_option']){
                            $select_box_option_html .= '<option value="">'.esc_html__('Choose Delivery Schedule', FCDS_F_WCS_TEXT_DOMAIN).'</option>';
                        }
                        foreach ($delivery_cycles as $key => $delivery_cycle){
                            if(isset($delivery_cycle['shipping_period'])){
                                $formatted_delivery_cycle = FCDS_F_WCS_Helper::getFormattedShippingCycle($delivery_cycle);
                                $select_box_option_html .= '<option value="'.esc_attr($key).'">'.FCDS_F_WCS_Helper::getShippingCycleText($delivery_cycle['shipping_period'], $delivery_cycle['shipping_period_interval'], $formatted_delivery_cycle).'</option>';
                            }
                        }
                        $select_box_option_html .= '</select>';
                        ?>
                        <div class="fcds_wcs_delivery_cycle_block fcds_wcs_delivery_cycle_block_list_con">
                            <?php
                            echo $select_box_option_html;
                            ?>
                            <div class="fcsc_wcs_shipping_cycle_additional_options_con">
                                <?php
                                echo FCDS_F_WCS_Helper::loadAdditionalDeliveryOptions($product, $delivery_cycles, $args);
                                ?>
                            </div>
                        </div>
                        <?php
                    } else {
                        foreach ($delivery_cycles as $key => $delivery_cycle){
                            if(isset($delivery_cycle['shipping_period'])){
                                $formatted_delivery_cycle = FCDS_F_WCS_Helper::getFormattedShippingCycle($delivery_cycle);
                                ?>
                                <div class="fcds_wcs_delivery_cycle_block">
                                    <input type="radio" name="fcsc_wcs_shipping_cycle" id="fcsc_wcs_shipping_cycle_<?php echo esc_attr($key); ?>" <?php echo ($args['use_only_shipping_option'] && $key == 0)? 'checked': ''; ?> value="<?php echo esc_attr($key); ?>" />
                                    <label for="fcsc_wcs_shipping_cycle_<?php echo esc_attr($key); ?>" class="fcds_wcs_radio-label">
                                        <?php echo FCDS_F_WCS_Helper::getShippingCycleText($delivery_cycle['shipping_period'], $delivery_cycle['shipping_period_interval'], $formatted_delivery_cycle); ?>
                                    </label>
                                    <?php
                                    FCDS_F_WCS_Helper::loadAdditionalDeliveryOption($product, $delivery_cycle, $key, $args);
                                    ?>
                                </div>
                                <?php
                            }
                        }
                    }
                }
            }

            do_action('fcds_wcs_after_delivery_options_loaded', $product, $delivery_cycles, $args);
        }
    }
    ?>
</div>
