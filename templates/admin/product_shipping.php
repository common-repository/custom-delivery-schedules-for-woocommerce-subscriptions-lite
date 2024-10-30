<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if(empty($fcsc_wcs_shipping_options_index)){
    $fcsc_wcs_shipping_options_index = 1;
}
$variable_subscription = false;
if($loop !== ''){
    $variable_subscription = true;
    $fcsc_wcs_enable_shipping_options_field_name = 'fcsc_wcs_enable_shipping_options['.$loop.']';
    $fcsc_wcs_shipping_options_index_field_name = 'fcsc_wcs_shipping_options_index['.$loop.']';
    $fcsc_wcs_shipping_options_loop_field_name = 'fcsc_wcs_shipping_options_loop['.$loop.']';
    $fcsc_wcs_use_only_shipping_cycle_field_name = 'fcsc_wcs_use_only_shipping_cycle['.$loop.']';
} else {
    $fcsc_wcs_enable_shipping_options_field_name = 'fcsc_wcs_enable_shipping_options';
    $fcsc_wcs_shipping_options_index_field_name = 'fcsc_wcs_shipping_options_index';
    $fcsc_wcs_shipping_options_loop_field_name = 'fcsc_wcs_shipping_options_loop';
    $fcsc_wcs_use_only_shipping_cycle_field_name = 'fcsc_wcs_use_only_shipping_cycle';
}
if(empty($fields)){
    $fcsc_wcs_use_only_shipping_cycle = 1;
}
?>
<div class='options_group flycart_csc_wcs_fields_con show_if_subscription options_group'>
    <h4><?php esc_html_e('Custom Delivery Schedules', FCDS_F_WCS_TEXT_DOMAIN); ?></h4>
    <div class="flycart_csc_wcs_shipping_options_checkbox">
        <p class="form-field ">
            <label>
                <input type="checkbox" value="1" <?php echo ($fcsc_wcs_enable_shipping_options == 1)? 'checked': ''; ?> name="<?php echo esc_attr($fcsc_wcs_enable_shipping_options_field_name); ?>" class="fcsc_wcs_enable_shipping_options"/> <?php esc_html_e('Enable Custom Delivery Schedules', FCDS_F_WCS_TEXT_DOMAIN); ?>
            </label>
        </p>
        <?php if(FCDS_F_WCS_Purchase::is_pro()){
            ?>
            <p class="form-field show_shipping_option_block">
                <label>
                    <input type="checkbox" value="1" <?php echo ($fcsc_wcs_use_only_shipping_cycle == 1)? 'checked': ''; ?> name="<?php echo esc_attr($fcsc_wcs_use_only_shipping_cycle_field_name); ?>" class="fcsc_wcs_use_only_shipping_cycle"/> <?php esc_html_e('Hide Single Delivery Schedule in Store Front', FCDS_F_WCS_TEXT_DOMAIN); ?>
                </label><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Choose if this product is used only with custom delivery schedules', FCDS_F_WCS_TEXT_DOMAIN); ?>"></span>
            </p>
            <?php
        } ?>
    </div>
    <div class="flycart_csc_wcs_shipping_options_con">
        <div class="flycart_csc_wcs_fields">
            <?php
            if(!empty($fields) && is_array($fields)){
                foreach ($fields as $key => $field){
                    self::loadShippingCycleBlockFields($key, $field, $loop);
                }
            } else {
                self::loadShippingCycleBlockFields(0, array(), $loop);
            }
            ?>
        </div>
        <div class="flycart_csc_wcs_btn_con">
            <input type="hidden" name="<?php echo esc_attr($fcsc_wcs_shipping_options_index_field_name); ?>" value="<?php echo esc_attr($fcsc_wcs_shipping_options_index); ?>" class="fcsc_wcs_shipping_options_index"/>
            <input type="hidden" name="<?php echo esc_attr($fcsc_wcs_shipping_options_loop_field_name); ?>" value="<?php echo esc_attr($loop); ?>" class="fcsc_wcs_shipping_options_loop"/>
            <?php
            if(FCDS_F_WCS_Purchase::is_pro()) {
                ?>
                <button class="fcsc_wcs_add_option_btn button button-primary button-large" type="button"><?php esc_html_e('Add option', FCDS_F_WCS_TEXT_DOMAIN); ?></button>
                <?php
            }
            ?>
        </div>
    </div>
</div>
