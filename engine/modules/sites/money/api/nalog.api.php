<?php
/** Проверка чеков онлайн */

class nalogApi
{

    // Общие настройки
    var $base_url = 'irkkt-mobile.nalog.ru:8888';
    var $phone = NALOG_AUTH_PHONE;

    var $debug = false;

    var $table;
    var $token = '';

    // Для аутентификации по логину / паролю
    var $inn = NALOG_AUTH_INN;                   // инн для авторизации в ЛК налоговой
    var $password = NALOG_AUTH_PASS;             // пароль для авторизации в ЛК налоговой


    // Для аутентификации по телефону
    var $client_secret = NALOG_CLIENT_SECRET;    // получен из телефона из /data/data/ru.fns.billchecker/shared_prefs/ru.fns.billchecker_preferences.xml из значения VERSION


    function __construct(){
        $this->table = new table('nalog_token');
    }


    // Загрузка токена
    function load_token(){
        // Ищем действующий токен в БД
        $nalogObj = $this->table->_DB_->q('SELECT * FROM `nalog_token` ORDER BY `id` DESC LIMIT 1');
        if( $row = $nalogObj->fetch_assoc()){
            $token_expired = intval($row['datetime']) + (60 * 15); // время жизни токена - 15 минут (установлено опытным путём, возможно оно другое)

            // Если не прошло 1 дня с момента получения, используем этот токен
            if( time() < $token_expired ){
                $this->token = $row['token'];
            } else{

                // Иначе пробуем получить новый через токен обновления
                $this->do_refresh_token($row['rtoken'], $row['id']);
            }
        }


        // Если в БД нет рабочего токена и не получили через токен обновления
        // пробуем получить новый в автоматическом режиме через inn/pass
        if( !$this->token ){
            $this->get_new_token_by_inn();
        }
    }



    /* Получение токена через inn/pass */
    function get_new_token_by_inn(){
        $url = '/v2/mobile/users/lkfl/auth';

        $data['inn'] = $this->inn;
        $data['password'] = $this->password;
        $data['client_secret'] = $this->client_secret;

        $result = $this->query($url, 'POST', $data);

        if( $result['http_code'] == '200'){
            return $this->save_token($result['data'], 'Получен через авторизацию по ИНН');
        } else{
            die('Не удалось получить новый токен по ИНН');
        }
    }


    /* Регистрация через номер телефона ( получаем смс код с подтверждением на телефон) */
    function register_by_phone() {
        $url = '/v2/auth/phone/request';

        $data['phone'] = $this->phone;
        $data['os'] = 'Android';
        $data['client_secret'] = $this->client_secret;

        $result = $this->query($url, 'POST', $data);

        if( $result['http_code'] == '204'){
            return true;
        } else{
            return false;
        }
    }


    /* Получение токена через номер телефона */
    function get_new_token_by_phone($sms_code){
        $url = '/v2/auth/phone/verify';

        $data['phone'] = $this->phone;
        $data['client_secret'] = $this->client_secret;
        $data['code'] = $sms_code;

        $result = $this->query($url, 'POST', $data);

        if( $result['http_code'] == '200'){
            return $this->save_token($result['data'], 'Получен через авторизацию по телефону');
        } else{
            flash::add_toast('Money', 'Не удалось получить новый токен через авторизацию по телефону', 10, 'danger');
        }
    }


    // Обновить текущий токен
    function _act_refresh_current_token(){
        $nalogObj = $this->table->_DB_->q($this->table->getSelect(' ORDER BY `id` DESC LIMIT 1'));
        if($row = $nalogObj->fetch_assoc()){
            $this->do_refresh_token($row['rtoken'], $row['id']);

            return true;
        }

        return false;
    }


    /* Обновление токена через $refresh_token */
    function do_refresh_token($refresh_token, $old_token_id){
        $url = '/v2/mobile/users/refresh';

        $data['refresh_token'] = $refresh_token;
        $data['client_secret'] = $this->client_secret;

        $result = $this->query($url, 'POST', $data);

        if( $result['http_code'] == '200'){
            return $this->save_token($result['data'], 'Получен путём обновления токена с id ' . $old_token_id);
        } else{
            flash::add_toast('Money', 'Не удалось обновить старый токен через $refresh_token', 10, 'danger');
        }
    }


    // Сохранение токена в БД
    function save_token($data, $source){
        if( !$data['sessionId'] || !$data['refresh_token']) return false;

        $nalogObj = $this->table->byId(0);
        $nalogObj->set('token', $data['sessionId']);
        $nalogObj->set('rtoken', $data['refresh_token']);
        $nalogObj->set('source', $source);
        $nalogObj->set('datetime', time());
        $nalogObj->save();

        $this->token = $data['sessionId'];


        // Оставляем последние 10 токенов на всякий пожарный, остальные удаляем.
        $nalogObj = $this->table->getSSelect(' ORDER BY `id` DESC');
        $i = 0;
        while($row = $nalogObj->fetch_assoc()){
            $i++;
            if($i <= 10) continue;

            $this->table->byId($row['id']);
            $this->table->del();
        }


        return true;
    }

    // Получаем все чеки
    function get_all_tickets(){
        $url = '/v2/tickets/';

        $add_header[] = 'sessionId: ' . $this->token;

        $result = $this->query($url, 'GET', [], $add_header);

        switch($result['http_code']){
            case '200':
                return $result['data'];
                break;
            case '401':
                $this->_act_refresh_current_token();    // Пробуем обновить текущий токен
                $this->get_all_tickets();          // И заного получить все чеки

//                die('Не удалось получить данные по всем чекам. Http код: 401');
                break;
            default:
                return false;
        }
    }


    // Получаем id чека в налоговой
    function get_ticket_id($qr_str){
        $url = '/v2/ticket';

        $data['qr'] = $qr_str;
        $add_header[] = 'sessionId: ' . $this->token;

        $result = $this->query($url, 'POST', $data, $add_header);

        switch($result['http_code']){
            case '200':
                return $result['data'];
                break;
            case '401':
                $this->_act_refresh_current_token();    // Пробуем обновить текущий токен
                $this->get_ticket_id($qr_str);          // И заного получить id чека

//                $tpl_err = get_template('', 'nalog_token', 'error');
//                $tpl_err = set($tpl_err, 'error_desk', 'Не удалось получить ticket_id. Http код: 401.');
//                $tpl_err = set($tpl_err, 'sulution', 'Токен был принудительно обновлен, <a href="javascript:void(0)" onclick="window.location.reload()">попробуйте</a> ещё раз');
//                $this->_act_refresh_current_token();
//                viewController::display($tpl_err);
//                die();
                break;
            default:
                return false;
        }
    }


    // Получаем сформированный чек
    function get_ticket($ticket_id){
        $url = '/v2/tickets/' . $ticket_id;

        $add_header[] = 'sessionId: ' . $this->token;

        $result = $this->query($url, 'GET', [], $add_header);

        switch($result['http_code']){
            case '200':
                return $result['data'];
                break;
            case '401':
                $this->_act_refresh_current_token();    // Пробуем обновить текущий токен
                $this->get_ticket($ticket_id);          // И заного получить сформированный чек

//                $tpl_err = get_template('', 'nalog_token', 'error');
//                $tpl_err = set($tpl_err, 'error_desk', 'Не удалось получить данные по чеку. Http код: 401.');
//                $tpl_err = set($tpl_err, 'sulution', 'Токен был принудительно обновлен, <a href="javascript:void(0)" onclick="window.location.reload()">попробуйте</a> ещё раз');
//                $this->_act_refresh_current_token();
//                viewController::display($tpl_err);
//                die();
                break;
            default:
                return false;
        }
    }


    function getReceiptStatus($status_id){
        $receipt = [
            2   => ['title' => 'Чек получен', 'title_nalog' => 'HAVE_COPY'],
            20  => ['title' => 'Чек получен', 'title_nalog' => 'NPD_FOUND'],
            3   => ['title' => 'Чек в процессе получения', 'title_nalog' => 'COPY_REQUESTED'],
            11  => ['title' => 'Чек в процессе получения', 'title_nalog' => 'COPY_REQUESTED'],
            0   => ['title' => 'Чек в процессе получения', 'title_nalog' => 'HSM_REQUESTED'],
            7   => ['title' => 'Чек в процессе получения', 'title_nalog' => 'HSM_REQUESTED'],
            9   => ['title' => 'Чек в процессе получения', 'title_nalog' => 'HSM_REQUESTED'],
            422 => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'NPD_NOT_FOUND'],
            8   => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'HSM_NOK'],
            10  => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'HSM_NOK'],
            5   => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'RETRIEVE_FAILED'],
            16  => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'UNSUPPORTED_DOCUMENT_TYPE'],
            12  => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'STANDALONE_CASH'],
            13  => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'STANDALONE_CASH'],
            15  => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'STANDALONE_CASH'],
            4   => ['title' => 'Ошибка при получении чека', 'title_nalog' => 'ERROR'],
        ];

        $data = [];

        switch($status_id){
            case 2:
            case 20:
                $data['result'] = 'ok';
                break;

            case 0:
            case 3:
            case 7:
            case 9:
            case 11:
                $data['result'] = 'request';
                break;

            case 4:
            case 5:
            case 8:
            case 10:
            case 12:
            case 13:
            case 15:
            case 16:
            case 422:
                $data['result'] = 'error';
                break;

            default:
                $data['result'] = 'error_unknown';
        }

        $data['title'] = $receipt[$status_id]['title'];
        $data['title_nalog'] = $receipt[$status_id]['title_nalog'];
        $data['str'] = $data['title'].'<br> ('.$data['title_nalog'].')';

        return $data;
    }


    function query($url, $method = 'GET', $data = [], $add_header = []) {
        $full_url = 'https://' . $this->base_url . $url;

        $ch = curl_init();
            $headers    = [];
            $headers[]  = 'Host: ' . $this->base_url;
            $headers[]  = 'Accept: */*';
            $headers[]  = 'Device-OS: iOS';
            $headers[]  = 'Device-Id: 7C82010F-16CC-446B-8F66-FC4080C66521';
            $headers[]  = 'clientVersion: 2.9.0';
            $headers[]  = 'Accept-Language: ru-RU;q=1, en-US;q=0.9';
            $headers[]  = 'User-Agent: billchecker/2.9.0 (iPhone; iOS 13.6; Scale/2.00)';

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

    // Можно как то удалять чеки из базы налоговой
    function delete_receipt(){
       // @pawellrus чеки можно удалять из списка отправкой HTTP запроса:
       // DELETE /v2/tickets/<id чека>
       // После этого чек пропадёт из общего списка и будет недоступен по своему id. При повторном запросе чека он опять станет доступным по тому же id.
    }



    // Генерирование секрета
    function generate_secret_id(){
//        @3bl3gamer благодарю за такой подробный ответ! Дальнейшие поиски по приведённым вами кускам кода подтвердили предположение о получении client_secret из хэша подписи приложения. Получить исходное значение client_secret у меня получилось следующим образом:
//
//        извлекаем из .apk приложения файл подписи в формате PKCS#7 BNDLTOOL.RSA
//        с помощью openssl извлекаем из него сертификат openssl pkcs7 -in BNDLTOOL.RSA -inform DER -print_certs получаем на выходе:
//        subject=/C=Russia/ST=Nizhegorodskaya oblast/L=N.Novgorod/O=Studio_TG/OU=RPO/CN=Sachkov Dmitry
//        issuer=/C=Russia/ST=Nizhegorodskaya oblast/L=N.Novgorod/O=Studio_TG/OU=RPO/CN=Sachkov Dmitry
//                -----BEGIN CERTIFICATE-----
//        MIIDpTCCAo2gAwIBAgIED1QoaDANBgkqhkiG9w0BAQsFADCBgjEPMA0GA1UEBhMG
//        UnVzc2lhMR8wHQYDVQQIExZOaXpoZWdvcm9kc2theWEgb2JsYXN0MRMwEQYDVQQH
//        EwpOLk5vdmdvcm9kMRIwEAYDVQQKDAlTdHVkaW9fVEcxDDAKBgNVBAsTA1JQTzEX
//        MBUGA1UEAxMOU2FjaGtvdiBEbWl0cnkwHhcNMTYxMDEzMTM1MzI3WhcNNDExMDA3
//        MTM1MzI3WjCBgjEPMA0GA1UEBhMGUnVzc2lhMR8wHQYDVQQIExZOaXpoZWdvcm9k
//        c2theWEgb2JsYXN0MRMwEQYDVQQHEwpOLk5vdmdvcm9kMRIwEAYDVQQKDAlTdHVk
//        aW9fVEcxDDAKBgNVBAsTA1JQTzEXMBUGA1UEAxMOU2FjaGtvdiBEbWl0cnkwggEi
//        MA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDW+7/Mgd9ucvuNZhUWpxfkYZ+Z
//        +YhlFB7ITcQsyF8CN1iFmrvnNCkl6Oz1FgkbsjTtR+r7NG0gc4aqyqftYO6aBhNX
//        JUWU6jQZMhGgWtQbMqjXKUchsq7y5D4n2MUWC2UFrVcK5Ao56N2rijacUMsLLkkv
//        STwPWEwjBXdU5IcbhFXOuk0k/hn+G3e8ZT+PUZ4f4WxWL1HDgn5sE6dzGyyPe77/
//        2I4OsiGS8/Llsr0B2xS3DEf9STFny9H/1J9MmvIi/vM1VVq+UKEBmpXURuGjQZp3
//        AXbJk/4EQkirMnVOl7ts/B05/35qt6+NJJp0w31Awr27GPNVzgVNB13crCXHAgMB
//        AAGjITAfMB0GA1UdDgQWBBQkneiTl7Dz+WlDLc4ylQeDd+VbuzANBgkqhkiG9w0B
//        AQsFAAOCAQEANs56jQAro5KodzMWEl3j4dYUVN2/PrH4msI8utyTjuR7K6gq6BrV
//        9BwqseBXxNw6R5Vk/ZidbJSFT9sv5yYUNrs8Ybw/AiABF/M3DRV3wvjAVRhQFLv9
//        QpRFCpMbzi/TSK3+fWtT33oGV58uRPd6caD9vRwNeNzKHUVIK2R4qGiYkboAfd8i
//        p6c+cAUFuaCaI+CuGyVx/XfkUkkI1RPpYPi2f90G1ZsEU1ZUya4ljxeFCtkjmHYl
//        tawfAGZvteZ20GuAw2fu/7ExU0Ei7u1ltNzsF3LbH1a+BDHPI2y1NtXndoh5Uou6
//        V9b7lMI0ilAKP8XYPuzu7JdIhaeLH9j0DQ==
//        -----END CERTIFICATE-----
//        то что между BEGIN и END декодируем в байты и вычисляем от этого SHA-1. На Python например вот так:
//        import base64
//        import hashlib
//        cert_bytes = base64.b64decode("MIIDpTCCAo2gAwIBAgIED1QoaDANBgkqhkiG9w0BAQsFADCBgjEPMA0GA1UEBhMGUnVzc2lhMR8wHQYDVQQIExZOaXpoZWdvcm9kc2theWEgb2JsYXN0MRMwEQYDVQQHEwpOLk5vdmdvcm9kMRIwEAYDVQQKDAlTdHVkaW9fVEcxDDAKBgNVBAsTA1JQTzEXMBUGA1UEAxMOU2FjaGtvdiBEbWl0cnkwHhcNMTYxMDEzMTM1MzI3WhcNNDExMDA3MTM1MzI3WjCBgjEPMA0GA1UEBhMGUnVzc2lhMR8wHQYDVQQIExZOaXpoZWdvcm9kc2theWEgb2JsYXN0MRMwEQYDVQQHEwpOLk5vdmdvcm9kMRIwEAYDVQQKDAlTdHVkaW9fVEcxDDAKBgNVBAsTA1JQTzEXMBUGA1UEAxMOU2FjaGtvdiBEbWl0cnkwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDW+7/Mgd9ucvuNZhUWpxfkYZ+Z+YhlFB7ITcQsyF8CN1iFmrvnNCkl6Oz1FgkbsjTtR+r7NG0gc4aqyqftYO6aBhNXJUWU6jQZMhGgWtQbMqjXKUchsq7y5D4n2MUWC2UFrVcK5Ao56N2rijacUMsLLkkvSTwPWEwjBXdU5IcbhFXOuk0k/hn+G3e8ZT+PUZ4f4WxWL1HDgn5sE6dzGyyPe77/2I4OsiGS8/Llsr0B2xS3DEf9STFny9H/1J9MmvIi/vM1VVq+UKEBmpXURuGjQZp3AXbJk/4EQkirMnVOl7ts/B05/35qt6+NJJp0w31Awr27GPNVzgVNB13crCXHAgMBAAGjITAfMB0GA1UdDgQWBBQkneiTl7Dz+WlDLc4ylQeDd+VbuzANBgkqhkiG9w0BAQsFAAOCAQEANs56jQAro5KodzMWEl3j4dYUVN2/PrH4msI8utyTjuR7K6gq6BrV9BwqseBXxNw6R5Vk/ZidbJSFT9sv5yYUNrs8Ybw/AiABF/M3DRV3wvjAVRhQFLv9QpRFCpMbzi/TSK3+fWtT33oGV58uRPd6caD9vRwNeNzKHUVIK2R4qGiYkboAfd8ip6c+cAUFuaCaI+CuGyVx/XfkUkkI1RPpYPi2f90G1ZsEU1ZUya4ljxeFCtkjmHYltawfAGZvteZ20GuAw2fu/7ExU0Ei7u1ltNzsF3LbH1a+BDHPI2y1NtXndoh5Uou6V9b7lMI0ilAKP8XYPuzu7JdIhaeLH9j0DQ==")
//        hash = hashlib.sha1()
//        hash.update(cert_bytes)
//        hash.hexdigest()
//        На выходе получаем:
//        '9a700b8caa1baea4ffb02f6e9b8c1795a9979cea'
//        Уже сейчас можно сравнить это с выводом openssl pkcs7 -in BNDLTOOL.RSA -inform DER -print_certs | openssl x509 -fingerprint -noout:
//        SHA1 Fingerprint=9A:70:0B:8C:AA:1B:AE:A4:FF:B0:2F:6E:9B:8C:17:95:A9:97:9C:EA
//        Как можно убедиться - результат идентичен.
//                Ну и в конце получим результат хэширования закодированный в Base64 base64.b64encode(hash.digest()).decode("utf-8"):
//        'mnALjKobrqT/sC9um4wXlamXnOo='
    }
}