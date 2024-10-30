/**
 * Custom Delivery Schedules for WooCommerce Subscriptions
 *
 * @version     1.0.0
 */

jQuery(document).ready(function($){
    $.extend({
        FCSCWCS: {
            subscription_cycle_changed : false,
            setShippingFieldsValue: function(){
                $('.flycart_csc_wcs_field_block .has_data_val').each(function(){
                    if($(this).is( "select" )){
                        var selected_val = $(this).attr('data-value');
                        var has_option = $(this).find("option[value='"+selected_val+"']").length;
                        if(has_option > 0){
                            $(this).val(selected_val);
                        }
                    } else {
                        $(this).val($(this).attr('data-value'));
                    }
                });
            },
            setCustomShippingOptions: function (parentObject) {
                $.FCSCWCS.subscription_cycle_changed = true;

                var subscription_period = parentObject.find('.wc_input_subscription_period').val();
                var subscription_period_interval = parseInt(parentObject.find('.wc_input_subscription_period_interval').val());
                var subscription_length = parseInt(parentObject.find('.wc_input_subscription_length').val());
                this.setCustomShippingOptionPeriod(parentObject, subscription_period, subscription_period_interval);
                $.FCSCWCS.subscription_cycle_changed = false;
                this.setShippingFieldsValue();
                $.FCSCWCS.subscription_cycle_changed = true;
                this.setCustomShippingOptionPeriodIntervals(parentObject, subscription_period, subscription_period_interval, subscription_length);
                $.FCSCWCS.subscription_cycle_changed = false;
                this.setShippingFieldsValue();
                this.setCustomShippingOptionSyncDate(parentObject, subscription_period, subscription_period_interval, subscription_length);
                this.setShippingFieldsValue();
            },
            setCustomShippingOptionPeriod: function (parentObject, subscription_period, subscription_period_interval) {
                var target = parentObject.find('.fcsc_wcs_shipping_period');
                target.html('');
                if(subscription_period === "day"){
                    if(subscription_period_interval != 1){
                        target.append($('<option></option>').attr('value', 'day').text(flycart_csc_wcs_localization.day));
                    }
                } else if(subscription_period === "week"){
                    target.append($('<option></option>').attr('value', 'day').text(flycart_csc_wcs_localization.day));
                    if(subscription_period_interval != 1){
                        target.append($('<option></option>').attr('value', 'week').text(flycart_csc_wcs_localization.week));
                    }
                } else if(subscription_period === "month"){
                    target.append($('<option></option>').attr('value', 'day').text(flycart_csc_wcs_localization.day))
                        .append($('<option></option>').attr('value', 'week').text(flycart_csc_wcs_localization.week));
                    if(subscription_period_interval != 1){
                        target.append($('<option></option>').attr('value', 'month').text(flycart_csc_wcs_localization.month));
                    }
                } else {
                    target.append($('<option></option>').attr('value', 'day').text(flycart_csc_wcs_localization.day))
                        .append($('<option></option>').attr('value', 'week').text(flycart_csc_wcs_localization.week))
                        .append($('<option></option>').attr('value', 'month').text(flycart_csc_wcs_localization.month));
                    if(subscription_period_interval != 1){
                        target.append($('<option></option>').attr('value', 'year').text(flycart_csc_wcs_localization.year));
                    }
                }
            },
            setCustomShippingOptionPeriodIntervals: function (parentObject, subscription_period, subscription_period_interval, subscription_length) {
                var blocks = parentObject.find('.flycart_csc_wcs_field_block');
                $(blocks).each(function(){
                    var target = $(this).find('.fcsc_wcs_shipping_period_interval');
                    var period_val = $(this).find('.fcsc_wcs_shipping_period').val();
                    var total_length = $.FCSCWCS.getTotalLength(period_val);
                    target.html('');
                    if(period_val == subscription_period){
                        total_length = subscription_period_interval;
                    } else {
                        var total_length_based_on_period = $.FCSCWCS.getMaxPeriodLength(subscription_period, subscription_period_interval, period_val);
                        if(parseInt(total_length_based_on_period) > 0){
                            total_length = total_length_based_on_period;
                        }
                    }
                    total_length = parseInt(total_length);
                    for (var n = 1; n < total_length; n++){
                        var option_text = $.FCSCWCS.generateOptionsTextForShippingInterval(n);
                        target.append($('<option></option>').attr('value', n).text(option_text));
                    }
                });
            },
            setCustomShippingOptionSyncDate: function (parentObject, subscription_period, subscription_period_interval, subscription_length) {
                var blocks = parentObject.find('.flycart_csc_wcs_field_block');
                $(blocks).each(function(){
                    var sync_field_cont = $(this).find('.fcsc_wcs_shipping_sync_fields');
                    var period_val = $(this).find('.fcsc_wcs_shipping_period').val();
                    var sync_week_month = $(this).find('.fcsc_wcs_shipping_sync_week_month');
                    var sync_year = $(this).find('.fcsc_wcs_shipping_sync_year');
                    if(period_val == 'day'){
                        sync_field_cont.hide();
                    } else if(period_val == 'week' || period_val == 'month'){
                        sync_field_cont.show();
                        $.FCSCWCS.getOptionsForSyncWeekMonth($(this), period_val);
                        sync_week_month.show();
                        sync_year.hide();
                    } else {
                        sync_field_cont.show();
                        sync_week_month.hide();
                        sync_year.show();
                    }
                });
            },
            getOptionsForSyncWeekMonth: function (block, period){
                var target = block.find('.fcsc_wcs_shipping_sync_week_month');
                target.html('');
                target.append($('<option></option>').attr('value', 0).text('Do not synchronise'));
                if(period == 'week'){
                    target.append($('<option></option>').attr('value', 1).text(flycart_csc_wcs_localization.monday_each_week));
                    target.append($('<option></option>').attr('value', 2).text(flycart_csc_wcs_localization.tuesday_each_week));
                    target.append($('<option></option>').attr('value', 3).text(flycart_csc_wcs_localization.wednesday_each_week));
                    target.append($('<option></option>').attr('value', 4).text(flycart_csc_wcs_localization.thursday_each_week));
                    target.append($('<option></option>').attr('value', 5).text(flycart_csc_wcs_localization.friday_each_week));
                    target.append($('<option></option>').attr('value', 6).text(flycart_csc_wcs_localization.saturday_each_week));
                    target.append($('<option></option>').attr('value', 7).text(flycart_csc_wcs_localization.sunday_each_week));
                } else {
                    var total_length = 29;
                    for (var n = 1; n < total_length; n++){
                        var number_string = n.toString();
                        var option_string = n+this.getLastCharForShippingIntervalText(number_string[number_string.length -1], n)+' '+flycart_csc_wcs_localization.day_of_the_month;
                        target.append($('<option></option>').attr('value', n).text(option_string));
                    }
                }
            },
            getMaxPeriodLength: function (subscription_period, subscription_period_interval, period){
                var total_length = 0;
                if(period === "day"){
                    if(subscription_period === 'year'){
                        total_length = 90;
                    } else if(subscription_period === 'month'){
                        if(subscription_period_interval < 3){
                            total_length = 30*subscription_period_interval;
                        }
                    } else if(subscription_period === 'week'){
                        if(subscription_period_interval < 8){
                            total_length = 7*subscription_period_interval;
                        }
                    }
                } else if(period === "week"){
                    if(subscription_period === 'year'){
                        total_length = 52;
                    } else if(subscription_period === 'month'){
                        if(subscription_period_interval < 12){
                            total_length = 5*subscription_period_interval;
                        }
                    }
                } else if(period === "month"){
                    if(subscription_period === 'year'){
                        if(subscription_period_interval < 2){
                            total_length = 12*subscription_period_interval;
                        }
                    }
                } else {
                }
                return total_length;
            },
            getTotalLength: function (period){
                var total_length = 0;
                if(period === "day"){
                    total_length = 90;
                } else if(period === "week"){
                    total_length = 52;
                } else if(period === "month"){
                    total_length = 24;
                } else {
                    total_length = 5;
                }
                return total_length;
            },
            generateOptionsTextForShippingInterval: function (number) {
                var option_string = flycart_csc_wcs_localization.every;
                if(number != 1){
                    number_string = number.toString();
                    option_string += ' '+number+this.getLastCharForShippingIntervalText(number_string[number_string.length -1], number);
                }
                return option_string;
            },
            getLastCharForShippingIntervalText: function(last_digit, full_number){
                var option_string_last = '';
                if(last_digit == 1 && full_number != 11){
                    option_string_last = flycart_csc_wcs_localization.st;
                } else if(last_digit == 2){
                    option_string_last = flycart_csc_wcs_localization.nd;
                } else if(last_digit == 3){
                    option_string_last = flycart_csc_wcs_localization.rd;
                } else {
                    option_string_last = flycart_csc_wcs_localization.th;
                }
                return option_string_last;
            },
            onAfterShippingOptionFieldsLoaded: function () {
                $('#general_product_data, .woocommerce_variation').each(function(){
                    $.FCSCWCS.setCustomShippingOptions($(this));
                });

                $(".fcsc_wcs_enable_shipping_options").trigger('change');

                /* Drag and drop the options */
                if($(".flycart_csc_wcs_shipping_options_con").length > 0){
                    $(".flycart_csc_wcs_shipping_options_con").sortable({
                        items:      ".flycart_csc_wcs_field_block",
                        handle:     "span.flycart_csc_wcs_field_block_drag",
                        axis:       "y",
                        cursor:     "move",
                        opacity:    0.80,
                        start:function( event,ui ){
                            ui.item.css( 'background-color','#dddddd' );
                        },
                        stop:function( event,ui ){
                            ui.item.removeAttr( 'style' );
                            $.FCSCWCS.reOrderFields($(this));
                        }
                    });
                }
            },
            reOrderFields: function (current_object) {
                var container = current_object.closest('.flycart_csc_wcs_shipping_options_con');
                $(container.find('.flycart_csc_wcs_field_block')).each(function (index) {
                   var fields = $(this).find('select, input');
                    $(fields).each(function () {
                        var org_field_name = $(this).attr('name');
                        var field_name_split = org_field_name.split("[");
                        if(field_name_split.length == 3 || field_name_split.length == 4){
                            var selected_part_index = field_name_split.length - 2;
                            var new_field_name = '';
                            $(field_name_split).each(function (key, value) {
                                if(key === parseInt(selected_part_index)){
                                    new_field_name += '['+index+']';
                                } else {
                                    if(key != 0) new_field_name += '[';
                                    new_field_name += value;
                                }
                            });
                            $(this).attr('name', new_field_name);
                        }
                    });
                });
            }
        }
    });

    /* On click the add option button */
    $('#woocommerce-product-data').on('click', '.fcsc_wcs_add_option_btn', function () {
        var container = $(this).closest('.flycart_csc_wcs_fields_con');
        var field_index = container.find('.fcsc_wcs_shipping_options_index').val();
        var loop = container.find('.fcsc_wcs_shipping_options_loop').val();
        field_index = parseInt(field_index);
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'load_fcsc_shipping_option', field_index: field_index, loop: loop
            },
            beforeSend: function() {

            },
            complete: function() {

            },
            success: function (response) {
                container.find('.flycart_csc_wcs_fields').append(response);
                container.find('.fcsc_wcs_shipping_options_index').val(++field_index);
                $.FCSCWCS.onAfterShippingOptionFieldsLoaded();
            }
        });
    });

    /* On change enable shipping option checkbox */
    $('#woocommerce-product-data').on('change', '.fcsc_wcs_enable_shipping_options', function () {
        var container = $(this).closest('.flycart_csc_wcs_fields_con');
        if ($(this).is(":checked")) {
            container.find('.flycart_csc_wcs_shipping_options_con, .show_shipping_option_block').show();
        } else {
            container.find('.flycart_csc_wcs_shipping_options_con, .show_shipping_option_block').hide();
        }
    });

    /* On change input fields */
    $('#woocommerce-product-data').on('change', '.flycart_csc_wcs_field_block .has_data_val', function () {
        if($.FCSCWCS.subscription_cycle_changed === false){
            $(this).attr('data-value', $(this).val());
        }
    });

    /* On change default subscription period, interval fields */
    $('#woocommerce-product-data').on('change','[name^="_subscription_length"], [name^="variable_subscription_length"], [name^="_subscription_period"], [name^="_subscription_period_interval"], [name^="variable_subscription_period"], [name^="variable_subscription_period_interval"]',function(){
        var parentObject = $(this.closest('#general_product_data, .woocommerce_variation'));
        $.FCSCWCS.setCustomShippingOptions(parentObject);
    });

    /* On change of shipping period */
    $('#woocommerce-product-data').on('change', '.fcsc_wcs_shipping_period', function () {
        if($.FCSCWCS.subscription_cycle_changed == false){
            var parentObject = $(this.closest('#general_product_data, .woocommerce_variation'));
            $.FCSCWCS.setCustomShippingOptions(parentObject);
        }
    });

    /* Remove a shipping option block */
    $('#woocommerce-product-data').on('click', '.fcsc_wcs_remove', function () {
        var container = $(this).closest('.flycart_csc_wcs_fields')
        $(this).closest('.flycart_csc_wcs_field_block').remove();
        $.FCSCWCS.reOrderFields(container);
    });

    /* When a variation is added or loaded */
    $('#woocommerce-product-data').on('woocommerce_variations_added woocommerce_variations_loaded',function(){
        $('.fcsc_wcs_enable_shipping_options').trigger('change');
        $.FCSCWCS.onAfterShippingOptionFieldsLoaded();
    });

    /* On load page refresh all shipping option fields based on subscriptions */
    $.FCSCWCS.onAfterShippingOptionFieldsLoaded();

    $.FCSCWCS.setShippingFieldsValue();
});