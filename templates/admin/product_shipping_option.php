<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

$shipping_period_interval = $shipping_period = '';
$shipping_sync_week_month = $shipping_sync_year_day = $shipping_sync_year = 0;
if(isset($data['shipping_period_interval'])){
    $shipping_period_interval = $data['shipping_period_interval'];
}
if(isset($data['shipping_period'])){
    $shipping_period = $data['shipping_period'];
}

$variable_subscription = false;
if($loop !== ''){
    $variable_subscription = true;
    $fcsc_wcs_shipping_options_shipping_period_interval_field_name = 'fcsc_wcs_shipping_options['.$loop.']['.$index.'][shipping_period_interval]';
    $fcsc_wcs_shipping_options_shipping_period_field_name = 'fcsc_wcs_shipping_options['.$loop.']['.$index.'][shipping_period]';
} else {
    $fcsc_wcs_shipping_options_shipping_period_interval_field_name = 'fcsc_wcs_shipping_options['.$index.'][shipping_period_interval]';
    $fcsc_wcs_shipping_options_shipping_period_field_name = 'fcsc_wcs_shipping_options['.$index.'][shipping_period]';
}
?>
<div class="flycart_csc_wcs_field_block options_group">
    <?php
    if(FCDS_F_WCS_Purchase::is_pro()){
        ?>
        <div class="fcsc_wcs_shipping_options_block_btn">
            <span class="flycart_csc_wcs_field_block_drag button button-small" ><?php esc_html_e('Drag and Change Position', FCDS_F_WCS_TEXT_DOMAIN); ?></span>
            <button class="fcsc_wcs_remove button button-small" type="button"><?php esc_html_e('Remove', FCDS_F_WCS_TEXT_DOMAIN); ?></button>
        </div>
        <?php
    }
    ?>
    <p class="form-field">
        <label><?php esc_html_e('Delivery Interval', FCDS_F_WCS_TEXT_DOMAIN); ?></label>
        <select name="<?php echo esc_attr($fcsc_wcs_shipping_options_shipping_period_interval_field_name); ?>" class="fcsc_wcs_shipping_period_interval has_data_val" data-value="<?php echo esc_attr($shipping_period_interval); ?>">
        </select>
        <select name="<?php echo esc_attr($fcsc_wcs_shipping_options_shipping_period_field_name); ?>" class="fcsc_wcs_shipping_period last has_data_val" data-value="<?php echo esc_attr($shipping_period); ?>">
        </select>
    </p>
    <?php
    if(FCDS_F_WCS_Purchase::is_pro()){
        include( FCDS_F_WCS_PLUGIN_PATH.'/advanced/templates/admin/product_shipping_option.php' );
    }
    ?>
</div>
