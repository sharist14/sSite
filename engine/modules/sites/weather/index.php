<?php
require_once(_MODULES_ . '/api/weather.api.php');


class index extends parentTemplate
{
    public $api;
    public $openweather_icon = '<img src="https://openweathermap.org/img/wn/';


    public function __construct($params = []){
        $this->api = new weatherApi();  // подключаем api

        $this->params = $params;        // Получаем параметры запроса

        return parent::__construct();
    }


    /**
     * Главная (site: weather, модуль index)
     */
    public function _act_(){
        $device = getOS();

        // Определяем шаблон страницы
        switch($device){
            case 'smart_mirror': $tpl_file = 'tablet_page'; break;
            case 'ios':
            case 'android': $tpl_file = 'mobile_page'; break;
            case 'linux':
            case 'windows': $tpl_file = 'pc_page'; break;
            default: $tpl_file = '';
        }

        $tpl = get_template('os_template', $tpl_file);


        // Отправляем на рендер
        $this->render($tpl, $this->include);
    }

    /**
     * Получаем данные по погоде
     */
    public function _ajax_getData(){
        $res = [];

        // Получаем данные погоды по api
        $wObj = $this->api->getWeather();
        if(!$wObj){
            $res['error'] = 'Не удалось получить корректные данные о погоде по api';
            die(json_encode($res, JSON_UNESCAPED_UNICODE));
        }


        /******* БЛОК ТЕКУЩЕЙ ПОГОДЫ *******/
        // set dates
        foreach($wObj->current as $title => $value){

            // Цвет текущей температуры
            if($title == 'curr_temp'){
                $temp = intval($value[0]);
                $color_temp = ($temp > 0)? 'temp_plus' : (($temp < 0)? 'temp_minus' : '');
                $res['current']['color_temp'] = $color_temp;

            }

            // поле "Ветер"
            if($title == 'curr_wind_speed'){
                $wind_power = powerWind($value);
                $res['current']['curr_wind_char'] = $wind_power;
            }


            // поле "Видимость" (в м или км)
            if($title == 'curr_visibility'){
                $value_km = floatval( number_format(($value / 1000), 1, '.', '') );
                $value = ($value < 1000)? $value.' м' : $value_km.' км';
            }


            // Добавляем данные по массив
            $res['current'][$title] = $value;
        }


        // Проверка на старые версии Андройд
        $cli_os = getOS();
        preg_match('~[aA]ndroid\s[1-4]~', $cli_os, $old_device);
        $use_main_video_icon = (!$old_device)? true : false;


        // use private video icon or public image icon
        $icon_name = getNameWeatherIcon($wObj->current);

        if($use_main_video_icon){
            $res['current']['main_icon'] = 'video';
            $res['current']['video_icon_url'] = _VIEWS_.'/sources/icon/'.$icon_name.'.mp4';
        } else{

            if($old_device){
                // для старых Андройд показываем gif
                $curr_icon = '<img src="'._VIEWS_DIR_.'sources/icon/'.$icon_name.'.gif">';
            } else{
                // Иконка от weather api
                $curr_icon = $this->openweather_icon . $wObj->current['curr_icon'] . '@4x.png">';
            }
            $res['current']['main_icon'] = 'image';
            $res['current']['image_icon_url'] = $curr_icon;
        }


        // Если есть осадки (таблица интенсивности осадков https://meteoinfo.ru/forcabout/3891-nast-kpp)
        $period_minutes = 0;
        $weather_id = $wObj->current['curr_w_id'];

        // передаем в powerRainSnow() тип осадков и значение и получаем в виде капелек или снежинок
        $res['current']['intensity_rows'] = '';
        foreach($wObj->rain as $datetime => $precipitation){

            switch($period_minutes){
                case '5':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">05 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $res['current']['intensity_rows'] .= $div;
                    break;

                case '30':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">30 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $res['current']['intensity_rows'] .= $div;
                    break;

                case '60':
                    $display_icon = powerRainSnow($weather_id, $precipitation);

                    $div = '<div class="intensity_row"><span class="w_param">60 мин: </span> <span class="w_val">' . $display_icon . '</span></div>';
                    $res['current']['intensity_rows'] .= $div;
                    break;
            }

            $period_minutes++;
        }
        /******* конец БЛОКА ТЕКУЩЕЙ ПОГОДЫ *******/


        /******* начало БЛОК ПОГОДЫ ПОЧАСОВОЙ на ближайшие 6 часов *******/
        $i = 1;
        $hour_row = get_template('', $this->module, 'hour_row');
        
        foreach($wObj->hourly as $datetime => $arr){
            if($i > 6) break;
            $tt = $hour_row;
            $hour = date('H', $datetime);                   // Текущий час

            $hour_now = date('H', time());
            if($hour_now == $hour) continue;
            $tt = set($tt, 'time', $hour . ':00');
            $tt = set($tt, 'temp',  tf($arr['hour_temp']));

            $curr_icon = $this->openweather_icon . $arr['hour_icon'] . '@2x.png">';
            $tt = set($tt, 'icon', $curr_icon);

            $i++;

            // Добавляем все даты
            $res['hours_6']['rows'] .= $tt;

        }
        /******* конец БЛОК ПОГОДЫ ПОЧАСОВОЙ на ближайшие 6 часов *******/


        /* БЛОК ПОГОДЫ ПОЧАСОВОЙ c 6-23 часов (график Google Charts) *******/
        foreach($wObj->hourly as $datetime => $arr){

            // Берем только данные за текущий день
            if(date('Ymd',$datetime) == date('Ymd')){
                $hour = (int) date('G', $datetime);

                $hour_temp = round($arr["hour_temp"]);
                $hour_chart[] = [["v" => [$hour, 0, 0], "f" => $hour.":00"], $hour_temp, (string)$hour_temp];
            }
        }

        // Вставляем данные
        $curr_hour = intval(date('H'));
        $lost_hours = 24 - $curr_hour;                                        // Проверяем сколько осталось часов до полуночи
        $width = round(($lost_hours * 100) / 17);                         // Вычисляем размер области графика (с расчетом что 17 часов до получночи занимают 100% ширины)

        $res['hours_gchart']['curr_hour_min'] = ($curr_hour == 0)? 0 : $curr_hour - 1;
        $res['hours_gchart']['curr_hour_width'] = $width;
        $res['hours_gchart']['hour_chart_data'] = json_encode($hour_chart, JSON_UNESCAPED_UNICODE);
        /* конец БЛОК ПОГОДЫ ПОЧАСОВОЙ c 6-23 часов (график Google Charts) *******/


        /******* БЛОК ПОГОДЫ ПО ДНЯМ *******/
        $day_row = get_template('', $this->module, 'day_row');
        $i = 0;
        foreach($wObj->daily as $datetime => $arr){
            if($i > 5) continue;

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
            $res['days']['rows'] .= $tt;

            $i++;
        }
        /******* конец БЛОК ПОГОДЫ ПО ДНЯМ *******/


        die(json_encode($res, JSON_UNESCAPED_UNICODE));
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