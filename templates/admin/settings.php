<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly
?>
<h1 class="wp-heading-inline"><?php esc_html_e('Settings', FCDS_F_WCS_TEXT_DOMAIN); ?></h1>
<hr class="wp-header-end">
<form method="post" id="mainform" action="" enctype="multipart/form-data">
    <h2><?php esc_html_e('General options'); ?></h2>
    <table class="form-table">
        <tbody>
        <?php
        if(FCDS_F_WCS_Purchase::is_pro()) {
            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="fcsc_shipping_licence_key"><?php esc_html_e('Licence key'); ?><span
                                class="woocommerce-help-tip"
                                data-tip="<?php esc_attr_e('Enter licence key to get auto update', FCDS_F_WCS_TEXT_DOMAIN); ?>"></span></label>
                </th>
                <td class="forminp forminp-select">
                    <?php
                    $fcsc_shipping_licence_key = get_option('fcsc_shipping_licence_key', '');
                    ?>
                    <input name="fcsc_shipping_licence_key" id="fcsc_shipping_licence_key" type="text" style=""
                           value="<?php echo $fcsc_shipping_licence_key; ?>" class="" placeholder="">
                </td>
            </tr>
            <?php
        }
        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <?php
                $display_block_hint_text = esc_attr__('Display delivery option block in Product Page.', FCDS_F_WCS_TEXT_DOMAIN);
                if(FCDS_F_WCS_Purchase::is_pro()) {
                    $display_block_hint_text = esc_attr__('Display delivery option block in Product Page. (This is applicable for the products which contains only one delivery option)', FCDS_F_WCS_TEXT_DOMAIN);
                } ?>
                <label for="fcsc_shipping_front_end_display_option_block"><?php esc_html_e('Display delivery option block in Product Page'); ?><span class="woocommerce-help-tip" data-tip="<?php echo $display_block_hint_text; ?>"></span></label>
            </th>
            <td class="forminp forminp-select">
                <?php
                $fcsc_shipping_front_end_display_option_block = get_option( 'fcsc_shipping_front_end_display_option_block', 'yes' );
                ?>
                <label for="fcsc_shipping_front_end_display_option_block_yes">
                    <input name="fcsc_shipping_front_end_display_option_block" id="fcsc_shipping_front_end_display_option_block_yes" type="radio" class="" value="yes" <?php echo ($fcsc_shipping_front_end_display_option_block === 'yes')? 'checked="checked"': ''; ?>>
                    <?php esc_html_e('yes'); ?>
                </label>
                <label for="fcsc_shipping_front_end_display_option_block_no">
                    <input name="fcsc_shipping_front_end_display_option_block" id="fcsc_shipping_front_end_display_option_block_no" type="radio" class="" value="no" <?php echo ($fcsc_shipping_front_end_display_option_block === 'no')? 'checked="checked"': ''; ?>>
                    <?php esc_html_e('No'); ?>
                </label>
            </td>
        </tr>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="fcsc_shipping_front_end_option_title"><?php esc_html_e('Schedule option title'); ?><span class="woocommerce-help-tip" data-tip="<?php esc_attr_e('Title for delivery schedules option in front end. Leave it as empty for no title', FCDS_F_WCS_TEXT_DOMAIN); ?>"></span></label>
            </th>
            <td class="forminp forminp-select">
                <?php
                $fcsc_shipping_front_end_option_title = get_option( 'fcsc_shipping_front_end_option_title', '' );
                ?>
                <input name="fcsc_shipping_front_end_option_title" id="fcsc_shipping_front_end_option_title" type="text" style="" value="<?php echo $fcsc_shipping_front_end_option_title; ?>" class="" placeholder="<?php esc_attr_e('Delivery on', FCDS_F_WCS_TEXT_DOMAIN); ?>">
            </td>
        </tr>
        <?php
        if(FCDS_F_WCS_Purchase::is_pro()) {
            include( FCDS_F_WCS_PLUGIN_PATH.'/advanced/templates/admin/settings.php' );
        }
        ?>
        </tbody>
    </table>
    <p class="submit">
        <button name="save" class="button-primary" type="submit" value="<?php esc_attr_e('Save changes', FCDS_F_WCS_TEXT_DOMAIN); ?>"><?php esc_html_e('Save changes', FCDS_F_WCS_TEXT_DOMAIN); ?></button>
        <?php wp_nonce_field( 'cscfs-settings' ); ?>
    </p>
</form>