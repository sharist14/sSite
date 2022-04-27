
/* Выделяем обязательные поля */
function mark_required(){
    var required = $('input,textarea,select').filter('[required]:visible');
    required.each(function(){
        if($(this).val() == ''){
            $(this).css('border', '2px solid red');
        } else{
            $(this).css('border', '2px solid green');
        }
    });
}

/* Активировать кассовый чек */
function add_receipt() {
    if ($('#enable_receipt').is(':checked')) {
        $('.receipt_data').show();
        $('.receipt_data').find('input,select').prop('required', true);
        $('.receipt_data').find('input[name=n]').val('1');
    } else {
        $('.receipt_data').hide();
        $('.receipt_data').find('input,select').prop('required', false);
        $('.receipt_data').find('input[name=n]').val('');
    }
}

/* Активировать Взять в долг */
function do_debt() {
    var cur_type = $('input[name=type_en]').val();
    if(cur_type == 'transfer') return;  // для переводов не показываем поля с кредитором и суммой

    if ($('#is_debt').is(':checked')) {
        $('.debt_data').show();
        $('.debt_data').find('input,select').prop('required', true);

        var debt_summa = $('input[name=debt_summa]');
        if(debt_summa.val() == ''){
            debt_summa.val($('input[name=s]').val())
        }
        // $('input[name=debt_summa]').val($('input[name=s]').val());
    } else {
        $('.debt_data').hide();
        $('.debt_data').find('input,select').prop('required', false);

        $('input[name=debt_summa]').val('');
    }
}

// Проверка суммы списания
/*function check_summa(){
    // Чекбокс "Взять в долг"
    var debt = $('#debt_in').is(':checked');

    // Сумма для списывания и баланс на счёте
    var sum = $('input[name=s]').val();
    var balance = $('select[name=money_account_id]').find('option:selected').data('balance');
    var diff = parseFloat(balance) - parseFloat(sum);

    if(diff < 0 && !debt){
        alert('В казне нет столько денег, милорд. Вы пытаетесь списать '+sum+'руб, а баланс всего '+balance+'руб. Воспользуйтесь возможностью взять в долг или соберите больше налогов с крестьян');
        return false;
    }

    return true;
}*/


/* Блокируем раскрытие селект */
function uncover_select(uncover_arr){
    console.log(uncover_arr);

    $.each(uncover_arr, function(index, id_select){
        $('#'+id_select).addClass('disable_color');

        console.log('#'+id_select);
    });


}


function scroll_btn(){
    var btn = $('#button_scroll');
    $(window).scroll(function() {
        if ($(window).scrollTop() > 300) {
            btn.addClass('show');
        } else {
            btn.removeClass('show');
        }
    });
    btn.on('click', function(e) {
        e.preventDefault();
        $('html, body').animate({scrollTop:0}, '300');
    });
}