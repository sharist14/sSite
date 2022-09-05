<?php
/**
 * Обработка полученных данных извне
 */

class ext_api extends parentTemplate
{

    var $raw_data;
    var $arr_data;
    var $json_data;


    function _act_(){
        $this->raw_data = file_get_contents('php://input');                       // Получаем сырые данные

        if(json_decode($this->raw_data)){
            $this->json_data = $this->raw_data;                                                 // Если данные пришли сразу в json
            $this->arr_data = json_decode($this->raw_data, true);
        } else{
            parse_str($this->raw_data, $this->arr_data);                                  // Преобразовываем в массив
            $this->json_data = json_encode($this->arr_data, JSON_UNESCAPED_UNICODE);    // Преобразовываем в json
        }


        // Проверка на дубль и сохранение
        $table = new table('ext_api');
        $extapiObj = $table->_DB_->q($table->getSelect(' AND `raw_data` = "'.$this->raw_data.'"'));
        if( !$extapiObj->fetch_assoc()){
            $extApi = $table->byId(0);
            $extApi->set('title', 'test');
            $extApi->set('ip', $_SERVER['REMOTE_ADDR']);
            $extApi->set('datetime', time());
            $extApi->set('raw_data', $this->raw_data);
            $extApi->set('json_data', $this->json_data);
            $extApi->save();

            // если СМС от Сбера - cохраняем в Money flow
            $res = $this->check_sber_data($extApi->id);

            echo "OK";
        }


    }



    // Проверка СМС от Сбера
    function check_sber_data($extapi_id){

        // Проверка на наобходимые поля в данных
        if($this->arr_data['phone'] == '900' && $this->arr_data['sim'] && $this->arr_data['text'] ){
            $table = new table('money_flow');

                // Ищем последние 4 цифры карты, дата операции, тип операции, сумма операции, остаток на счёте
                // Пример: https://regex101.com/r/vljzcx/1
                $pattern = '(?<=\w-)(\d{4})\s?((\d{2}.\d{2}.\d{2})?\s\d{2}:\d{2})\s(\w+)\s(\d+(\.\d+)?)(?=р).*(?<=Баланс:\s)(\d+(\.\d+)?)(?=р$)';
                preg_match('~'.$pattern.'~u', $this->arr_data['text'], $matches);

                if($matches[1] && $matches[2] && $matches[4] && $matches[5] && $matches[7]) {

                    // Если время с датой (напр 19.12.21 19:44 переводим в понятный вид для функции даты 12/19/21 19:44)
                    $date_not_format = trim($matches[2]);
                    if (preg_match('~^\d{2}.\d{2}.\d{2}~', $date_not_format)) {
                        $pattern = "~^(\d{2}).(\d{2}).(\d{2})\s(\d{2}:\d{2})~";
                        $replacement = "\${2}/\${1}/\${3} \${4}";
                        $date_format = preg_replace($pattern, $replacement, $date_not_format);
                    } else {
                        $date_format = $date_not_format;
                    }

                    $op_datetime = strtotime($date_format); // перевели в unixtime

                    $card_no = $matches[1];
                    $op_type = mb_strtolower($matches[4], 'UTF-8');
                    $op_sum = $matches[5];
                    $balance = $matches[7];

                    // Определяем тип операции
                    switch($op_type){
                        case "покупка":
                        case "перевод":
                        case "списание":
                            $op_sign = -1;
                            $money_type_id = 3;    // расход
                            break;

                        case "зачисление":
                            $op_sign = 1;
                            $money_type_id = 2;    // приход
                            break;

                        default:
                            $op_sign = 1;
                            $money_type_id = 1;    // не определено
                    }


                    // Проверка на дубль и сохранение
                    $moneyObj = $table->_DB_->q($table->getSelect(' AND `title` = "' . $op_type . '" AND `op_sum` = "' . $op_sum . '" AND FROM_UNIXTIME(`op_datetime`, "%Y%m%d") = "' . date("Ymd", $op_datetime) . '"'));
                    if (!$moneyObj->fetch_assoc()) {

                        $moneyObj = $table->byId(0);
                        $moneyObj->set('balance', $balance);
                        $moneyObj->set('title', $op_type);
                        $moneyObj->set('op_sum', $op_sum * $op_sign);
                        $moneyObj->set('card_no', $card_no);
                        $moneyObj->set('op_datetime', $op_datetime);
                        $moneyObj->set('money_type_id', $money_type_id);
                        $moneyObj->set('ext_api_id', $extapi_id);

                        $moneyObj->save();
                    }
                }

            return true;
        } else{
            return false;
        }
    }


}