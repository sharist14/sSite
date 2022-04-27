<?php

//require_once(_ROOT_DIR_ . 'engine/modules/api/weatherObj.php');


class weatherApi
{

    public $url_curr_weather = 'https://api.openweathermap.org/data/2.5/';
    public $city_name = 'Saint Petersburg,RU';      // title city (Saint-Petersburg)
    public $city_id = '498817';                     // city id (Saint-Petersburg)
    public $city_coord = 'lat=59.89&lon=30.26';     // city coordinate (Saint-Petersburg)

    public $orig;          // Полученные от api данные
    var $common;            // Общие данные
    var $current;           // Текущая погода
    var $rain;              // Поминутный объем осадков мм на последующие 60 минут
    var $hourly;            // Почасовой прогноз на ближайшие 48 часов
    var $daily;             // Ежедневный прогноз на 8 дней



    // Основная функция
    public function getWeather(){

        /* Подгатавливаем запрос к получению данных */
        $operation = 'onecall';
        $url = $this->url_curr_weather.$operation.'?'.$this->city_coord.'&appid='.WEATHER_CURRENT_API.'&lang=ru&units=metric';

        // Запрашиваем погоду
        $data_api = $this->send_query($url);

        // Если данные корректные, добавляем их в объект
        if(!$data_api['message']){
            $this->orig = $data_api;

            $this->common   = $this->common();
            $this->current  = $this->current();
            $this->rain     = $this->rain();
            $this->hourly   = $this->hourly();
            $this->daily    = $this->daily();

            return $this;
        }

        return false;
    }


    // Получение данных по api
    public function send_query($url){
        $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
            curl_setopt($ch, CURLOPT_URL, $url);

            $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data,true);

        return $data;
    }


    /* Общие данные */
    public function common(){
        $common['comm_coord']           = $this->orig['lat'].','.$this->orig['lon'];
        $common['comm_timezone']        = $this->orig['timezone'];
        $common['comm_timezone_offset'] = $this->orig['timezone_offset'];

        return $common;
    }


    /* Текущая погода */
    public function current(){
        $current['curr_update_date']    = [ df($this->orig['current']['dt'],'dt'), $this->orig['current']['dt'] ];                         // Время расчета данных, unix
        $current['curr_sunrise']        = [ df($this->orig['current']['sunrise'],'ft'), $this->orig['current']['sunrise']];                        // Рассвет
        $current['curr_sunset']         = [ df($this->orig['current']['sunset'],'ft'), $this->orig['current']['sunset'] ];                          // Закат
        $current['curr_temp']           = [ tf($this->orig['current']['temp'], 'min'), $this->orig['current']['temp'] ];                              // температура
        $current['curr_feels_like']     = [ tf($this->orig['current']['feels_like']), $this->orig['current']['feels_like'] ];                  // ощущается как
        $current['curr_pressure']       = $this->orig['current']['pressure'];                      // давление в гПа
        $current['curr_humidity']       = $this->orig['current']['humidity'];
        $current['curr_dew_point']      = [ tf($this->orig['current']['dew_point']), $this->orig['current']['dew_point'] ];                    // Атмосферная температура (меняется в зависимости от давления и влажности), ниже которой капли воды начинают конденсироваться и может образовываться роса. Единицы в метрическая система: Цельсий
        $current['curr_uvi']            = $this->orig['current']['uvi'];                                // Полуденный ультрафиолетовый индекс
        $current['curr_clouds']         = $this->orig['current']['clouds'];                          // Облачность в %
        $current['curr_visibility']     = $this->orig['current']['visibility'];                  // видимость в метрах
        $current['curr_wind_speed']     = $this->orig['current']['wind_speed'];                  // скорость ветра
        $current['curr_wind_deg']       = $this->orig['current']['wind_deg'];                      // направление ветра, градусы (метеорологические)
        $current['curr_wind_arrow']     = wind_arrow($this->orig['current']['wind_deg']);                      // направление ветра, часть света
        $current['curr_w_id']           = $this->orig['current']['weather'][0]['id'];                  // id текущей погоды
        $current['curr_w_main']         = $this->orig['current']['weather'][0]['main'];            // группа погодных условий
        $current['curr_description']    = $this->orig['current']['weather'][0]['description'];  // описание текущей погоды
        $current['curr_icon']           = $this->orig['current']['weather'][0]['icon'];                // id иконки
        $current['curr_wind_gust']      = $this->orig['current']['wind_gust'];                    // порыв ветра метр/сек (когда доступно)
        $current['curr_rain_1h']        = $this->orig['current']['rain'][0]['1h'];                  // Объем дождя за последний час, мм (когда доступно)
        $current['curr_snow_1h']        = $this->orig['current']['snow'][0]['1h'];                  // Объем снега за последний час, мм (когда доступно)

        return $current;
    }


    /* Поминутный объем осадков (в мм/кв.м) на ближайший 1 час */
    public function rain(){

        foreach($this->orig['minutely'] as $arr){
            $rain[$arr['dt']] = $arr['precipitation'];
        }

        return $rain;
    }


    /* Почасовой прогноз на ближайшие 48 часов */
    public function hourly(){
        foreach($this->orig['hourly'] as $data){
            $dt = $data['dt'];
            $hour[$dt]['hour_temp'] = $data['temp'];                                // температура
            $hour[$dt]['hour_feels_like'] = $data['feels_like'];                    // ощущается как
            $hour[$dt]['hour_pressure'] = $data['pressure'];                        // давление в гПа
            $hour[$dt]['hour_humidity'] = $data['humidity'];                        // влажность в %
            $hour[$dt]['hour_dew_point'] = $data['dew_point'];                      // Атмосферная температура (меняется в зависимости от давления и влажности), ниже которой капли воды начинают конденсироваться и может образовываться роса. Единицы в метрическая система: Цельсий
            $hour[$dt]['hour_clouds'] = $data['clouds'];                            // Облачность в %
            $hour[$dt]['hour_visibility'] = $data['visibility'];                    // видимость в метрах
            $hour[$dt]['hour_wind_speed'] = $data['wind_speed'];                    // скорость ветра
            $hour[$dt]['hour_wind_deg'] = $data['wind_deg'];                        // направление ветра, градусы (метеорологические)
            $hour[$dt]['hour_w_id'] = $data['weather'][0]['id'];                    // id текущей погоды
            $hour[$dt]['hour_w_main'] = $data['weather'][0]['main'];                // группа погодных условий
            $hour[$dt]['hour_description'] = $data['weather'][0]['description'];    // описание текущей погоды
            $hour[$dt]['hour_icon'] = $data['weather'][0]['icon'];                  // id иконки
            $hour[$dt]['hour_pop'] = $data['pop'];                                  // вероятность выпадения осадков

            $hour[$dt]['hour_wind_gust'] = $data['wind_gust'];                      // порыв ветра метр/сек (когда доступно)
            $hour[$dt]['hour_rain_1h'] = $data['rain'][0]['1h'];                    // Объем дождя за последний час, мм (когда доступно)
            $hour[$dt]['hour_snow_1h'] = $data['snow'][0]['1h'];                    // Объем снега за последний час, мм (когда доступно)
        }

        return $hour;
    }


    /* Ежедневный прогноз на ближайшие 8 дней */
    public function daily(){

        foreach ($this->orig['daily'] as $data) {
            $dt = $data['dt'];
            $daily[$dt]['day_sunrise'] = $data['sunrise'];
            $daily[$dt]['day_sunset'] = $data['sunset'];
            $daily[$dt]['day_pressure'] = $data['pressure'];
            $daily[$dt]['day_humidity'] = $data['humidity'];
            $daily[$dt]['day_dew_point'] = $data['dew_point'];
            $daily[$dt]['day_wind_speed'] = $data['wind_speed'];
            $daily[$dt]['day_wind_deg'] = $data['wind_deg'];
            $daily[$dt]['day_clouds'] = $data['clouds'];
            $daily[$dt]['day_pop'] = $data['pop'];
            $daily[$dt]['day_uvi'] = $data['uvi'];
            $daily[$dt]['day_temp'] = $data['temp']['day'];
            $daily[$dt]['day_min'] = $data['temp']['min'];
            $daily[$dt]['day_max'] = $data['temp']['max'];
            $daily[$dt]['day_night'] = $data['temp']['night'];
            $daily[$dt]['day_eve'] = $data['temp']['eve'];
            $daily[$dt]['day_morn'] = $data['temp']['morn'];
            $daily[$dt]['day_feels_like_day'] = $data['feels_like']['day'];
            $daily[$dt]['day_feels_like_night'] = $data['feels_like']['night'];
            $daily[$dt]['day_feels_like_eve'] = $data['feels_like']['eve'];
            $daily[$dt]['day_feels_like_morn'] = $data['feels_like']['morn'];
            $daily[$dt]['day_w_id'] = $data['weather'][0]['id'];
            $daily[$dt]['day_w_main'] = $data['weather'][0]['main'];
            $daily[$dt]['day_w_description'] = $data['weather'][0]['description'];
            $daily[$dt]['day_w_icon'] = $data['weather'][0]['icon'];

            $daily[$dt]['day_wind_gust'] = $data['wind_gust'];                      // порыв ветра метр/сек (когда доступно)
            $daily[$dt]['day_rain_1h'] = $data['rain'][0]['1h'];                    // Объем дождя за последний час, мм (когда доступно)
            $daily[$dt]['day_snow_1h'] = $data['snow'][0]['1h'];
        }

        return $daily;
    }




}



/*
УФ индекс

НИЗКИЙ:
УФ-индекс 1-2 (low)
Защита не требуется
Можно спокойно находиться на улице

УМЕРЕННЫЙ/ВЫСОКИЙ:
УФ-индекс 3-5 (medium)
УФ-индекс 6-7 (high)
Требуется защита
Наденьте рубашку, головной убор и солнечные очки, используйте солнцезащитные средства

ОЧЕНЬ ВЫСОКИЙ/ ЭКСТРЕМАЛЬНЫЙ:
УФ-индекс 8-10 (very high)
УФ-индекс 11+ (extremely high)
Требуется дополнительная защита
Старайтесь не находиться на улице в полуденное время
Постарайтесь найти тень
В обязательном порядке носите рубашку, головной убор и солнечные очки и используйте солнцезащитные средства
*/


