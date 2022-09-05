<?php
/** Операции с расходами
 * Документация (неофициальная) https://github.com/nikolaynau/fns-api/tree/master/docs/spec/ru
 */


require_once(_MODULES_ . '/api/nalog.api.php');


class money_out extends _moneyFlow_parent
{
    var $table;

    var $id_account_from = 2;               // id счёта списания
    var $type_ru = 'расход';                // заголовок операции
    var $type_en = 'out';                   // заголовок операции

    var $block_account_from = true;         // блок "Счет списания"
    var $block_account_to = false;          // блок "Счет зачисления"
    var $block_type = true;                 // блок "Тип операции"
    var $block_entity = true;               // блок "Юр лицо(вручную)"
    var $block_receipt = true;              // блок "Кассовый чек"
    var $block_debt = true;                 // блок "Взять вернуть в долг" (с выбором кредитора)
    var $chb_manual_confirm = true;         // чекбокс "Подтвердить вручную"

    var $str_month_summ = true;             // строка с суммой за месяц
    var $str_day_summ = true;               // строка с суммой за день
    
    function __construct(array $params = []) {
        parent::__construct($params);
    }


    /**
     * Сохраняем доход в БД
     */
    function _act_save() {
        if (!$_POST) die('Нет данных для сохранения');
        $data = $_POST;


        // Определяем на каком счете будет операция
        $data['money_account_id'] = $data['money_account_from'];
        unset($data['money_account_to']);


        // Сохраняем в БД
        $result = $this->do_save($data, -1);


        // Если указан кассовый чек - пытаемся сразу получить данные по api
        if($result['status'] == 'create' && $data['fn'] && $data['i'] && $data['fp'] ){
            $this->_act_get_receipt_from_nalog($result['id'], false);
        }


        // Если берём в долг
        if( $data['debt_account'] && $data['debt_summa'] ){
            $data2 = [];

            // Если обновляем основные данные, обновляем и инфо по долгу
            if($result['status'] == 'update'){
                $db = $this->table->getSSelect(' AND `debt_flow_id` = "'.intval($result['id']).'" AND `op_sign` < 0');
                if($row = $db->fetch_assoc()){
                    $data2['id'] = $row['id'];
                    $data2['linked_id'] = $row['linked_id'];
                }
            }

            $data2["type_en"] = 'transfer';
            $data2["t"] = date_timestamp($data['t'], 'iso8601') - 60;
            $data2["s"] = $data['debt_summa'];
            $data2["money_type_id"] = 6;
            $data2["is_debt"] = $data["is_debt"];
            $data2["comment"] = $data["debt_comment"];
            $data2["money_account_from"] = $data['debt_account'];
            $data2["money_account_to"] = $data['money_account_id'];
            $data2["debt_flow_id"] = $result['id'];

            $transfer = new money_transfer();
            $transfer->_act_save($data2, true);
        }


        // Пагинация
        $page = '';
        switch($result['status']){
            case 'create':
            case 'update':
                // Находим страницу пагинации
                $page = getPageByDate($result['t']);
                break;
            case 'duplicate':
                break;
        }

        flash::add_toast('Money',$this->frame_info[$result['status']], 3);
        _redirect('/money_out/?'.$page.'&#r'.$result['id']);
    }


    /**
     * Получаем и сохраняем ticket_id и json_data чека по api
     */
    function _act_get_receipt_from_nalog($id = '', $redirect = true){
        $nalogApi = new nalogApi();
        $nalogApi->load_token();

        $flow_id = '';
        if($id){
            $flow_id = intval($id);
        } elseif($_GET['id']){
            $flow_id = intval($_GET['id']);
        }
        if( !$flow_id ) die('Нет flow_id, который необходим для получения ticket_id и json_data');

        $status = 'update';
        $result = [];

        // Формируем строку с данными по чеку
        $flowObj = $this->table->byId(intval($flow_id));

        // Находим страницу пагинации
        $page = getPageByDate($flowObj->t);

        // Пробуем получить ticket_id из налоговой
        if( !$flowObj->ticket_id ){

            $qr_str = $this->qrStrByID($flow_id);             // Получаем строку с данными
            $api_data = $nalogApi->get_ticket_id($qr_str);    // Посылаем запрос

            // Переводим в читабельный статус
            $statusInfo = $nalogApi->getReceiptStatus($api_data['status']);
            
            switch($statusInfo['result']){
                case 'request':
                    $flowObj->set('ticket_id', $api_data['id']);
                    $flowObj->set('status_id', 2);   // статус: идёт формирование
                    $flowObj->save();
                    break;

                case 'ok':
                    if( !empty($api_data['id'])){
                        $flowObj->set('ticket_id', $api_data['id']);
                        $flowObj->set('status_id', 3);   // статус: данные сформированы
                        $flowObj->save();
                    }
                    break;

                case 'error':
                    $flowObj->set('status_id', 4);   // статус: Ошибка
                    $flowObj->save();
                    flash::add_toast('Налоговая', $statusInfo['str'], 5, 'danger');
                    break;
            }
        }

        // Получаем и сохраняем данные по чеку от налоговой
        if( $flowObj->ticket_id && !$flowObj->json_data ) {

            // Отправляем запрос
            $receipt_data = $nalogApi->get_ticket($flowObj->ticket_id);
            
            // Переводим в читабельный статус
            $statusInfo = $nalogApi->getReceiptStatus($receipt_data['status']);

            // Сохраняем
            if($statusInfo['result'] == 'ok' && $receipt_data['ticket']) {
                $status_time = time();

                // Ищем юр лицо
                $inn = trim($receipt_data['ticket']['document']['receipt']['userInn']);
                $entity_name = trim($receipt_data['ticket']['document']['receipt']['user']);

                $money_index = new index();
                $entity_id = $money_index->get_entity_id($inn, $entity_name);
                $flowObj->set('entity_id', $entity_id);


                $flowObj->set('json_data', json_encode($receipt_data['ticket'], JSON_UNESCAPED_UNICODE));
                $flowObj->set('updatetime', $status_time);
                $flowObj->set('status_id', 3);   // статус: данные сформированы
                $flowObj->save();

                // Сохраняем время запроса данных в налоговую
                $querytime = new table('nalog_querytime');
                $querytimeObj = $querytime->byId(0);
                $querytimeObj->set('datetime', $status_time);
                $querytimeObj->save();
            }

            if( $statusInfo['result'] == 'error' ) flash::add_toast('Налоговая', $statusInfo['str'], 5, 'danger');
        }


        // Находим страницу пагинации
        $page = getPageByDate($flowObj->t);

        if($redirect){
            _redirect('/money_out/?'.$page.'&#r'.$flowObj->id);
        } else return true;
    }


    /**
     * Отображаем опеределенный чек
     */
    function _act_view_receipt(){
        $tpl = get_template('', $this->module, 'receipt_view_body');
        $tplr = get_template('', $this->module, 'item_row');
        if( !$_GET['id'] ) die('Нет flow_id, который необходим для отображения чека');

        // Получаем массив с данными из БД
        $receiptObj = $this->table->byId( intval($_GET['id']) );
        $receipt_full_data = json_decode($receiptObj->json_data, true);
        $receipt = $receipt_full_data['document']['receipt'];


        // Вставляем общие данные
        $tpl = set($tpl, 'user', $receipt['user'] );
        $tpl = set($tpl, 'retailPlace', $receipt['retailPlace'] );
        $tpl = set($tpl, 'datetime', date('d.m.Y H:i', $receipt['dateTime']) );
        $tpl = set($tpl, 'totalSum', $receipt['totalSum']/100 );
        $tpl = set($tpl, 'ecashTotalSum', $receipt['ecashTotalSum']/100 );
        $tpl = set($tpl, 'cashTotalSum', $receipt['cashTotalSum']/100 );
        $tpl = set($tpl, 'kktRegId', $receipt['kktRegId'] );
        $tpl = set($tpl, 'fiscalDriveNumber', $receipt['fiscalDriveNumber'] );
        $tpl = set($tpl, 'fiscalDocumentNumber', $receipt['fiscalDocumentNumber'] );
        $tpl = set($tpl, 'fiscalSign', $receipt['fiscalSign'] );
        $tpl = set($tpl, 'userInn', $receipt['userInn'] );
        $tpl = set($tpl, 'requestNumber', $receipt['requestNumber'] );
        $tpl = set($tpl, 'shiftNumber', $receipt['shiftNumber'] );
        $tpl = set($tpl, 'retailPlace', $receipt['retailPlace'] );

        $op_type = [1 => 'ПРИХОД', 2 => 'ВОЗВРАТ ПРИХОДА', 3 => 'РАСХОД', 4 => 'ВОЗВРАТ РАСХОДА'];
        $tpl = set($tpl, 'operationType', $op_type[$receipt['operationType']]);

        $tax = [1 => 'ОСН', 2 => 'УСН доход', 4 => 'УСН доход - Расход', 8 => 'ЕНВД', 16 => 'ЕСХН', 32 => 'Патент'];
        $tpl = set($tpl, 'tax', $tax[$receipt['taxationType']] );

        if($receipt['retailPlaceAddress']){
            $tpl = set($tpl, 'retailPlaceAddress', $receipt['retailPlaceAddress'] );
        } else{
            $tpl = set($tpl, 'retailPlaceAddress_hide', 'hide' );
        }

        if($receipt['operator']){
            $tpl = set($tpl, 'operator', $receipt['operator'] );
        } else{
            $tpl = set($tpl, 'operator_hide', 'hide' );
        }


        // Вставляем товары
        foreach($receipt['items'] as $item){
            $tt = $tplr;
            $tt = set($tt, 'title', $item['name'] );
            $tt = set($tt, 'price', number_format($item['price']/100, 2, '.', '') );
            $tt = set($tt, 'count', $item['quantity'] );
            $tt = set($tt, 'summ_row', number_format($item['sum']/100, 2, '.', '') );
            $tpl = setm($tpl, 'item_rows', $tt );
        }


        // Вставляем QR код
        require_once(_ROOT_DIR_.'/engine/core/common_api/TCPDF/tcpdf_barcodes_2d.php');
        $qr_code = $this->qrStrByID($receiptObj->id);
        $barcodeobj = new TCPDF2DBarcode($qr_code, 'QRCODE,H');                    // set the barcode content and type
        $qr_code = $barcodeobj->getBarcodeSVGcode(4, 4, 'black');           // output the barcode as SVG inline code
        $tpl = set($tpl, 'qr_code', $qr_code );

        echo viewController::clear($tpl);
    }


    /**
     * Генерируем QR строку по id
     */
    function qrStrByID($id){
        // Получаем данные
        $flowObj = $this->table->byId($id);
        $data = $flowObj->get_vars_values();

        // Генерируем массив с данными
        $summa = abs($data['s']);   // убираем у суммы знак числа
        $summa = number_format($summa, 2, '.', '');  // переводим сумму в нужный формат

        $qr_arr['t'] = date_iso8601($data['t'], 'ts');
        $qr_arr['s'] = $summa;
        $qr_arr['fn'] = $data['fn'];
        $qr_arr['i'] = $data['i'];
        $qr_arr['fp'] = $data['fp'];
        $qr_arr['n'] = $data['n'];

        // Трансформируем в GET строку
        $qr_str = http_build_query($qr_arr);

        return $qr_str;
    }




    /**
     * Добавить расход используя камеру на телефоне и QR на чеке
     */
    function _act_add_qr(){
        $tpl = get_template('', $this->module, 'add_qr');

        $this->render($tpl, []);
    }


    /**
     * Проверка кассового qr кода
     */
    function _ajax_check_receipt_qr(){
        $answer = [];

        if($_POST['qr_scan']) {
            $scan_code = $_POST['qr_scan'];

            // Переводим в массив
            parse_str($scan_code, $receipt_arr);

            // Проверяем что qr - кассовый чек
            $barcode_type = get_type_barcode($receipt_arr, $scan_code);

            if($barcode_type == 'receipt'){
                $answer['title'] = 'Ok';    // На фронте заполняем форму и отправляем на сохранение
            } else{
                $answer['title'] = 'Данный чек не кассовый, его формат определен как: ' . $barcode_type;
                $answer['error'] = true;
            }

            // Проверяем что нет дублей
            $receiptObj = $this->table->_DB_->q($this->table->getSelect(' AND `t` = "'.date_timestamp($receipt_arr["t"], "iso8601").'" AND `s` = "'.-1 * abs($receipt_arr["s"]).'"'));
            if( $row = $receiptObj->fetch_assoc()) {
                $answer['title'] = 'Данный чек в БД уже есть';
                $answer['error'] = true;
            }

        } else{
            $answer['title'] = 'Ошибка: Это не POST запрос';
            $answer['error'] = true;
        }

        echo  json_encode($answer);
        die();
    }



    /**
     * Подтверждаем расход вручную
     */
    function _act_receipt_manual_confirm($id = ''){
        $id = '';
        if($id){
            $id = intval($id);
        } elseif($_GET['id']){
            $id = intval($_GET['id']);
        }
        if( !$id ) die('Нет flow_id, который необходим подтверждения операции');

        // Формируем строку с данными по чеку
        $flowObj = $this->table->byId($id);
        $flowObj->set('status_id', 5);
        $flowObj->save();

        $status = 'update';

        // Находим страницу пагинации
        $page = getPageByDate($flowObj->t);
        flash::add_toast('Money',$this->frame_info[$status], 3);
        _redirect('/money_out/?'.$page.'&#r'.$flowObj->id);
    }



    /**
     * Включения стилей и скриптов
     */
    public function getInclude(){

        $include['head'][]= '
            <link rel="shortcut icon" href="https://learncodeweb.com/demo/favicon.ico">
            <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
            <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
            <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
            <script>
              (adsbygoogle = window.adsbygoogle || []).push({
                google_ad_client: "ca-pub-6724419004010752",
                enable_page_level_ads: true
              });
            </script>
            <!-- Global site tag (gtag.js) - Google Analytics -->
            <script async src="https://www.googletagmanager.com/gtag/js?id=UA-131906273-1"></script>
            <script>
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag("js", new Date());
              gtag("config", "UA-131906273-1");
            </script>
	    ';

        return $include;
    }



}