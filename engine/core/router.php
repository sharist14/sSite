<?php
require_once(_ROOT_DIR_ . '/engine/core/Db.php');

class router
{

    public function run(){

        //
        // ПРОВЕРКА КОРРЕКТНОСТИ САЙТА
        //

        // Проверяем наличие поддомена в запросе
        preg_match('~(\.?\w+)+(?<!www)(?=\.\w+\.\w+(?:$|\/))~', $_SERVER['HTTP_HOST'], $match);

        // Парсим УРЛ
        $way =  trim($_SERVER['REQUEST_URI'], '/');

        // Назначаем имя сайта (поддомен или домен)
        if( isset($match[0]) ){
            $site = $match[0];
        } else{

            $part_way = explode('/',$way);

            if($part_way[0] != 'ext') $site = '_default';
        }

        // Проверяем наличие необходимых файлов и папок
        $modules_dir = _ROOT_DIR_ . '/engine/modules/sites/' . $site;
        $view_folder = 'views/sites/' . $site;

        if( is_dir($modules_dir) && is_dir($view_folder) ){
            define('_CORE_',        'engine/core');                               // engine сайта
            define('_CFG_',         'config/sites/' . $site);                     // Конфиг сайта
            define('_MODULES_',     'engine/modules/sites/' . $site);             // Модули сайта
            define('_VIEWS_',       'views/sites/' . $site);                      // View сайта
            define('_SITE_',        $site);                                       // Название сайта

            define('_STATIC_URL_',  'https://'.$_SERVER['HTTP_HOST']);
            define('_JS_',          _STATIC_URL_ . '/' . _VIEWS_ . '/sources/js');      // JS сайта
            define('_CSS_',         _STATIC_URL_ . '/' . _VIEWS_ . '/sources/css');     // CSS сайта
            define('_IMG_',         _STATIC_URL_ . '/' . _VIEWS_ . '/sources/img');     // IMG сайта
            define('_CSS_CORE_',     _STATIC_URL_ .'/engine/core/common_css');          // общие CSS
            define('_JS_CORE_',      _STATIC_URL_ .'/engine/core/common_js');           // общие JS

            define('_FILES_', 'files/'.$site);
            if( !is_dir(_FILES_) ){
                mkdir(_FILES_, 0755, true);
            }

            define('_LOGS_', 'logs/'.$site);
            if( !is_dir(_LOGS_) ){
                mkdir(_LOGS_, 0755, true);
            }

            // Подключаем конфиги
            require_once(_ROOT_DIR_ . '/config/secret_common.php');  // общие секреты
            require_once(_CFG_ . '/secrets.php');                   // секреты сайтов

            // Автоподключение модулей сайта
            require_once(_CORE_ . '/autoloader.php');
            spl_autoload_register('include_module');

            // Локальные утилиты
            $local_utils = _MODULES_.'/utils_'.$site.'.php';
            if(file_exists($local_utils)) require_once($local_utils);

        } else{

           _redirect('',404);
        }


        $url_components = parse_url($way);      // Разбиваем строку идущую после имени хоста на контроллер и параметры


        //
        // КОНТРОЛЛЕР
        //

        if( !empty($url_components['path']) ) {
            $controller_data = trim($url_components['path'], '/');
            $controller_data = explode('/', $controller_data);

            // Если указано больше одного контроллера
            if (count($controller_data) > 1) {
                _redirect('', 404);
            }

            $controller_name = array_shift($controller_data);
        } else{
            $controller_name = 'index';            
        }


        $controller_file = _MODULES_ .'/'. $controller_name . '.php';

        // Проверяем наличие файла контроллера
        if( file_exists($controller_file) ){
            require_once($controller_file);
        }

        // Проверяем наличие класса
        if( !class_exists($controller_name) ){
            _redirect('',404);
        }


        //
        // ПАРАМЕТРЫ ЗАПРОСА
        //

        parse_str($url_components['query'], $query_data);


        // Определяем имя метода
        if($query_data['act']){
            $ajax_query = preg_match('~^_ajax_~', $query_data['act']);

            if($ajax_query){
                $method_name = $query_data['act'];              // Ajax запрос с именем метода
            } else{
                $method_name = '_act_' . $query_data['act'];    // Обычный запрос с именем метода
            }
        } else{
            $method_name = '_act_';                             // Имя по умолчанию
        }


        // Если метод существует, передаём ему управление
        if(method_exists($controller_name, $method_name)){
            $controller = new $controller_name($query_data);
            $controller->$method_name();

        } else{
            _redirect('',404);
        }


        die();
    }
}