var money = {
    core_js: '/engine/core/common_js',
    money_js: '/view/sites/money/sources/js',

    /* Действие при зарузке сайта */
    global_onload: function () {

    },


    /* Выделяем обязательные поля */
    mark_required: function(){
        var required = $('input,textarea,select').filter('[required]:visible');
        required.each(function () {
            if ($(this).val() == '') {
                $(this).css('border', '2px solid red');
            } else {
                $(this).css('border', '2px solid green');
            }
        });
    },


    /* Активировать кассовый чек */
    add_receipt: function() {
        if ($('#enable_receipt').is(':checked')) {
            $('.receipt_data').show();
            $('.receipt_data').find('input,select').prop('required', true);
            $('.receipt_data').find('input[name=n]').val('1');
        } else {
            $('.receipt_data').hide();
            $('.receipt_data').find('input,select').prop('required', false);
            $('.receipt_data').find('input[name=n]').val('');
        }
    },


    /* Активировать Взять в долг */
    do_debt: function() {
        var cur_type = $('input[name=type_en]').val();
        if (cur_type == 'transfer') return;  // для переводов не показываем поля с кредитором и суммой

        if ($('#is_debt').is(':checked')) {
            $('.debt_data').show();
            $('.debt_data').find('input,select').prop('required', true);

            var debt_summa = $('input[name=debt_summa]');
            if (debt_summa.val() == '') {
                debt_summa.val($('input[name=s]').val())
            }
        } else {
            $('.debt_data').hide();
            $('.debt_data').find('input,select').prop('required', false);

            $('input[name=debt_summa]').val('');
        }
    },


    /* Отображать баланс на счете */
    show_balance: function(id,summa){
        let balance = '';
        if(id && summa) balance = 'Баланс: '+parseFloat(summa)+' руб';

        $('#'+id).text(balance);
    },


    /* Блокируем раскрытие селект */
    uncover_select: function(uncover_arr) {
        $.each(uncover_arr, function (index, id_select) {
            $('#' + id_select).addClass('disable_color');
        });
    },


    /* Скрываем графики */
    hide_charts: function(){
        $('.chart').hide();
        $('#expand_btn').remove();
        $('body').append('<a onclick="money.show_charts()" id="expand_btn" href="javascript: void(0)" class="btn btn-warning btn-circle btn-md " style="position: fixed; bottom: 100px; right: 30px; text-align: center;"><i class="fal fa-list-alt fa-2x" style="padding-top: 7px"></i></a>');
    },


    /* Отображаем графики */
    show_charts: function(){
        $('.flow').empty();
        $('.chart').show();
        $('#expand_btn').remove();
    },


    /* Скрыть/отобразить кнопки фильтров */
    doActiveGraph: function(type, cur_month, cur_year){
        if(type == 'pie') {
            $('.graph_type').prop('disabled', true);    // Выключаем фильтр "Данные"
            $('#f_month').prop('disabled', false);       // Включаем фильтр "Месяц"

            money.genPieGraphic(cur_month, cur_year);
        }

        // Автозапуск линейного графика
        if(type == 'linear') {
            $('.graph_type').prop('disabled', false);       // Включаем фильтр "Данные"
            $('#f_month').prop('disabled', true);       // Выключаем фильтр "Месяц"

            money.genLinearGraphic();
        }
    },


    // Генерируем график пирог
    genPieGraphic: function(cur_month, cur_year){
        $('#googleCharts').empty();

        /* создаём объект с данными из полей */
        let formData = new FormData(filter);

        $.ajax({
            type: 'POST',
            url: '/analytics?act=_ajax_getDataPie',
            contentType: false,
            processData: false,
            data: formData,
            success: function (jsonData) {
                var data = jQuery.parseJSON(jsonData);

                gchart.parseYears(data);
            },
        });

        let id = cur_year+'_'+cur_month;
        utils.scrollRecursive(id, 500, 10);
    },


    // Генерируем линейный график
    genLinearGraphic: function(){
        $('#googleCharts').empty();

        /* создаём объект с данными из полей */
        let formData = new FormData(filter);

        $.ajax({
            type: 'POST',
            url: '/analytics?act=_ajax_getDataLinear',
            contentType: false,
            processData: false,
            data: formData,
            success: function (jsonData) {
                var data = jQuery.parseJSON(jsonData);

                gchart.parseCats(data);
            },
        });
    },


    // Проверка суммы списания
    /*check_summa: function(){
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
    },*/

}