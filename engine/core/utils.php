<?php

/**
 * Получить шаблон из файла
 */
function get_template($folder, $file, $area = ''){

    // если папка не указана, ищем шаблон в корне tpl
    $folder = $folder? $folder.'/':'';

    if( file_exists($way = _VIEWS_.'/tpl/'.$folder.$file.'.html') ){

        // Ищем определенный блок шаблона
        if($area){
            // Шаблон по ключевым словам
            $template = "~\#\[".$area."\]\#(.+?)\#\[\!".$area."\]\#~is";

            // Ищем область по шаблону
            preg_match($template, file_get_contents($way), $match);

            $result = $match[1]? : '';
        } else{
            $result = file_get_contents($way);
        }

        return ($result)? : die('Ошибка: <br>Модуль <span style="color:red;font-weight: bold">'.$file.'.php</span> попытался вызвать шаблон <span style="color:red;font-weight: bold">{'.$area.'}</span>, но у него это не получилось <span style="font-size: 1.8em">&#128545;</span> <br> Полный путь до шаблона: <span style="color:red;font-weight: bold">'.$way.'</span>');
    } else{
        die('Ошибка: <br>Модуль <span style="color:red;font-weight: bold">'.$file.'.php</span> попытался подключить шаблон <span style="color:red;font-weight: bold">'.$way.'</span>, но такого файла не существует <span style="font-size: 1.8em">&#128545;</span>');
    }
}



/**
 * Вставка данных в шаблон
 */
function set($template, $area, $value){

    // Если есть массив с данными, первый элемент оригинальное значение, а второй - отформатированное
    if(is_array($value)){
        $value_orig = $value[1];
        $value = $value[0];
    }


    // Вставляем данные
    if(strrpos($template, "{".$area."}")){
        $template = preg_replace("~{".$area."}~", $value, $template);
    }

    // Вставляем оригинальные данные
    if(strrpos($template, "{".$area."_orig}")){
        $template = preg_replace("~{".$area."_orig}~", $value_orig, $template);
    }

    return $template;
}



/**
 * Множественная вставка данных в шаблон
 */
function setm($template, $area, $value){

    // Если есть массив с данными, первый элемент оригинальное значение, а второй - отформатированное
    if(is_array($value)){
        $value_orig = $value[1];
        $value = $value[0];
    }

    // Вставляем данные
    if(strrpos($template, "{".$area."}")){
        $template = preg_replace("~{".$area."}~", $value.'{'.$area.'}', $template);
    }

    // Вставляем оригинальные данные
    if(strrpos($template, "{".$area."_orig}")){
        $template = preg_replace("~{".$area."_orig}~", $value_orig.'{'.$area.'_orig}', $template);
    }

    return $template;
}



/**
 * Вывод технической информации в удобном виде
 */
function pr($data){

    $type = ucfirst(gettype($data));

    switch($type){
        case "String":
            $type_pr = 'pre_'.$type.' ';
            $print = '"'.showSpecChars($data).'"';
            break;
        case "Object":
            $type_pr = 'pre_';
            $print = $data;
            break;
            break;
        case "Array":
            $type_pr = 'pre_';
            $print = showSpecChars($data);
            break;
        case "Double":
        case "Integer":
            $type_pr = 'pre_'.$type.' ';
            $print = $data;
            break;
        case "Boolean":
            $type_pr = 'pre_'.$type.' ';
            $print = ($data)? 'true' : 'false';
            break;
        default:
            $type_pr = 'pre_';
            $print = 'NULL';
    }

    echo "<pre style='color: #ff5100;'><span style='color: #ff5100; font-weight: bold; font-size: 16px;'>" .$type_pr.'</span>';
    print_r(($print));
    echo "</pre>";

    return true;
}


/**
 * Выводить html сущности как они есть
 */
function showSpecChars($data){
    return (is_array($data))? array_map('showSpecChars',$data) : htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}


/**
 * Преобразование строки или массива в нужную кодировку
 */
function deepIconv($from, $to, $data){
    if (is_array($data) || is_object($data)){
        foreach ($data as &$val){
            $val= deepIconv($from, $to, $val);
        }
        return $data;
    }else{
        return iconv($from, $to, $data);
    }
}



/**
 * Отображение в формате температуры
 */
function tf($temp, $format = 'celsius'){
    $temp = round($temp);
    if(abs($temp) == 0){
        $temp = 0;
    } elseif($temp > 0){
        $plus_sign = '+';
    }

    switch ($format){
        case 'min':
            $display = '';          //
            break;
        case 'degree':
            $display = '&#176;'; // degree
            break;
        case 'celsius':
            $display = '&#8451;'; // degree Celsius
            break;
    }

    $temp = $plus_sign.$temp.$display;

    return $temp;
}



/**
 * Отображение даты в читаемом виде
 */
function df($date, $format = 'fd'){

    switch($format){
        case "sd":                       //"sd" - short date (12.05)
            $format = "d.m";
            break;
        case "fd":                       //"fd" - full date (12.05.2020)
            $format = "d.m.Y";
            break;
        case "dt":                       //"dt" - full(12.05.2020 15:26)
            $format = "d.m.Y H:i:s";
            break;
        case "st":                       // "st" - short time(18:15)
            $format = "H:i";
            break;
        case "ft":                       // "ft" - full time(18:15:51)
            $format = "H:i:s";
            break;
    }

    return date($format, $date);
}

// Конвертировать дату в формат ISO8601
function date_iso8601($date, $format_from){
    switch($format_from){
        case 'ts':
            $iso8601 = date('Ymd\THis', $date);
            break;
    }

    return $iso8601;
}

// Конвертировать дату в формат timestamp
function date_timestamp($date, $format_from){
    switch($format_from){
        case 'iso8601':
            $ts =  date("U",strtotime($date) );
            break;
    }

    return $ts;
}


/**
 * Отображение денег в читаемом виде
 * Пример: на входе число 1280.55
 */
function mf($sum, $format = 'is_penny'){

    switch($format){
        case "int":                       // без копеек (1280)
            $data = number_format(intval($sum), 0, '', '');
            break;
        case "penny":                       // с копейками всегда (1280.00). Если копеек нет, то они будут показаны в виде нулей
            $data = number_format($sum, 2, '.','');
            break;
        case "is_penny":                       // если есть копейки - показывать, если нет - не показывать
            $type = explode('.', floatval($sum));

            // Если есть число после точки, значит сумма с копейками
            if($type[1]){
                $data = number_format($sum, 2, '.','');
            } else{
                $data = number_format(intval($sum), 0, '', '');
            }
            break;
        case "space_int":                       // без копеек c пробелами у тысяч (1 280)
            $data = number_format(intval($sum), 0, '', ' ');
            break;
        case "space_penny":                       // с копейками всегда + c пробелами у тысяч (1 280.00)
            $data = number_format($sum, 2, '.',' ');
            break;
        case "space_is_penny":                       // если есть копейки - показывать, если нет - не показывать + c пробелами у тысяч (1 280.55)
            $type = explode('.', floatval($sum));

            // Если есть число после точки, значит сумма с копейками
            if($type[1]){
                $data = number_format($sum, 2, '.',' ');
            } else{
                $data = number_format(intval($sum), 0, '', ' ');
            }
            break;
    }

    return $data;
}


/**
 * Направление ветра
 */
function wind_arrow($deg){

    switch($deg) {
        case 0:
        case ($deg <= 22):
            $direct = 'южный';
            break;
        case ($deg <= 67):
            $direct = 'юго-западный';
            break;
        case ($deg <= 112):
            $direct = 'западный';
            break;
        case ($deg <= 157):
            $direct = 'северо-западный';
            break;
        case ($deg <= 202):
            $direct = 'северный';
            break;
        case ($deg <= 247):
            $direct = 'северо-восточный';
            break;
        case ($deg <= 292):
            $direct = 'восточный';
            break;
        case ($deg <= 337):
            $direct = 'юго-восточный';
            break;
        case ($deg <= 360):
            $direct = 'южный';
            break;
    }

    return $direct;
}



// Первая заглавная буква (для utf-8)
function ucfirst_utf8($str){
    return mb_substr(mb_strtoupper($str, 'utf-8'), 0, 1, 'utf-8') . mb_substr($str, 1, mb_strlen($str)-1, 'utf-8');
}



// Определяем день недели
function day_of_week($num_day, $format){
    $title = [
        1 => [
            'ru_full' => 'Понедельник',
            'ru_short' => 'Пн'
        ],
        2 => [
            'ru_full' => 'Вторник',
            'ru_short' => 'Вт'
        ],
        3 => [
            'ru_full' => 'Среда',
            'ru_short' => 'Ср'
        ],
        4 => [
            'ru_full' => 'Четверг',
            'ru_short' => 'Чт'
        ],
        5 => [
            'ru_full' => 'Пятница',
            'ru_short' => 'Пт'
        ],
        6 => [
            'ru_full' => 'Суббота',
            'ru_short' => 'Сб'
        ],
        0 => [
            'ru_full' => 'Воскресенье',
            'ru_short' => 'Вс'
        ],

    ];

    return $title[$num_day][$format];
}


// Определяем иконку погоды
// https://openweathermap.org/weather-conditions#How-to-get-icon-URL
function getNameWeatherIcon($wather_arr){

    $icon_arr = [
        // group 2хх: Thunderstorm (Гроза)
        '200' => ['default' => '2xx_3',     'sunny' => '2xx_2'],   // is_sunny
        '201' => ['default' => '2xx_3',     'sunny' => ''],
        '202' => ['default' => '2xx_4',     'sunny' => ''],
        '210' => ['default' => '2xx_1',     'sunny' => '2xx_2'],   // is_sunny
        '211' => ['default' => '2xx_1',     'sunny' => ''],
        '212' => ['default' => '2xx_1',     'sunny' => ''],
        '221' => ['default' => '2xx_1',     'sunny' => ''],
        '230' => ['default' => '2xx_3',     'sunny' => '2xx_2'],   // is_sunny
        '231' => ['default' => '2xx_3',     'sunny' => ''],
        '232' => ['default' => '2xx_4',     'sunny' => ''],

        // group 3хх: Drizzle (Морось)
        '300' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '301' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '302' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '310' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '311' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '312' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '313' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '314' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '321' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny

        // group 5хх: Rain (Дождь)
        '500' => ['default' => '4xx_1',     'sunny' => ''],
        '501' => ['default' => '4xx_2',     'sunny' => ''],
        '502' => ['default' => '4xx_3',     'sunny' => ''],
        '503' => ['default' => '4xx_3',     'sunny' => ''],
        '504' => ['default' => '4xx_3',     'sunny' => ''],
        '511' => ['default' => '8xx_4',     'sunny' => ''],
        '520' => ['default' => '4xx_1',     'sunny' => '3xx'],   // is_sunny
        '521' => ['default' => '4xx_2',     'sunny' => '3xx'],   // is_sunny
        '522' => ['default' => '4xx_3',     'sunny' => '3xx'],   // is_sunny
        '531' => ['default' => '4xx_4',     'sunny' => ''],

        // group 6хх: Snow (Снег)
        '600' => ['default' => '6xx_1',     'sunny' => ''],
        '601' => ['default' => '6xx_2',     'sunny' => ''],
        '602' => ['default' => '6xx_3',     'sunny' => ''],
        '611' => ['default' => '6xx_4',     'sunny' => ''],
        '612' => ['default' => '6xx_4',     'sunny' => '6xx_5'],   // is_sunny
        '613' => ['default' => '6xx_6',     'sunny' => ''],
        '615' => ['default' => '6xx_6',     'sunny' => ''],
        '616' => ['default' => '6xx_4',     'sunny' => ''],
        '620' => ['default' => '6xx_1',     'sunny' => '6xx_9'],   // is_sunny
        '621' => ['default' => '6xx_2',     'sunny' => '6xx_10'],   // is_sunny
        '622' => ['default' => '6xx_3',     'sunny' => '6xx_10'],   // is_sunny

        // group 7хх: Atmosphere (Атмосфера)
        '701' => ['default' => '8xx_4',     'sunny' => ''],
        '711' => ['default' => '8xx_4',     'sunny' => ''],
        '721' => ['default' => '8xx_4',     'sunny' => ''],
        '731' => ['default' => '8xx_4',     'sunny' => ''],
        '741' => ['default' => '8xx_4',     'sunny' => ''],
        '751' => ['default' => '8xx_4',     'sunny' => ''],
        '761' => ['default' => '8xx_4',     'sunny' => ''],
        '762' => ['default' => '8xx_4',     'sunny' => ''],
        '771' => ['default' => '8xx_4',     'sunny' => ''],
        '781' => ['default' => '8xx_4',     'sunny' => ''],

        // group 800: Clear (Ясно)
        '800' => ['default' => '888',       'sunny' => ''],   // is_sunny

        // group 80х: Clouds (Облачность)
        '801' => ['default' => '8xx_4',     'sunny' => '8xx_1'],   // is_sunny
        '802' => ['default' => '8xx_4',     'sunny' => '8xx_2'],   // is_sunny
        '803' => ['default' => '8xx_4',     'sunny' => '8xx_3'],   // is_sunny
        '804' => ['default' => '8xx_4',     'sunny' => ''],
    ];


    // Проверяем есть ли солнечный свет
    $sunlight = isSunLight($wather_arr['curr_sunrise'][0], $wather_arr['curr_sunset'][0]);

    // Наличие большого количества облаков
    $cloudly = ($wather_arr['curr_clouds'] > 25)? true : false;


    // Determine icon
    if($sunlight){

        // Иконка по умолчанию
        $cur_id = $wather_arr['curr_w_id'];
        $icon_name = $icon_arr[$cur_id]['default'];

        // Если есть солнечный свет, малооблачно и есть солнечная иконка - то используем её
        // if is sunlight and clouds < 25% and is sunny icon
        if($sunlight && !$cloudly && $icon_arr[$cur_id]['sunny']){
            $icon_name = $icon_arr[$cur_id]['sunny'];
        }

    } else{
        $icon_name = '000';     // Иконка для ночного времени суток
    }

    return $icon_name;
}


// Проверка на наличие солнечного света
function isSunLight($sunrise, $sunset){
    $time_now = date('H:i:s');

    return ( ($time_now > $sunrise) && ($time_now < $sunset))? true : false;
}


//Качественная характеристика скорости ветра
//Диапазон скорости ветра, м/с
//
//Слабый      0-5
//Умеренный   6-14
//Сильный     15-24
//Очень сильный 25-32
//Ураганный 33 и более
function powerWind($speed){
    switch($speed){
        case ($speed < 6):
            $power = 'cлабый';
            break;
        case ($speed < 15):
            $power = 'умеренный';
            break;
        case ($speed < 25):
            $power = 'сильный';
            break;
        case ($speed < 33):
            $power = 'очень сильный';
            break;
        case ($speed):
            $power = 'ураганный';
            break;
    }

    return $power;
}



//Кол-во осадков дождя, мм/12 час
//
//Без осадков, сухая погода - 0 мм.
//Небольшой дождь, слабый дождь, морось, моросящие осадки, небольшие осадки - 0-2 мм.
//Дождь, дождливая погода, осадки, мокрый снег, дождь со снегом; снег, переходящий в дождь; дождь, переходящий в снег 3-14 мм.
//Сильный дождь, ливневый дождь (ливень), сильные осадки, сильный мокрый снег, сильный дождь со снегом, сильный снег с дождем 15-49 мм.
//Очень сильный дождь, очень сильные осадки (очень сильный мокрый снег, очень сильный дождь со снегом, очень сильный снег с дождем) ≥ 50  мм.
//
//
//
//Кол-во осадков снега, мм/12 час
//
//Без осадков, сухая погода -  0 мм.
//Небольшой снег, слабый снег 0-1 мм.
//Снег, снегопад 2-5 мм.
//Сильный снег, сильный снегопад 6-19 мм.
//Очень сильный снег, очень сильный снегопад ≥ 20
function powerRainSnow($weather_id, $precipitation){

    // по умолчанию количество осадков приходит в размере мм/час
    // переводим в мм/12 ч
    $precipitation = $precipitation * 12;

    //$icon_n = '<i class="fas fa-ban"></i>';                // old none icon
    $icon_n = '<span class="none-dash">---</span>';          // none
    $icon_r = '<i class="rain_ico fas fa-tint"></i>';        // rain
    $icon_s = '<i class="snow_ico fas fa-snowflake"></i>';   // snow

    // determine type precipitation
    switch($weather_id){

        // SNOW
        case '600':   // light snow (небольшой снегопад)
        case '601':   // Snow (снегопад)
        case '602':   // Heavy snow (сильный снегопад)
        case '620':   // Light shower snow (кратковременный небольшой снегопад)
        case '621':   // Shower snow (кратковременный снегопад)
        case '622':   // Heavy shower snow (кратковременный сильный снегопад)

            if($precipitation == 0){              // Без осадков
                $icon_block = $icon_n;

            } elseif( $precipitation <= 1 ){      // Небольшой снег, слабый снег
                $icon_block = $icon_s;

            } elseif( $precipitation <= 5 ){      // Снег, снегопад
                $icon_block = $icon_s.$icon_s;

            } elseif( $precipitation <= 19 ){     // Сильный снег, сильный снегопад
                $icon_block = $icon_s.$icon_s.$icon_s;

            } elseif( $precipitation >= 50 ){     // Очень сильный снег, очень сильный снегопад
                $icon_block = $icon_s.$icon_s.$icon_s.$icon_s;

            }
            break;

        // RAIN
        default :   // Snow (снег)
            if($precipitation == 0){              // Без осадков
                $icon_block = $icon_n;

            } elseif( $precipitation <= 2 ){      // Небольшой дождь, слабый дождь, морось, моросящие осадки, небольшие осадки
                $icon_block = $icon_r;

            } elseif( $precipitation <= 14 ){     // Дождь, дождливая погода, осадки, мокрый снег, дождь со снегом; снег, переходящий в дождь; дождь, переходящий в снег
                $icon_block = $icon_r.$icon_r;

            } elseif( $precipitation <= 49 ){     // Сильный дождь, ливневый дождь (ливень), сильные осадки, сильный мокрый снег, сильный дождь со снегом, сильный снег с дождем
                $icon_block = $icon_r.$icon_r.$icon_r;

            } elseif( $precipitation >= 50 ){     // Очень сильный дождь, очень сильные осадки (очень сильный мокрый снег, очень сильный дождь со снегом, очень сильный снег с дождем)
                $icon_block = $icon_r.$icon_r.$icon_r.$icon_r;
            }

            break;
    }


    return $icon_block;
}

/** Detect type client OS */
function getOS() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'];

    $oses = [
        'smart_mirror' => '~ALCATEL ONE TOUCH P320X~',
        'android' => '~[Aa]ndroid~',
        'ios' => '~iOS~',
        'windows' => '~[Ww]indows~',
        'linux' => '~[Ll]inux~'
    ];

    foreach($oses as $os => $pattern){
        $matches = preg_match($pattern, $userAgent);

        if($matches) { // Пройдемся по массиву $oses для поиска соответствующей операционной системы.
            return $os;
        }
    }

    return 'Unknown'; // Хрен его знает, чего у него на десктопе стоит.
}

/** Редирект в указанное место */
function _redirect($url='', $header_code = 0){

    if($url){
        header("Location: " . $url);

        exit;
    }

    if($header_code){
        switch($header_code){
            case 404:
                http_response_code(404);
                include(_ROOT_DIR_ . '/404.php');
                die();
            break;
        }

        exit;
    }
}

// Определяем тип баркода по его ключам
function get_type_barcode($barcode_arr, $barcode_orig = ''){

    $receipt = array_flip(['t', 's', 'fn', 'i', 'fp', 'n']);    // Обязательные ключи для кассового чека

    switch($barcode_arr){
        case array_diff_key($receipt, $barcode_arr) == false:
            $type = 'receipt';
            break;

        case preg_match('~^http~', $barcode_orig) == true:
            $type = '[Cсылка => '.$barcode_orig.']';
            break;
        default:
            $type = 'unknown';
    }

    return $type;
}

/** Находим страницу на которой расположена запись flow*/
function getPageByDate($date_ts){
    $page = '';
    $date_format = date('m.Y', $date_ts);

    if( $date_format!= date('m.Y', time()) ){
        $page = '&page='. $date_format;
    }

    return $page;
}

/** Добавляем сообщение в log */
function add_log($str, $filename = '', $err_lvl = 0){
    $err_arr = [0=>'', 1=>'INFO',2=>'WARNING', 3=>'ERROR'];
    $logs_dir = _ROOT_DIR_.'/logs/'._SITE_;
    if( !is_dir($logs_dir) ) mkdir($logs_dir, 0755);
    if(!$filename) $filename = '_common.log';

    $path = pathinfo($filename);
    if( in_array($path['dirname'], ['.','/']) ){
        $full_dir = $logs_dir;
    } else{
        $full_dir = $logs_dir .'/'. trim($path['dirname'], './');
    }
    if( !is_dir($full_dir) ) mkdir($full_dir, 0755, true);

    $full_filename = $full_dir .'/'. $path['basename'];

    file_put_contents($full_filename, date('[Y-m-d H:i:s]') .' '. $err_arr[$err_lvl] .' '. $str . PHP_EOL, FILE_APPEND|LOCK_EX);

    return;
}


// Сколько прошло времени с определенного момента
function diff_time($last_time, $cur_time = 0){
    if( !$cur_time ) $cur_time = time();

    $dt_last = new DateTime();
    $dt_last->setTimestamp($last_time);

    $dt_cur = new DateTime();
    $dt_cur->setTimestamp($cur_time);

    $diff = $dt_last->diff($dt_cur);

    switch(true){
        case (($diff->y > 0) == true):
            $res = $diff->y.' '.num_translit($diff->y, "Y").' назад';
            break;
        case (($diff->m > 0) == true):
            $res = $diff->m.' '.num_translit($diff->m, "m").' назад';
            break;
        case (($diff->d > 0) == true):
            $res = $diff->d.' '.num_translit($diff->d, "d").' назад';
            break;
        case (($diff->h > 0) == true):
            $res = $diff->h.' '.num_translit($diff->h, "H").' назад';
            break;
        case (($diff->i > 0) == true):
            $res = $diff->i.' '.num_translit($diff->i, "i").' назад';
            break;
        case (($diff->s > 0) == true):
            $res = $diff->s.' '.num_translit($diff->s, "s").' назад';
            break;
        default:
            $res = 'только что';
            break;
    }

    return $res;
}

/**
 * Получаем период дат в определенном формате
 * @param int   $from_ts    - Начальная дата
 * @param int   $to_ts      - Конечная дата
 * @param string $period    - Период в формате 'P1M'(Каждый 1 месяц), 'P15D'(Каждый 15 день)
 * @param string $format    - Формат нужной даты 'd.m.Y'
 *
 * @return array            - Массив с датами
 * @throws Exception
 */
function getPeriod($from_ts, $to_ts, $period, $format, $order_by = 'desc'){
    $from = new DateTime();
    $from->setTimestamp($from_ts);
    $to   = new DateTime();
    $to->setTimestamp($to_ts);

    $period = new DatePeriod($from, new DateInterval($period), $to);
    foreach($period as $date){
        $period_map[] = $date->format($format);
    }

    // По умолчанию даты отсортированы от прошлой к текущей ('asc')
    // Если включено 'desc', пересортируем даты от текущей к прошлой
    if($order_by == 'desc') krsort($period_map);


    return $period_map;
}

/**
 * Получить первый и последний день месяца
 * @param array $date_arr - формата [0 => ['year' => '2022', 'month' => '06'], 1 => ['year' => 2022, 'month' => '07']]
 */
function getStartEndDays($date_arr){
    $result = [];
    
    foreach($date_arr as $date){
        $year = (int) $date['year'];
        $month = (int) $date['month'];

        // Начало месяца ts
        $ts_start_month = mktime(0,0,0, $month, 1, $year);

        // Находим последний день месяца
        $datetime = new DateTime();
        $datetime->setTimestamp($ts_start_month);
        $last_day = intval($datetime->format( 't' ));

        // Конец месяца ts
        $ts_end_month = mktime(23,59,59,(int) $month, $last_day, $year);

        $result[$year][$month] = ['start' => $ts_start_month, 'end' => $ts_end_month];
    }

    return $result;
}

/**
 * Транлитерация из кириллицы в латиницу
 * @param $text
 *
 * @return string
 */
function translit($text){
    $converter = array(
        'а' => 'a',   'б' => 'b',   'в' => 'v',    'г' => 'g',   'д' => 'd',   'е' => 'e',
        'ё' => 'e',   'ж' => 'zh',  'з' => 'z',    'и' => 'i',   'й' => 'y',   'к' => 'k',
        'л' => 'l',   'м' => 'm',   'н' => 'n',    'о' => 'o',   'п' => 'p',   'р' => 'r',
        'с' => 's',   'т' => 't',   'у' => 'u',    'ф' => 'f',   'х' => 'h',   'ц' => 'c',
        'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',  'ь' => '',    'ы' => 'y',   'ъ' => '',
        'э' => 'e',   'ю' => 'yu',  'я' => 'ya',

        'А' => 'A',   'Б' => 'B',   'В' => 'V',    'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
        'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',    'И' => 'I',   'Й' => 'Y',   'К' => 'K',
        'Л' => 'L',   'М' => 'M',   'Н' => 'N',    'О' => 'O',   'П' => 'P',   'Р' => 'R',
        'С' => 'S',   'Т' => 'T',   'У' => 'U',    'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
        'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',  'Ь' => '',    'Ы' => 'Y',   'Ъ' => '',
        'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
    );

    $text = strtr($text, $converter);

    return $text;
}


/**
 * Правильное произношение цифр
 * @param $number
 * @param $format - формат даты (берется такой же как у date())
 * return string
 */
function num_translit($number, $format){
    $type = [
      0 => ['Y' => 'год', 'y' => 'год', 'm' => 'месяц', 'd' => 'день', 'H' => 'час', 'i' => 'минуту', 's' => 'секунду', 'u' => 'миллесекунду'],
      1 => ['Y' => 'года', 'y' => 'года', 'm' => 'месяца', 'd' => 'дня', 'H' => 'часа', 'i' => 'минуты', 's' => 'секунды', 'u' => 'миллесекунды'],
      2 => ['Y' => 'лет', 'y' => 'лет', 'm' => 'месяцев', 'd' => 'дней', 'H' => 'часов', 'i' => 'минут', 's' => 'секунд', 'u' => 'миллесекунд'],
    ];

    $num = $number % 100;
	if ($num > 19) {
		$num = $num % 10;
	}

    // Последняя цифра
    $digit = substr($number, -1);

    switch($digit){
        case 1:
            $str = $type[0][$format];
            break;
        case 2:
        case 3:
        case 4:
            $str = $type[1][$format];
            break;
        default:
            $str = $type[2][$format];
    }

    return $str;
}


/**
 * Экранирование как у оригинальная mysql_real_escape_string
 * (но не нужно активное сетевое подключение)
 * @param string $inp - входящая строка
 * @return array|mixed
 */
function mysql_escape_mimic($inp) {
    if(is_array($inp))
        return array_map(__METHOD__, $inp);

    if(!empty($inp) && is_string($inp)) {
        return str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $inp);
    }

    return $inp;
}
