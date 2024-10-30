/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @version     1.0.0
 */

jQuery(document).ready(function($){
    /* On change of shipping cycle option */
    $(document).on('change', 'select.fcds_wcs_delivery_cycle_select', function () {
        var value = $(this).val();
        var parentObject = $(this.closest('.fcds_wcs_delivery_cycle_block'));
        parentObject.find('.fcds_wcs_additional_delivery_options').hide();
        if(value != ''){
            parentObject.find('.fcds_wcs_additional_delivery_options[data-id="'+value+'"]').show();
        }
    });
    $('select.fcds_wcs_delivery_cycle_select').trigger('change');
    $( ".single_variation_wrap" ).on( "show_variation", function ( event, variation ) {
        $('select.fcds_wcs_delivery_cycle_select').trigger('change');
    });
});