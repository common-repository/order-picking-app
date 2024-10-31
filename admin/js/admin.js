jQuery(document).ready(function($) {

    $("#pickingRoute").sortable({
        items: 'tr.main',
        cursor: 'pointer',
        axis: 'y',
        dropOnEmpty: false,
        start: function (e, ui) {
            ui.item.addClass("selected");
        },
        stop: function (e, ui) {
            ui.item.removeClass("selected");
        }
    });

    $('.app_logo_upload').click(function(e) {
        e.preventDefault();

        var custom_uploader = wp.media({
            title: 'Company logo',
            button: {
                text: 'Upload Image'
            },
            multiple: false  // Set this to true to allow multiple files to be selected
        })
            .on('select', function() {
                var attachment = custom_uploader.state().get('selection').first().toJSON();
                $('.app_logo').attr('src', attachment.url);
                $('.app_logo').show();
                $('.logoUrl').val(attachment.url);

            })
            .open();
    });

    $(".pickingSubRoute").sortable({
        items: 'tr.sub',
        cursor: 'pointer',
        axis: 'y',
        dropOnEmpty: false,
        start: function (e, ui) {
            ui.item.addClass("selected");
        },
        stop: function (e, ui) {
            ui.item.removeClass("selected");
        }
    });

    $(document).on('click', '#add_app_user', function(e) {
        e.preventDefault();
        var clone = $('.duplicate_row').last();
        $( clone ).clone().removeAttr('value').insertAfter( clone );
    });

    $(document).on('click', '.remove_picker', function(e) {
        e.preventDefault();

        $(this).parent().remove();
    });

    // AJAX - Create account
    $("#createAccount").click( function(e){
        e.preventDefault();

        $("#createAccount").addClass('disabled');

        // Iterate hidden checkboxes ( unchecked )
        var data = $(this).parent('form').serializeArray();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                'action': 'create_user_account',
                'data': data
            },
            dataType: 'JSON',
            success:function(response){
                if( response.message != '' ) {
                    alert(response.message);
                    $("#createAccount").removeClass('disabled');
                }
                else{
                    location.reload();
                }
            }
        });
    });

    // AJAX - Save settings
    $("#reset_api_key").click( function(e){
        e.preventDefault();

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                'action': 'reset_api_key',
            },
            dataType: 'JSON',
            success:function(response){
                location.reload();
            }
        });
    });

    // AJAX - Save settings
    $(".submit_options").click( function(e){
        e.preventDefault();

        $(".submit_options").addClass('disabled');

        // Iterate hidden checkboxes ( unchecked )
        var current_form = $(this).parent('form');
        var data = $(this).parent('form').serializeArray();

        app_users = [];
        $("input[name='app_users']").each(function() {
            app_user = $(this).val();
            if( app_user != '' ) {
                app_users.push($(this).val());
            }
        });
        data.push({
            "name": 'app_users',
            "value": app_users,
        });

        $(current_form).find('input:checkbox').each(function(){

            //push the object onto the array
            if( $(this).attr('type') == 'checkbox' ) {
                data.push({
                    "name": $(this).attr('name'),
                    "value": this.checked ? this.value : "0",
                });
            }
        });

        pickroute = [];
        $("input[name='pickroute']").each(function() {
            pickroute_item = $(this).val();
            if( pickroute_item != '' ) {
                pickroute.push($(this).val());
            }
        });
        data.push({
            "name": 'pickroute',
            "value": pickroute,
        });

        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                'action': 'save_app_settings',
                'token': $('#token').val(),
                'data': data
            },
            dataType: 'JSON',
            success:function(response){
                alert(response.message);
                $(".submit_options").removeClass('disabled');
            }
        });
    });

    $("#barcodeScannerInput").focus();

    $('.clickers.decrement').on('click', function() {
        var packedAmount = $(this).parent('.row').find('.packedAmount').text();
        var ordered = $(this).attr('data-ordered');
        ordered = parseInt(ordered);
        packedAmount = parseInt(packedAmount);
        if( packedAmount > 0 ) {
            packedAmount = packedAmount - 1;
            $(this).parent('.row').find('.packedAmount').text(packedAmount);
        }

        var barcode = $(this).attr('data-barcode');
        if( $('.packing_product[data-sku="'+barcode+'"]:visible').length > 0 ){
            var unpacked_amount = $('.packing_product[data-sku="'+barcode+'"]:visible').attr('data-unpacked');
            unpacked_amount = parseInt(unpacked_amount);
            if( unpacked_amount < ordered ) {
                unpacked_amount = unpacked_amount + 1;
                $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                var packed_amount = $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                if (unpacked_amount == 0) {
                    $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('completed');
                    $('.packing_product[data-sku="' + barcode + '"]:visible').removeClass('backorder');
                } else {
                    $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('backorder');
                    $('.packing_product[data-sku="' + barcode + '"]:visible').removeClass('completed');
                }
            }
        }
        else if( $('.packing_product[data-barcode="'+barcode+'"]').length > 0 ){
            var unpacked_amount = $('.packing_product[data-barcode="'+barcode+'"]:visible').attr('data-unpacked');
            unpacked_amount = parseInt(unpacked_amount);
            if( unpacked_amount < ordered ) {
                unpacked_amount = unpacked_amount + 1;

                $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                var packed_amount = $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                if (unpacked_amount == 0) {
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').addClass('completed');
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').removeClass('backorder');
                } else {
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').addClass('backorder');
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').removeClass('completed');
                }
            }
        }
    });

    $('.completeAmount').on('click', function() {
        var ordered = $(this).attr('data-ordered');
        ordered = parseInt(ordered);
        $(this).parent('td').find('.packedAmount').text(ordered);

        $(this).closest('.packing_product').attr('data-unpacked', 0);
        $(this).closest('.packing_product').find('.packed').text(ordered);

        $(this).closest('.packing_product').addClass('completed');
        $(this).closest('.packing_product').removeClass('backorder');
    });

    $('.clickers.increment').on('click', function() {
        var packedAmount = $(this).parent('.row').find('.packedAmount').text();
        var ordered = $(this).attr('data-ordered');
        packedAmount = parseInt(packedAmount);
        ordered = parseInt(ordered);
        if( packedAmount < ordered ) {
            packedAmount = packedAmount + 1;
            $(this).parent('.row').find('.packedAmount').text(packedAmount);
        }

        var barcode = $(this).attr('data-barcode');
        if( $('.packing_product[data-sku="'+barcode+'"]:visible').length > 0 ){
            var unpacked_amount = $('.packing_product[data-sku="'+barcode+'"]:visible').attr('data-unpacked');
            unpacked_amount = parseInt(unpacked_amount);
            if( unpacked_amount > 0 ) {
                unpacked_amount = unpacked_amount - 1;

                $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                var packed_amount = $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                if (unpacked_amount == 0) {
                    $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('completed');
                    $('.packing_product[data-sku="' + barcode + '"]:visible').removeClass('backorder');
                } else {
                    $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('backorder');
                }
            }
            else{
                alert("You already have picked the correct amount for this product with barcode: " + barcode);
            }
        }
        else if( $('.packing_product[data-barcode="'+barcode+'"]').length > 0 ){
            var unpacked_amount = $('.packing_product[data-barcode="'+barcode+'"]:visible').attr('data-unpacked');
            unpacked_amount = parseInt(unpacked_amount);
            if( unpacked_amount > 0 ) {
                unpacked_amount = unpacked_amount - 1;

                $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                var packed_amount = $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                if (unpacked_amount == 0) {
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').addClass('completed');
                    $('.packing_product[data-barcode="' + barcode + '"]:visible').removeClass('backorder');
                } else {
                    $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('backorder');
                }
            }
            else{
                alert("You already have picked the correct amount for this product with barcode: " + barcode);
            }
        }
    });

    $('.completeOrder').on('click', function() {

        var orderid = $(this).parent('.order_detail').attr('id');
        var token = $(this).parent('.order_detail').attr('data-token');
        var shop = $(this).parent('.order_detail').attr('data-token');

        if (confirm('I have checkecd all the packing products and want to completed this order!')) {

            $.ajax({
                type: "POST",
                url: 'https://orderpickingapp.com/wp-json/picking/v1/update-order-status?token=' + token + '&shop=' + shop +'&status=completed&appuser=desktop&orderid=' + orderid,
                success: function (response) {
                    location.reload();
                }
            });
        }
    });

    $('#barcodeScannerInput').on('input', function() {
        window.clearTimeout(this.timeout);
        this.timeout = window.setTimeout(() =>  {

            var barcode = $(this).val();

            console.log(barcode);

            // Search product by SKU or Barcode
            if( $('.packing_product[data-sku="'+barcode+'"]:visible').length > 0 ){
                var unpacked_amount = $('.packing_product[data-sku="'+barcode+'"]:visible').attr('data-unpacked');

                if( unpacked_amount > 0 ) {
                    unpacked_amount = unpacked_amount - 1;

                    $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                    var packed_amount = $('.packing_product[data-sku="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                    $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                    if (unpacked_amount == 0) {
                        $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('completed');
                        $('.packing_product[data-sku="' + barcode + '"]:visible').removeClass('backorder');
                    } else {
                        $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('backorder');
                    }
                }
                else{
                    alert("You already have picked the correct amount for this product with barcode: " + barcode);
                }
            }
            else if( $('.packing_product[data-barcode="'+barcode+'"]').length > 0 ){
                var unpacked_amount = $('.packing_product[data-barcode="'+barcode+'"]:visible').attr('data-unpacked');

                if( unpacked_amount > 0 ) {
                    unpacked_amount = unpacked_amount - 1;

                    $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-unpacked', unpacked_amount);

                    var packed_amount = $('.packing_product[data-barcode="' + barcode + '"]:visible').attr('data-ordered') - unpacked_amount;
                    $('.packing_product[data-sku="' + barcode + '"] .packed:visible').text(packed_amount);

                    if (unpacked_amount == 0) {
                        $('.packing_product[data-barcode="' + barcode + '"]:visible').addClass('completed');
                        $('.packing_product[data-barcode="' + barcode + '"]:visible').removeClass('backorder');
                    } else {
                        $('.packing_product[data-sku="' + barcode + '"]:visible').addClass('backorder');
                    }
                }
                else{
                    alert("You already have picked the correct amount for this product with barcode: " + barcode);
                }
            }
            else{
                alert("Barcode "+ barcode + " not found inside this order.");
                console.log("Barcode "+ barcode + " not found inside this order.");
            }

            // Re focus
            $("#barcodeScannerInput").val('');
            $("#barcodeScannerInput").focus();

        }, 300);
    });

    $(document).on('click', '.order_list_item', function(e) {
        e.preventDefault();
        $('.order_list_item').removeClass('open');
        $(this).addClass('open');

        var orderid = $(this).attr('data-orderid');
        $('.order_detail').hide();
        $('#'+orderid).show();

        $("#barcodeScannerInput").val('');
        $("#barcodeScannerInput").focus();
    });

});
