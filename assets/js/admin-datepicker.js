jQuery(document).ready(function($){

    /* --------------------
       Datepicker
    ---------------------*/

    $('#admin_date_picker').persianDatepicker({
        format: 'YYYY/MM/DD',
        autoClose: true,
        initialValue: false
    });

    $('#add_price_history').on('click', function(){

        let price = $('#manual_price').val();
        let date  = $('#admin_date_picker').val();

        if(!price || !date){
            alert('قیمت و تاریخ را وارد کنید');
            return;
        }

        let input = $('<input>')
            .attr('type','hidden')
            .attr('name','manual_price_history[]')
            .val(price+'|'+date);

        $('#manual_price_container').append(input);

        alert('اضافه شد. محصول را بروزرسانی کنید.');
    });

    /* --------------------
       مدیریت تم نمودار
    ---------------------*/

    function toggleCustomFields(){

        let theme = $('#chart_theme').val();

        if(theme === 'custom'){
            $('.custom-style-field').prop('disabled', false).closest('tr').show();
        } else {
            $('.custom-style-field').prop('disabled', true).closest('tr').hide();
        }
    }

    $('#chart_theme').on('change', function(){
        toggleCustomFields();
    });

    toggleCustomFields();

});
