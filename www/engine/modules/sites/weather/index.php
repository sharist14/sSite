<?php
require_once(_MODULES_ . '/api/weather.api.php');


class index extends parentTemplate
{
    var $api;
    public $openweather_icon = '<img src="https://openweathermap.org/img/wn/';


    public function __construct($params = []){
        $this->api = new weatherApi();  // подключаем api

        $this->params = $params;        // Получаем параметры запроса

        return parent::__construct();
    }


    /**
     * Точка входа по умолчанию
     */
    public function _act_(){
        $tpl = get_template('', $this->module, 'body');

        // Получаем данные погоды по api
        $wObj = $this->api->getWeather();
        if(!$wObj) die('Не удалось получить корректные данные о погоде по api');


        /******* БЛОК ТЕКУЩЕЙ ПОГОДЫ *******/
        // set dates
        foreach($wObj->current as $title => $value){

            // Цвет текущей температуры
            if($title == 'curr_temp'){
                $temp = intval($value[0]);
                $color_temp = ($temp > 0)? 'temp_plus' : (($temp < 0)? 'temp_minus' : '');
                $tpl = set($tpl, 'color_temp', $color_temp );
            }

            // Показываем видимость в м или км
            if($title == 'curr_visibility'){
                $value_km = floatval( number_format(($value / 1000), 1, '.', '') );
                $value = ($value < 1000)? $value.' м' : $value_km.' км';
            }

            $tpl = set($tpl, $title, $value );
            
            

            // power wind
            if($title == 'curr_wind_speed'){
                $wind_power = powerWind($value);
                $tpl = set($tpl, 'curr_wind_char', $wind_power );
            }
        }


        // Проверка на старые версии Андройд
        $cli_os = getOS();
        preg_match('~[aA]ndroid\s[1-4]~', $cli_os, $old_device);
        $use_main_video_icon = (!$old_device)? true : false;


        // use private video icon or public image icon
        $icon_name = getNameWeatherIcon($wObj->current);


        if($use_main_video_icon){
            $tpl = set($tpl, 'weather_video_icon', _VIEWS_.'/sources/icon/'.$icon_name.'.mp4');
            $tpl = set($tpl, 'display_iblock', 'hidden');

        } else{

            if($old_device){
                // для старых Андройд показываем gif
                $curr_icon = '<img src="'._VIEWS_DIR_.'sources/icon/'.$icon_name.'.gif">';
            } else{
                // Иконка от weather api
                $curr_icon = $this->openweather_icon . $wObj->current['curr_icon'] . '@4x.png">';
            }

            $tpl = set($tpl, 'weather_img_icon', $curr_icon);

            $tpl = set($tpl, 'display_vblock', 'hidden');
        }


        // Если есть осадки (таблица интенсивности осадков https://meteoinfo.ru/forcabout/3891-nast-kpp)
        $period_minutes = 0;
        $weather_id = $wObj->current['curr_w_id'];

        // передаем в powerRainSnow() тип осадков и значение и получаем в виде капелек или снежинок
        foreach($wObj->rain as $datetime => $precipitation){

            switch($period_minutes){
                case '5':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">05 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $tpl = setm($tpl, 'intensity_rows', $div);
                    break;
                case '30':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">30 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $tpl = setm($tpl, 'intensity_rows', $div);
                    break;
                case '60':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">60 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $tpl = setm($tpl, 'intensity_rows', $div);
                    break;
            }

            $period_minutes++;
        }
        /******* конец БЛОКА ТЕКУЩЕЙ ПОГОДЫ *******/



        /******* БЛОК ПОГОДЫ ПОЧАСОВОЙ c 6-23 часов (график Google Charts) *******/
        $tplch = get_template('',$this->module, 'google_chart_data');
        $hour_chart = '';
        foreach($wObj->hourly as $datetime => $arr){

            // Берем только данные за текущий день
            if(date('Ymd',$datetime) == date('Ymd')){
                $hour = date('H', $datetime);

                $hour_temp = round($arr["hour_temp"]);
                $hour_chart .= '[{v: ['.$hour.', 0, 0], f: "'.$hour.':00"},   '.$hour_temp.', "'.$hour_temp.'"],';
            }
        }

        // Вставляем данные
        $curr_hour = intval(date('H'));
        $lost_hours = 24 - $curr_hour;                                        // Проверяем сколько осталось часов до полуночи
        $width = round(($lost_hours * 100) / 17);                         // Вычисляем размер области графика (с расчетом что 17 часов до получночи занимают 100% ширины)
        $tplch = set($tplch, 'curr_hour_min', $curr_hour - 1);
        $tplch = set($tplch, 'curr_hour_width', $width);
        $tplch = set($tplch, 'hour_chart_data', $hour_chart);           // Добавляем данные в график

//        $tpl = set($tpl, 'google_chart_csript', $tplch);
        /******* конец БЛОК ПОГОДЫ ПОЧАСОВОЙ c 6-23 часов (график Google Charts) *******/


        /******* начало БЛОК ПОГОДЫ ПОЧАСОВОЙ на ближайшие 6 часов *******/
//        $tpl6ch = get_template('',$this->module, 'hour_row');
//        foreach($wObj->hourly as $datetime => $arr){
//
//            // Берем только данные за текущий день
//            if(date('Ymd',$datetime) == date('Ymd')){
//                $hour = date('H', $datetime);
//
//                $hour_temp = round($arr["hour_temp"]);
//                $hour_chart .= '[{v: ['.$hour.', 0, 0], f: "'.$hour.':00"},   '.$hour_temp.', "'.$hour_temp.'"],';
//            }
//        }

        $i = 1;
        $hour_row = get_template('', $this->module, 'hour_row');

        
        foreach($wObj->hourly as $datetime => $arr){
            if($i > 6) break;
//            pre($arr);
            $tt = $hour_row;
            
//            pre($arr);
//
//            $num_day = date('w', $datetime);
//            $name_day = day_of_week($num_day, 'ru_short');    // День недели
            $hour = date('H', $datetime);                   // Текущий час

            $hour_now = date('H', time());
            if($hour_now == $hour) continue;
//
//            $tt = set($tt, 'day_of_weak', $name_day);
            $tt = set($tt, 'time', $hour . ':00');
            $tt = set($tt, 'temp',  tf($arr['hour_temp']));
//
            $curr_icon = $this->openweather_icon . $arr['hour_icon'] . '@2x.png">';
            $tt = set($tt, 'icon', $curr_icon);
//
//            // Выделяем сб и вс
//            if($num_day == 6 || $num_day == 0) {
//                $tt = set($tt, 'selected', 'selected_day');
//            }

            // Добавляем все даты
            $tpl = setm($tpl, 'hour_rows', $tt);

            $i++;
        }
        /******* конец БЛОК ПОГОДЫ ПОЧАСОВОЙ на ближайшие 6 часов *******/

        /******* БЛОК ПОГОДЫ ПО ДНЯМ *******/
        $day_row = get_template('', $this->module, 'day_row');

        foreach($wObj->daily as $datetime => $arr){

            $tt = $day_row;

            $num_day = date('w', $datetime);
            $name_day = day_of_week($num_day, 'ru_short');    // День недели
            $date = date('d.m', $datetime);                   // Дата в формате "14.02"

            $tt = set($tt, 'day_of_weak', $name_day);
            $tt = set($tt, 'date', $date);
            $tt = set($tt, 'temp',  tf($arr['day_temp']));

            $curr_icon = $this->openweather_icon . $arr['day_w_icon'] . '.png">';
            $tt = set($tt, 'icon', $curr_icon);

            // Выделяем сб и вс
            if($num_day == 6 || $num_day == 0) {
                $tt = set($tt, 'selected', 'selected_day');
            }

            // Добавляем все даты
            $tpl = setm($tpl, 'days_rows', $tt);
        }
        /******* конец БЛОК ПОГОДЫ ПО ДНЯМ *******/


        // Заносим данные в шаблон погоды
        $tpl = set($tpl, 'weather', $tpl);


        // Отправляем на рендер
        $this->render($tpl, $this->include);
    }


    /**
    * Подключение скриптов и стилей к странице
    */
    public function getInclude(){
        $include['head'][] = '<link rel="stylesheet" href="'._CSS_.'/weather_animated_icons.css">';
        $include['head'][] = '<link rel="stylesheet" href="'._CSS_.'/weather-icons.css">';
        $include['head'][] = '<link rel="stylesheet" href="'._CSS_.'/weather-icons-wind.css">';
        $include['head'][] = '<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">';
        $include['head'][] = '<link href="https://fonts.googleapis.com/css2?family=Work+Sans:wght@300&display=swap" rel="stylesheet">';
        $include['head'][] = '<link href="https://fonts.googleapis.com/css2?family=Lato&display=swap" rel="stylesheet">';
        $include['head'][] = '<link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet">';

        // Библиотека для построения часового графика
        $include['head'][] = '<script src="https://www.gstatic.com/charts/loader.js"></script>';

        return $include;
    }


}