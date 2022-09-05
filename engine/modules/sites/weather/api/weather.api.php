<?php
/** API по для openweathermap.org
 *  docs https://openweathermap.org/api/one-call-api
 */

class weatherApi
{

    public $url_curr_weather = 'https://api.openweathermap.org/data/2.5/';
    public $city_name = 'Saint Petersburg,RU';      // title city (Saint-Petersburg)
    public $city_id = '498817';                     // city id (Saint-Petersburg)
    public $city_coord = 'lat=59.89&lon=30.26';     // city coordinate (Saint-Petersburg)

    public $orig;                                   // Полученные от api данные
    public $common;                                 // Общие данные
    public $current;                                // Текущая погода
    public $rain;                                   // Поминутный объем осадков мм на последующие 60 минут
    public $hourly;                                 // Почасовой прогноз на ближайшие 48 часов
    public $daily;                                  // Ежедневный прогноз на 8 дней

    public $debug = false;


    /**
     * Получить данные по погоде
     * @return $this - данные
     */
    public function getWeather(){
        $operation = 'onecall';     // один запрос для получения данных по разных категориям
        $url = $this->url_curr_weather.$operation.'?'.$this->city_coord.'&appid='.WEATHER_CURRENT_API.'&lang=ru&units=metric';

        // Получаем данные
        if($this->debug){
            // для теста
            $data_api_json = '{"lat":59.89,"lon":30.26,"timezone":"Europe\/Moscow","timezone_offset":10800,"current":{"dt":1657577054,"sunrise":1657587459,"sunset":1657653062,"temp":17.27,"feels_like":17.53,"pressure":1007,"humidity":95,"dew_point":16.46,"uvi":0,"clouds":75,"visibility":10000,"wind_speed":3,"wind_deg":50,"weather":[{"id":200,"main":"Thunderstorm","description":"гроза с небольшим дождём","icon":"11n"},{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10n"}],"rain":{"1h":0.73}},"minutely":[{"dt":1657577100,"precipitation":0.749},{"dt":1657577160,"precipitation":0.749},{"dt":1657577220,"precipitation":0.749},{"dt":1657577280,"precipitation":0.749},{"dt":1657577340,"precipitation":0.749},{"dt":1657577400,"precipitation":0.749},{"dt":1657577460,"precipitation":0.7722},{"dt":1657577520,"precipitation":0.7954},{"dt":1657577580,"precipitation":0.8186},{"dt":1657577640,"precipitation":0.8418},{"dt":1657577700,"precipitation":0.865},{"dt":1657577760,"precipitation":0.865},{"dt":1657577820,"precipitation":0.865},{"dt":1657577880,"precipitation":0.865},{"dt":1657577940,"precipitation":0.865},{"dt":1657578000,"precipitation":0.865},{"dt":1657578060,"precipitation":0.865},{"dt":1657578120,"precipitation":0.865},{"dt":1657578180,"precipitation":0.865},{"dt":1657578240,"precipitation":0.865},{"dt":1657578300,"precipitation":0.865},{"dt":1657578360,"precipitation":0.865},{"dt":1657578420,"precipitation":0.865},{"dt":1657578480,"precipitation":0.865},{"dt":1657578540,"precipitation":0.865},{"dt":1657578600,"precipitation":0.865},{"dt":1657578660,"precipitation":0.8418},{"dt":1657578720,"precipitation":0.8186},{"dt":1657578780,"precipitation":0.7954},{"dt":1657578840,"precipitation":0.7722},{"dt":1657578900,"precipitation":0.749},{"dt":1657578960,"precipitation":0.7288},{"dt":1657579020,"precipitation":0.7086},{"dt":1657579080,"precipitation":0.6884},{"dt":1657579140,"precipitation":0.6682},{"dt":1657579200,"precipitation":0.648},{"dt":1657579260,"precipitation":0.6308},{"dt":1657579320,"precipitation":0.6136},{"dt":1657579380,"precipitation":0.5964},{"dt":1657579440,"precipitation":0.5792},{"dt":1657579500,"precipitation":0.562},{"dt":1657579560,"precipitation":0.562},{"dt":1657579620,"precipitation":0.562},{"dt":1657579680,"precipitation":0.562},{"dt":1657579740,"precipitation":0.562},{"dt":1657579800,"precipitation":0.562},{"dt":1657579860,"precipitation":0.562},{"dt":1657579920,"precipitation":0.562},{"dt":1657579980,"precipitation":0.562},{"dt":1657580040,"precipitation":0.562},{"dt":1657580100,"precipitation":0.562},{"dt":1657580160,"precipitation":0.5792},{"dt":1657580220,"precipitation":0.5964},{"dt":1657580280,"precipitation":0.6136},{"dt":1657580340,"precipitation":0.6308},{"dt":1657580400,"precipitation":0.648},{"dt":1657580460,"precipitation":0.648},{"dt":1657580520,"precipitation":0.648},{"dt":1657580580,"precipitation":0.648},{"dt":1657580640,"precipitation":0.648},{"dt":1657580700,"precipitation":0.648}],"hourly":[{"dt":1657576800,"temp":17.27,"feels_like":17.53,"pressure":1007,"humidity":95,"dew_point":16.46,"uvi":0,"clouds":75,"visibility":10000,"wind_speed":2.82,"wind_deg":46,"wind_gust":7.11,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10n"}],"pop":0.98,"rain":{"1h":0.65}},{"dt":1657580400,"temp":16.91,"feels_like":17.16,"pressure":1008,"humidity":96,"dew_point":16.27,"uvi":0,"clouds":80,"visibility":10000,"wind_speed":3.85,"wind_deg":41,"wind_gust":9.39,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10n"}],"pop":0.97,"rain":{"1h":0.65}},{"dt":1657584000,"temp":16.51,"feels_like":16.72,"pressure":1009,"humidity":96,"dew_point":15.87,"uvi":0,"clouds":85,"visibility":10000,"wind_speed":3.38,"wind_deg":42,"wind_gust":9.45,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04n"}],"pop":0.77},{"dt":1657587600,"temp":15.93,"feels_like":16.11,"pressure":1009,"humidity":97,"dew_point":15.45,"uvi":0,"clouds":90,"visibility":10000,"wind_speed":3.05,"wind_deg":46,"wind_gust":8.87,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.29},{"dt":1657591200,"temp":15.36,"feels_like":15.51,"pressure":1010,"humidity":98,"dew_point":15.05,"uvi":0.07,"clouds":95,"visibility":10000,"wind_speed":2.41,"wind_deg":50,"wind_gust":8.04,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.29},{"dt":1657594800,"temp":15.34,"feels_like":15.46,"pressure":1011,"humidity":97,"dew_point":14.69,"uvi":0.22,"clouds":95,"visibility":10000,"wind_speed":3.02,"wind_deg":36,"wind_gust":7.9,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.29},{"dt":1657598400,"temp":16.48,"feels_like":16.66,"pressure":1010,"humidity":95,"dew_point":15.46,"uvi":0.4,"clouds":91,"visibility":10000,"wind_speed":3.23,"wind_deg":31,"wind_gust":5.34,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.13},{"dt":1657602000,"temp":17.88,"feels_like":18.13,"pressure":1010,"humidity":92,"dew_point":16.36,"uvi":0.79,"clouds":93,"visibility":10000,"wind_speed":3.83,"wind_deg":44,"wind_gust":6.32,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.02},{"dt":1657605600,"temp":18.08,"feels_like":18.37,"pressure":1010,"humidity":93,"dew_point":16.8,"uvi":1.34,"clouds":94,"visibility":10000,"wind_speed":3.72,"wind_deg":32,"wind_gust":7.3,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0},{"dt":1657609200,"temp":19.87,"feels_like":20.16,"pressure":1009,"humidity":86,"dew_point":17.17,"uvi":1.91,"clouds":99,"visibility":10000,"wind_speed":4.13,"wind_deg":40,"wind_gust":8.43,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.28,"rain":{"1h":0.12}},{"dt":1657612800,"temp":21.81,"feels_like":22.06,"pressure":1009,"humidity":77,"dew_point":17.42,"uvi":2.5,"clouds":99,"visibility":10000,"wind_speed":4.45,"wind_deg":54,"wind_gust":8.81,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.32,"rain":{"1h":0.28}},{"dt":1657616400,"temp":25.34,"feels_like":25.52,"pressure":1008,"humidity":61,"dew_point":17.24,"uvi":2.92,"clouds":92,"visibility":10000,"wind_speed":5.52,"wind_deg":60,"wind_gust":9.27,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.4},{"dt":1657620000,"temp":27.13,"feels_like":27.88,"pressure":1008,"humidity":55,"dew_point":17.39,"uvi":5.12,"clouds":90,"visibility":10000,"wind_speed":6.01,"wind_deg":68,"wind_gust":9.63,"weather":[{"id":804,"main":"Clouds","description":"пасмурно","icon":"04d"}],"pop":0.4},{"dt":1657623600,"temp":28.74,"feels_like":29.33,"pressure":1007,"humidity":50,"dew_point":17.45,"uvi":4.86,"clouds":77,"visibility":10000,"wind_speed":6.1,"wind_deg":63,"wind_gust":10.94,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04d"}],"pop":0.71},{"dt":1657627200,"temp":25.77,"feels_like":26.05,"pressure":1006,"humidity":63,"dew_point":17.74,"uvi":4.17,"clouds":76,"visibility":10000,"wind_speed":6.54,"wind_deg":83,"wind_gust":11.16,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.79,"rain":{"1h":0.52}},{"dt":1657630800,"temp":21.23,"feels_like":21.63,"pressure":1007,"humidity":85,"dew_point":18.45,"uvi":1.65,"clouds":95,"visibility":10000,"wind_speed":2.94,"wind_deg":50,"wind_gust":6.48,"weather":[{"id":501,"main":"Rain","description":"дождь","icon":"10d"}],"pop":0.8,"rain":{"1h":2.5}},{"dt":1657634400,"temp":22.84,"feels_like":23.22,"pressure":1007,"humidity":78,"dew_point":18.61,"uvi":1.12,"clouds":93,"visibility":10000,"wind_speed":3.8,"wind_deg":45,"wind_gust":7.43,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.84,"rain":{"1h":0.79}},{"dt":1657638000,"temp":23.14,"feels_like":23.52,"pressure":1007,"humidity":77,"dew_point":18.66,"uvi":0.66,"clouds":88,"visibility":10000,"wind_speed":4.49,"wind_deg":62,"wind_gust":8.61,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.98,"rain":{"1h":0.87}},{"dt":1657641600,"temp":21.89,"feels_like":22.22,"pressure":1008,"humidity":80,"dew_point":18.17,"uvi":0.57,"clouds":83,"visibility":10000,"wind_speed":4.29,"wind_deg":107,"wind_gust":8.81,"weather":[{"id":501,"main":"Rain","description":"дождь","icon":"10d"}],"pop":0.96,"rain":{"1h":1.97}},{"dt":1657645200,"temp":21.1,"feels_like":21.54,"pressure":1008,"humidity":87,"dew_point":18.7,"uvi":0.24,"clouds":77,"visibility":10000,"wind_speed":3.36,"wind_deg":115,"wind_gust":7.93,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.93,"rain":{"1h":0.92}},{"dt":1657648800,"temp":19.9,"feels_like":20.32,"pressure":1008,"humidity":91,"dew_point":18.35,"uvi":0.07,"clouds":81,"visibility":10000,"wind_speed":3.21,"wind_deg":124,"wind_gust":8.25,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"pop":0.93,"rain":{"1h":0.19}},{"dt":1657652400,"temp":18.59,"feels_like":19.01,"pressure":1008,"humidity":96,"dew_point":17.73,"uvi":0,"clouds":61,"visibility":10000,"wind_speed":2.75,"wind_deg":135,"wind_gust":7.62,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04d"}],"pop":0},{"dt":1657656000,"temp":17.74,"feels_like":18.08,"pressure":1009,"humidity":96,"dew_point":17.01,"uvi":0,"clouds":80,"visibility":10000,"wind_speed":2.58,"wind_deg":146,"wind_gust":6.91,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04n"}],"pop":0},{"dt":1657659600,"temp":17.06,"feels_like":17.33,"pressure":1009,"humidity":96,"dew_point":16.22,"uvi":0,"clouds":68,"visibility":10000,"wind_speed":2.44,"wind_deg":155,"wind_gust":5.12,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04n"}],"pop":0},{"dt":1657663200,"temp":16.46,"feels_like":16.67,"pressure":1009,"humidity":96,"dew_point":15.64,"uvi":0,"clouds":67,"visibility":10000,"wind_speed":2.66,"wind_deg":167,"wind_gust":6.97,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04n"}],"pop":0},{"dt":1657666800,"temp":15.76,"feels_like":15.9,"pressure":1010,"humidity":96,"dew_point":14.92,"uvi":0,"clouds":57,"visibility":10000,"wind_speed":2.72,"wind_deg":182,"wind_gust":8.35,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04n"}],"pop":0},{"dt":1657670400,"temp":14.87,"feels_like":14.89,"pressure":1010,"humidity":95,"dew_point":13.93,"uvi":0,"clouds":48,"visibility":10000,"wind_speed":2.6,"wind_deg":193,"wind_gust":7.75,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03n"}],"pop":0},{"dt":1657674000,"temp":13.96,"feels_like":13.89,"pressure":1010,"humidity":95,"dew_point":13.03,"uvi":0,"clouds":4,"visibility":10000,"wind_speed":2.65,"wind_deg":183,"wind_gust":7.27,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657677600,"temp":13.51,"feels_like":13.4,"pressure":1010,"humidity":95,"dew_point":12.58,"uvi":0.08,"clouds":3,"visibility":10000,"wind_speed":2.75,"wind_deg":179,"wind_gust":7.45,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657681200,"temp":13.95,"feels_like":13.8,"pressure":1011,"humidity":92,"dew_point":12.58,"uvi":0.27,"clouds":3,"visibility":10000,"wind_speed":2.87,"wind_deg":185,"wind_gust":7.64,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657684800,"temp":14.88,"feels_like":14.7,"pressure":1011,"humidity":87,"dew_point":12.67,"uvi":0.63,"clouds":2,"visibility":10000,"wind_speed":3.08,"wind_deg":188,"wind_gust":6.69,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657688400,"temp":16.05,"feels_like":15.77,"pressure":1011,"humidity":79,"dew_point":12.29,"uvi":1.25,"clouds":2,"visibility":10000,"wind_speed":3.14,"wind_deg":191,"wind_gust":5.55,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657692000,"temp":17.72,"feels_like":17.35,"pressure":1011,"humidity":69,"dew_point":11.82,"uvi":2.11,"clouds":1,"visibility":10000,"wind_speed":3.19,"wind_deg":188,"wind_gust":5.07,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657695600,"temp":19.42,"feels_like":18.96,"pressure":1011,"humidity":59,"dew_point":11.03,"uvi":3.06,"clouds":0,"visibility":10000,"wind_speed":3.21,"wind_deg":188,"wind_gust":4.67,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657699200,"temp":20.82,"feels_like":20.34,"pressure":1011,"humidity":53,"dew_point":10.74,"uvi":4,"clouds":0,"visibility":10000,"wind_speed":3.19,"wind_deg":186,"wind_gust":4.47,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657702800,"temp":22.07,"feels_like":21.59,"pressure":1011,"humidity":48,"dew_point":10.42,"uvi":4.67,"clouds":0,"visibility":10000,"wind_speed":3.06,"wind_deg":182,"wind_gust":4.42,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657706400,"temp":23.08,"feels_like":22.59,"pressure":1010,"humidity":44,"dew_point":10.16,"uvi":4.92,"clouds":0,"visibility":10000,"wind_speed":3.08,"wind_deg":176,"wind_gust":4.68,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657710000,"temp":23.94,"feels_like":23.46,"pressure":1010,"humidity":41,"dew_point":9.94,"uvi":4.67,"clouds":0,"visibility":10000,"wind_speed":3.02,"wind_deg":174,"wind_gust":4.81,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657713600,"temp":24.53,"feels_like":24.06,"pressure":1009,"humidity":39,"dew_point":9.82,"uvi":4,"clouds":2,"visibility":10000,"wind_speed":2.97,"wind_deg":175,"wind_gust":5.29,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"pop":0},{"dt":1657717200,"temp":24.92,"feels_like":24.46,"pressure":1008,"humidity":38,"dew_point":9.65,"uvi":3.06,"clouds":29,"visibility":10000,"wind_speed":2.91,"wind_deg":184,"wind_gust":5.4,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03d"}],"pop":0},{"dt":1657720800,"temp":24.79,"feels_like":24.37,"pressure":1008,"humidity":40,"dew_point":10.05,"uvi":2.08,"clouds":38,"visibility":10000,"wind_speed":3.33,"wind_deg":190,"wind_gust":5.04,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03d"}],"pop":0},{"dt":1657724400,"temp":23.08,"feels_like":22.62,"pressure":1008,"humidity":45,"dew_point":10.4,"uvi":1.23,"clouds":49,"visibility":10000,"wind_speed":3.86,"wind_deg":190,"wind_gust":6.48,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03d"}],"pop":0},{"dt":1657728000,"temp":20.6,"feels_like":20.1,"pressure":1008,"humidity":53,"dew_point":10.62,"uvi":0.38,"clouds":61,"visibility":10000,"wind_speed":3.19,"wind_deg":180,"wind_gust":8.14,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04d"}],"pop":0},{"dt":1657731600,"temp":19.8,"feels_like":19.32,"pressure":1008,"humidity":57,"dew_point":10.97,"uvi":0.16,"clouds":65,"visibility":10000,"wind_speed":2.4,"wind_deg":162,"wind_gust":4.65,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04d"}],"pop":0},{"dt":1657735200,"temp":18.66,"feels_like":18.12,"pressure":1007,"humidity":59,"dew_point":10.47,"uvi":0.05,"clouds":65,"visibility":10000,"wind_speed":2.6,"wind_deg":154,"wind_gust":4.7,"weather":[{"id":803,"main":"Clouds","description":"облачно с прояснениями","icon":"04d"}],"pop":0},{"dt":1657738800,"temp":17.25,"feels_like":16.6,"pressure":1007,"humidity":60,"dew_point":9.42,"uvi":0,"clouds":34,"visibility":10000,"wind_speed":2.92,"wind_deg":161,"wind_gust":5.49,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03d"}],"pop":0},{"dt":1657742400,"temp":16.03,"feels_like":15.39,"pressure":1007,"humidity":65,"dew_point":9.38,"uvi":0,"clouds":21,"visibility":10000,"wind_speed":3.35,"wind_deg":162,"wind_gust":9.31,"weather":[{"id":801,"main":"Clouds","description":"небольшая облачность","icon":"02n"}],"pop":0},{"dt":1657746000,"temp":16.8,"feels_like":16.23,"pressure":1007,"humidity":65,"dew_point":9.99,"uvi":0,"clouds":35,"visibility":10000,"wind_speed":3.66,"wind_deg":190,"wind_gust":11.69,"weather":[{"id":802,"main":"Clouds","description":"переменная облачность","icon":"03n"}],"pop":0}],"daily":[{"dt":1657620000,"sunrise":1657587459,"sunset":1657653062,"moonrise":1657653120,"moonset":1657577940,"moon_phase":0.44,"temp":{"day":27.13,"min":15.34,"max":28.74,"night":17.74,"eve":21.89,"morn":16.48},"feels_like":{"day":27.88,"night":18.08,"eve":22.22,"morn":16.66},"pressure":1008,"humidity":55,"dew_point":17.39,"wind_speed":6.54,"wind_deg":83,"wind_gust":11.16,"weather":[{"id":501,"main":"Rain","description":"дождь","icon":"10d"}],"clouds":90,"pop":0.98,"rain":9.46,"uvi":5.12},{"dt":1657706400,"sunrise":1657673966,"sunset":1657739370,"moonrise":1657742580,"moonset":1657667340,"moon_phase":0.5,"temp":{"day":23.08,"min":13.51,"max":24.92,"night":16.03,"eve":20.6,"morn":14.88},"feels_like":{"day":22.59,"night":15.39,"eve":20.1,"morn":14.7},"pressure":1010,"humidity":44,"dew_point":10.16,"wind_speed":3.86,"wind_deg":190,"wind_gust":9.31,"weather":[{"id":800,"main":"Clear","description":"ясно","icon":"01d"}],"clouds":0,"pop":0,"uvi":4.92},{"dt":1657792800,"sunrise":1657760475,"sunset":1657825674,"moonrise":1657830360,"moonset":1657759140,"moon_phase":0.52,"temp":{"day":18.5,"min":13.34,"max":19.44,"night":15.8,"eve":19.43,"morn":15.99},"feels_like":{"day":18.08,"night":15.68,"eve":19.18,"morn":15.63},"pressure":1003,"humidity":64,"dew_point":11.47,"wind_speed":6.07,"wind_deg":160,"wind_gust":12.46,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"clouds":96,"pop":1,"rain":3.26,"uvi":4.25},{"dt":1657879200,"sunrise":1657846988,"sunset":1657911975,"moonrise":1657917360,"moonset":1657852140,"moon_phase":0.56,"temp":{"day":17.17,"min":12.39,"max":18.18,"night":14.11,"eve":18.18,"morn":12.5},"feels_like":{"day":16.8,"night":13.87,"eve":17.88,"morn":12.21},"pressure":1002,"humidity":71,"dew_point":11.73,"wind_speed":4.23,"wind_deg":206,"wind_gust":8.16,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"clouds":100,"pop":1,"rain":5.55,"uvi":3.84},{"dt":1657965600,"sunrise":1657933503,"sunset":1657998272,"moonrise":1658004120,"moonset":1657945200,"moon_phase":0.6,"temp":{"day":16.29,"min":11.9,"max":17.69,"night":14.49,"eve":16.93,"morn":11.94},"feels_like":{"day":15.96,"night":14.32,"eve":16.79,"morn":11.64},"pressure":1002,"humidity":76,"dew_point":11.96,"wind_speed":6.17,"wind_deg":216,"wind_gust":10.36,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"clouds":98,"pop":1,"rain":4.58,"uvi":3.27},{"dt":1658052000,"sunrise":1658020020,"sunset":1658084565,"moonrise":1658090700,"moonset":1658037840,"moon_phase":0.64,"temp":{"day":14.69,"min":11.94,"max":16.46,"night":12.71,"eve":13.4,"morn":12.13},"feels_like":{"day":14.33,"night":12.57,"eve":13.25,"morn":11.83},"pressure":1000,"humidity":81,"dew_point":11.41,"wind_speed":6.47,"wind_deg":167,"wind_gust":12.19,"weather":[{"id":501,"main":"Rain","description":"дождь","icon":"10d"}],"clouds":100,"pop":1,"rain":6,"uvi":4},{"dt":1658138400,"sunrise":1658106540,"sunset":1658170856,"moonrise":1658177220,"moonset":1658130060,"moon_phase":0.67,"temp":{"day":15.29,"min":11.59,"max":18.45,"night":14.64,"eve":17.76,"morn":11.59},"feels_like":{"day":15.07,"night":14.38,"eve":17.42,"morn":11.36},"pressure":997,"humidity":84,"dew_point":12.54,"wind_speed":3.74,"wind_deg":308,"wind_gust":6.78,"weather":[{"id":501,"main":"Rain","description":"дождь","icon":"10d"}],"clouds":100,"pop":1,"rain":11.48,"uvi":4},{"dt":1658224800,"sunrise":1658193062,"sunset":1658257143,"moonrise":1658263740,"moonset":1658221920,"moon_phase":0.71,"temp":{"day":18.27,"min":12.3,"max":18.27,"night":14.67,"eve":16.53,"morn":12.71},"feels_like":{"day":18.16,"night":14.54,"eve":16.43,"morn":12.52},"pressure":997,"humidity":77,"dew_point":14.14,"wind_speed":7.11,"wind_deg":287,"wind_gust":10.49,"weather":[{"id":500,"main":"Rain","description":"небольшой дождь","icon":"10d"}],"clouds":100,"pop":1,"rain":7.08,"uvi":4}],"alerts":[{"sender_name":"","event":"Thunderstorms","start":1657573200,"end":1657591200,"description":"","tags":["Thunderstorm"]},{"sender_name":"","event":"Thunderstorms","start":1657627200,"end":1657656000,"description":"","tags":["Thunderstorm"]},{"sender_name":"","event":"Гроза","start":1657573200,"end":1657591200,"description":"местами","tags":["Thunderstorm"]},{"sender_name":"","event":"Гроза","start":1657627200,"end":1657656000,"description":"Местами","tags":["Thunderstorm"]},{"sender_name":"","event":"Rain","start":1657573200,"end":1657591200,"description":"","tags":["Rain"]},{"sender_name":"","event":"Rain","start":1657627200,"end":1657656000,"description":"","tags":["Rain"]},{"sender_name":"","event":"Дождь","start":1657573200,"end":1657591200,"description":"Местами ливни","tags":["Rain"]},{"sender_name":"","event":"Дождь","start":1657627200,"end":1657656000,"description":"Местами","tags":["Rain"]},{"sender_name":"","event":"Wind","start":1657627200,"end":1657656000,"description":"","tags":["Wind"]},{"sender_name":"","event":"Ветер","start":1657627200,"end":1657656000,"description":"Местами при грозе 23-28м\/с","tags":["Wind"]}]}';
            $data_api = json_decode($data_api_json, true);
        } else{
            // Отправляем запрос
            $data_api = $this->send_query($url);
        }

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


    /**
     * Общие данные
     * @return array $common
     */
    private function common(){
        $common['comm_coord']           = $this->orig['lat'].','.$this->orig['lon'];
        $common['comm_timezone']        = $this->orig['timezone'];
        $common['comm_timezone_offset'] = $this->orig['timezone_offset'];

        return $common;
    }


    /**
     * Текущая погода
     * @return array $current
     */
    private function current(){
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


    /**
     * Поминутный объем осадков (в мм/кв.м)
     * (на ближайший 1 час)
     *
     * @return array $rain
     */
    private function rain(){
        foreach($this->orig['minutely'] as $arr){
            $rain[$arr['dt']] = $arr['precipitation'];
        }

        return $rain;
    }


    /**
     * Почасовой прогноз
     * (на ближайшие 48 часов)
     *
     * @return array $hour
     */
    private function hourly(){
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


    /**
     * Ежедневный прогноз
     * (на ближайшие 8 дней)
     *
     * @return array $daily
     */
    private function daily(){

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


    /**
     * Отправляем запрос
     * @param string $url
     *
     * @return array $data - массив с данными
     */
    private function send_query($url){
        $ch = curl_init();
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Устанавливаем параметр, чтобы curl возвращал данные, вместо того, чтобы выводить их в браузер.
            curl_setopt($ch, CURLOPT_URL, $url);

            $data = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($data,true);

        return $data;
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


