<?php
/** Роутер для различных api */
// todo пока не работает, т.к. доступ к сайту закрыт ssl auth и авторизацией по логину/паролю

/* Подключаем пути */
define( '_SITE_', '_default');
define( '_ROOT_DIR_', getcwd() . '/..' );
require_once(_ROOT_DIR_ . '/engine/core/loader.php');


// Определяем основную папку и внутренние папки
$data = parse_url($_SERVER["REQUEST_URI"]);
$way_str = trim($data['path'],'/');     // убираем лишние слеш
$way_arr = explode('/', $way_str);     // переводим путь в массив
array_shift($way_arr);                  // убрали слово ext_api
$API_FOLDER_MAIN = array_shift($way_arr);    // определили основную папку
$API_FOLDER_INNER = $way_arr;


// Получаем параметры из url
$API_PARAMS = $_REQUEST;

if($_SERVER["REQUEST_METHOD"] == "POST") {
    
    switch($_SERVER["CONTENT_TYPE"]) {
        case "application/json":        // json данные
            $body_json = file_get_contents("php://input", false, stream_context_get_default(), 0, $_SERVER["CONTENT_LENGTH"]);
            $API_BODY_PARAMS = json_decode($body_json, true);
        
            // Сливаем параметры из url с параметрами из тела
            if (is_array($body_arr)) $API_PARAMS = $API_BODY_PARAMS + $API_PARAMS;
            break;
    }
}


// Добавляем инфо по запросу в лог
$post_body_log = '';
if($body_json) $post_body_log = 'BODY: ' . json_encode($API_BODY_PARAMS, JSON_UNESCAPED_UNICODE) . PHP_EOL;
$log = '<<< '.$_SERVER['REMOTE_ADDR'] . PHP_EOL . $_SERVER['REQUEST_METHOD'] .' ' . $_SERVER['REQUEST_URI'] . PHP_EOL . $post_body_log;
add_log($log, 'api_' . $API_FOLDER_MAIN .'.log');


echo 'OK';
die();