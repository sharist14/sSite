<?php
/** Документация https://core.telegram.org/bots/api
    Класс для работы с ботом telegram */

class telegramApi {

    // Общие настройки
    var $data = [
        'repeletDefault_bot' => ['chat_id' => TG_DEFBOT_CHAT_ID, 'token' => TG_DEFBOT_TOKEN]
    ];

    var $base_url = 'https://api.telegram.org';
    var $chat_id;
    var $token;

    var $debug = false;

    // todo сделать в этом классе проверку try catch на случай если код ответа сервера не 200


    function __construct($chat_name){
        $this->token = $this->data[$chat_name]['token'];
        if(empty($this->token)) die('Не найден token');

        $this->chat_id = $this->data[$chat_name]['chat_id'];
    }


    /** Отправить текст в чат */
    function sendText($text){
        $url = '/sendMessage';

        $data['chat_id'] = $this->chat_id;
        $data['text'] = $text;

        $result = $this->query($url, 'POST', $data);

        return ($result['http_code'] == 200 && $result['data']['ok'])? true : false;
    }



    function query($url, $method = 'GET', $data = [], $add_header = []) {
        $full_url = $this->base_url.'/bot'.$this->token . $url;

        $ch = curl_init();
            $headers    = [];
            $headers[]  = 'Accept: */*';

            // Дополнительные headers
            if($add_header){
                foreach($add_header as $header){
                    $headers[] = $header;
                }
            }

            if($method == 'POST'){
                $headers[]  = 'Content-Type: application/json';

                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_URL, $full_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

            $result = curl_exec($ch);
        curl_close($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result_orig = $result;

        if(json_decode($result, true)){
            $result = json_decode($result, true);
            $result_json = $result;
        }

        if($this->debug){
            pr('========== ЗАПРОС НА СЕРВЕР ==========');
                pr('Текущий токен: ' . $this->token);
                pr('Запрос на url: ' . $full_url);
                pr('Метод: ' . $method);
                pr('Заголовки:');
                pr($headers);
                pr('Тело запроса:');
                pr($data);
            pr('========== конец ЗАПРОС НА СЕРВЕР ==========');


            pr('========== КОД ОТВЕТА ОТ СЕРВЕРА ==========');
                pr($http_code);
            pr( '========== конец КОД ОТВЕТА ОТ СЕРВЕРА ==========');


            pr( '========== ОТВЕТА ОТ СЕРВЕРА КАК ОН ЕСТЬ ==========');
                pr($result_orig);
            pr( '========== конец ОТВЕТА ОТ СЕРВЕРА КАК ОН ЕСТЬ ==========');


            pr('========== ОТВЕТА ОТ СЕРВЕРА json_decode ==========');
                pr($result_json);
            pr( '========== конец ОТВЕТА ОТ СЕРВЕРА json_decode ==========');
        }

        return ['http_code' => $http_code, 'data' => $result];
    }

}