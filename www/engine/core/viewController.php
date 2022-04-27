<?php
require_once(_ROOT_DIR_ . 'engine/core/parentTemplate.php');

class viewController extends parentTemplate
{
    /**
     * Подключение параметров к странице
     */
    public static function display($tpl_body, $params=[]){

        // Получаем базовый шаблон
        $base_index = _ROOT_DIR_ . 'engine/core/base_layers/index.php';
        $tpl = file_get_contents($base_index);


        // Подключаем хэдер
        $base_header = _ROOT_DIR_ . 'engine/core/base_layers/header.html';
        $site_header = _VIEWS_.'/tpl/layers/header.html';
        $use_header = file_exists($site_header)? $site_header : $base_header;
        $tpl = set($tpl, 'include_header', file_get_contents($use_header));

        // Подключаем футер
        $base_footer = _ROOT_DIR_ . 'engine/core/base_layers/footer.html';
        $site_footer = _VIEWS_.'/tpl/layers/footer.html';
        $use_footer = file_exists($site_footer)? $site_footer : $base_footer;
        $tpl = set($tpl, 'include_footer', file_get_contents($use_footer));


        // Подключаем скрипты и стили от модулей
        $params? $tpl = self::connect_params($tpl, 'head', $params):NULL;

        // Подключаем общие стили
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_DEF_.'/bootstrap_5.0.2/bootstrap.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_DEF_.'/fontawesome_all.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_DEF_.'/chosen.min.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_.'/media_query.css"/>');
        $tpl = setm($tpl, 'head', '<link rel="stylesheet" href="'._CSS_.'/style_custom.css"/>');

        // Подключаем общие скрипты
        $tpl = setm($tpl, 'head',   '<script src="'._JS_DEF_.'/jquery-3.6.0.min.js"></script>');
        $tpl = setm($tpl, 'head', '<script src="'._JS_DEF_.'/bootstrap_5.0.2/bootstrap.bundle.js"></script>');
        $tpl = setm($tpl, 'head', '<script src="'._JS_DEF_.'/chosen.jquery.min.js"></script>');
        $tpl = setm($tpl, 'script', '<script src="'._JS_.'/script_custom.js"></script>');


        // Добавляем контент в body
        $tpl = set($tpl, 'content', $tpl_body);

        // Добавляем служебные пути
        $tpl = set($tpl, '_CFG_', _CFG_);
        $tpl = set($tpl, '_MODULES_', _MODULES_);
        $tpl = set($tpl, '_VIEWS_', _VIEWS_);
        $tpl = set($tpl, '_JS_', _JS_);
        $tpl = set($tpl, '_JS_DEF_', _JS_DEF_);
        $tpl = set($tpl, '_CSS_', _CSS_);
        $tpl = set($tpl, '_CSS_DEF_', _CSS_DEF_);
        $tpl = set($tpl, '_IMG_', _IMG_);


        // Убираем служебные символы
        $tpl = preg_replace("~{[\w]+}~", '', $tpl);


        print($tpl);
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