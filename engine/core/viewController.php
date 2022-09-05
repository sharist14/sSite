<?php
require_once(_ROOT_DIR_ . '/engine/core/parentTemplate.php');

class viewController extends parentTemplate
{
    /**
     * Подключение параметров к странице
     */
    public static function display($tpl_body, $params=[]){

        // Получаем глобальный шаблон
        $tpl = file_get_contents(_ROOT_DIR_ . '/engine/core/base_layers/index_base.html');


        // Подключаем хэдер (местный или глобальный)
        if(file_exists(_MODULES_.'/_header_static.php')) include_once(_MODULES_ . '/_header_static.php');
        else include_once(_CORE_ . '/_header_static.php');
        $header = new header();
        $tpl_header = $header->getHeader();
        $tpl = set($tpl, 'include_header', $tpl_header);


        // Подключаем футер (местный или глобальный)
        if(file_exists(_MODULES_.'/_footer_static.php')) include_once(_MODULES_ . '/_footer_static.php');
        else include_once(_CORE_ . '/_footer_static.php');
        $footer = new footer();
        $tpl_footer = $footer->getFooter();
        $tpl = set($tpl, 'include_footer', $tpl_footer);


        // Подключаем скрипты и стили от модулей
        $params? $tpl = self::connect_params($tpl, 'head', $params):NULL;

        // Подключаем общие стили
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_CORE_.'/bootstrap_5.0.2/bootstrap.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_CORE_.'/bootstrap_5.0.2/bootstrap-select.min.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_CORE_.'/fontawesome_all.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_.'/media_query.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_.'/style_custom.css"/>');

        // Подключаем общие скрипты
        $tpl = setm($tpl, 'head', '<script src="'._JS_CORE_.'/jquery-3.6.0.min.js"></script>');
        $tpl = setm($tpl, 'head', '<script src="'._JS_CORE_.'/bootstrap_5.0.2/bootstrap.bundle.js"></script>');
        $tpl = setm($tpl, 'head', '<script src="'._JS_CORE_.'/_custom_utils.js"></script>');               // общие js скрипты
        $tpl = setm($tpl, 'head', '<script src="'._JS_CORE_.'/_custom_gchart.js"></script>');              // гугл графики
        $tpl = setm($tpl, 'script', '<script src="'._JS_CORE_.'/bootstrap_5.0.2/bootstrap-select.min.js"></script>');
        $tpl = setm($tpl, 'script', '<script src="'._JS_CORE_.'/run_custom_flash.js"></script>');                    // Уведомления в div и сплывающие подсказки
        $tpl = setm($tpl, 'script', '<script src="'._JS_.'/script_custom.js"></script>');


        // Добавляем контент в body
        $tpl = set($tpl, 'content', $tpl_body);

        // Добавляем служебные пути
        $tpl = set($tpl, '_CFG_', _CFG_);
        $tpl = set($tpl, '_MODULES_', _MODULES_);
        $tpl = set($tpl, '_VIEWS_', _VIEWS_);
        $tpl = set($tpl, '_CSS_', _CSS_);
        $tpl = set($tpl, '_JS_', _JS_);
        $tpl = set($tpl, '_IMG_', _IMG_);
        $tpl = set($tpl, '_CSS_CORE_', _CSS_CORE_);
        $tpl = set($tpl, '_JS_CORE_', _JS_CORE_);

        print(self::clear($tpl));
    }


    /**
     * Убираем служебные символы
     * @param $tpl  - шаблон с со скобками
     *
     * @return $tpl - шаблон без скобок
     */
    public static function clear($tpl){
        $tpl = preg_replace("~{[\w]+}~", '', $tpl);
        return $tpl;
    }

    // Подключение скриптов и стилей из контроллера
    public static function connect_params($page, $type, $params){

        // Ищем в параметре тип который необходимо добавить
        foreach($params as $param => $str_arr){
            if($param == $type){
                foreach($str_arr as $str){
                    $page = setm($page, $type, $str);
                }
            }
        }

        return $page;
    }
}