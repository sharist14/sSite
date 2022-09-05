var utils = {
    core_js: '/engine/core/common_js',
    money_js: '/view/sites/money/sources/js',

    /* Действие при зарузке сайта */
    global_onload: function(){

        // Модальные ссылки открываем во всплывающем окне (после загрузки страницы)
        $(document).on("click", ".modal_custom", async function(e) {
            e.preventDefault();                 // отменяем действие по умолч для ссылок (переход по ссылке)

            // Трюк, чтобы функция вызывались лишь для одного элемента, а не класса элементов
            e.stopPropagation();
            e.stopImmediatePropagation();

            // Header
            let header = $(this).text();

            // Footer
            let footer = '';

            // Content
            let ajaxurl = $(this).attr('href');
            let content = await utils.getDataFromUrl(ajaxurl);

            utils.modal_custom(content, header, footer, true);

            return false;
        });


        // Закрыть модальное окно при клике на body
        $(document).mouseup(function (e) {
            var container = $(".modal-content");
            if (container.has(e.target).length === 0){
                utils.modal_custom_close();
            }
        });

        // Открыть в полноэкранном режиме
        $('body').delegate(".fullscr_custom", "click",  async function(e) {
            e.preventDefault();                 // отменяем действие по умолч для ссылок (переход по ссылке)

            utils.fullScreen('body');
        });

    },


    /* Создать модальное окно */
    modal_custom: function(content, header = '', footer = '', night_mode = false){

        let tpl_data = utils.templateModal();    // Загружаем шаблон
        let div_id = tpl_data['div_id'];
        let tpl = tpl_data['template'];

        // Header
        tpl = tpl.replace('~title~', header);

        // Content
        tpl = tpl.replace('~content~', content);

        // Footer
        if( footer ){
            tpl = tpl.replace('~footer~', footer);
        } else{
            tpl = tpl.replace('~display_footer~', 'hide');
        }

        $('body').append(tpl);
        $('#'+div_id).modal('show');
        $('#'+div_id+' .header').remove();
        $('#'+div_id+' .footer').remove();
        
        // Темный фон
        if(night_mode == true){
            $('#'+div_id+' .modal-header').css('background-color', 'black');
            $('#'+div_id+' .modal-header .btn-close').css('background-color', 'white');
            $('#'+div_id+' .modal-body').css('background-color', 'black').css('padding-left', 0).css('padding-right', 0);
            $('body .content').css('padding-left', '5px').css('padding-right', '5px');
        }

        return true;
    } ,


    /* Получаем данные для модального окна */
    getDataFromUrl: async function(ajaxurl){
        return await $.ajax(ajaxurl);
    },


    /* Шаблон для модального окна */
    templateModal: function(){
        let cnt_modal = $('.modal').length;
        let div_id = 'modal_tpl'+(Number(cnt_modal)+1);

        let template = '<div id="'+div_id+'" class="modal fade bg-dark" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">'+
            '<div class="modal-dialog">'+
                '<div class="modal-content">'+
                  '<div class="modal-header">'+
                    '<h5 class="modal-title" id="exampleModalLabel">~title~</h5>'+
                    '<button type="button" onclick="utils.modal_custom_close(this)" class="btn-close" aria-label="Закрыть"></button>'+
                  '</div>'+
                  '<div class="modal-body">'+
                    '~content~'+
                  '</div>'+
                  '<div class="modal-footer ~display_footer~">'+
                    '~footer~'+
                  '</div>'+
                '</div>'+
          '</div>'+
        '</div>';

        return {'div_id': div_id, 'template': template};
    },

    // Закрыть модальное окно
    modal_custom_close: function(el = ''){
        if(el){
            el.closest('.modal').remove();
        } else{
            $('.modal').remove();
        }

        $('.modal-backdrop').remove();

        // Удаляем настройки модального окна, чтобы корректно работала прокрутка
        if( !$('.modal').length ){
            $('body').removeAttr("style");
            $('body').removeClass('modal-open');
        }
    },

    // Работа с полноэкранным режимом
    fullScreen: function(element_id = ''){
        var element;

        // Полный экран для конкретного элемента или страницы в целом
        if(element_id){
            element = document.getElementById(element_id);
        } else{
            element = document.documentElement;
        }

        // Если запрос на включение
        if (element.requestFullscreen || element.webkitrequestFullscreen || element.mozRequestFullscreen) {
            utils.fullScreenEnable(element_id);
        }

        // Если запрос на выключение
        if (document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen) {
            utils.fullScreenCancel();
        }
    },


    /* Включени полноэкранного режима для выбранного элемента */
    fullScreenEnable: function(element_id = ''){
        var element;

        // Полный экран для конкретного элемента илистраницы в целом
        if(element_id){
            element = document.getElementById(element_id);
        } else{
            element = document.documentElement;
        }

        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitrequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullscreen) {
            element.mozRequestFullScreen();
        }
    },


    /* Отмена полноэкранного режима */
    fullScreenCancel: function(){
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) { /* Safari */
            document.webkitExitFullscreen();
        } else if (document.msExitFullscreen) { /* IE11 */
            document.msExitFullscreen();
        }
    },


    // Кнопка скролл
    scroll_btn: function() {
        var btn = $('#button_scroll');
        $(window).scroll(function () {
            if ($(window).scrollTop() > 300) {
                btn.addClass('show');
            } else {
                btn.removeClass('show');
            }
        });
        btn.on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({scrollTop: 0}, '300');
        });
    },


    /* Скроллинг к элементам когда они появяться */
    scrollRecursive: function (id, delay, offset = 0, animate = 'fast') {
        window.setTimeout(function () {
            if ($("#" + id).length) {
                $('html,body').animate({
                    scrollTop: $("#" + id).offset().top - offset
                }, animate);
                return;
            } else {
                utils.scrollRecursive(id, delay, offset)
            }

        }, delay);
    },

}