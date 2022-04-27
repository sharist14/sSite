<?php
/** Операции с кассовыми чеками */
require_once(_MODULES_ . '/api/nalog.api.php');

class receipt extends parentTemplate
{
    var $table;
    var $nalogApi;
    
    function __construct(array $params = [])
    {
        $this->table = new table('receipt');
        $this->nalogApi = new nalogApi();
        $this->nalogApi->load_token();

        parent::__construct($params);
    }


    // Вывод всех чеков
    function _act_(){
        $tpl = get_template('', $this->module, 'body');
        $tpldr = get_template('', $this->module, 'day_row');
        $tplrr = get_template('', $this->module, 'receipt_row');

        // Общая информация

        // Лимит запросов к api (на форумах писали, что при запросах на получение чека свыше 25 раз, учетку могуь заблокировать. Поэтому ставлю 20 на вский пожарный)
        $table = new table('nalog_querytime');
        $query_today = $table->getCount(' AND FROM_UNIXTIME(`datetime`, "%Y%m%d") = ' . date("Ymd", time()));
        $tpl = set($tpl, 'limit_query_api', 20 - intval($query_today) );

        // Информация по чекам
        $db = $this->table->_DB_->q('SELECT FROM_UNIXTIME(`t`, "%Y%m%d") AS "buy_date", `t` as "buy_date_ts", COUNT(*) AS "cnt", SUM(s) as "day_summ" FROM receipt GROUP BY FROM_UNIXTIME(`t`, "%Y%m%d") ORDER BY buy_date DESC');
        while($row = $db->fetch_assoc()){

            /* ЗАПОЛНЯЕМ ДАТЫ */
            $buy_date = $row['buy_date'];
            $tt = $tpldr;
            $tt = set($tt, 'title_date', date('d.m.Y', $row['buy_date_ts']));
            $tt = set($tt, 'buy_date', $buy_date);
            $tt = set($tt, 'cnt', $row['cnt']);
            $tt = set($tt, 'day_summ', $row['day_summ']);


            // День недели
            $num_week = date('w', $row['buy_date_ts']);
            $week = day_of_week($num_week, 'ru_short');
            $tt = set($tt, 'week', $week);


            /* ЗАПОЛНЯЕМ ЧЕКИ */
            $db2 = $this->table->_DB_->q($this->table->getSelect(' AND FROM_UNIXTIME(`t`, "%Y%m%d") = "'.$buy_date.'" ORDER BY `t` DESC'));
            while($row2 = $db2->fetch_assoc() ){
                $tt2 = $tplrr;
                $tt2 = set($tt2, 'title_date', date('H:i', $row2['t']));
                $tt2 = set($tt2, 'receipt_summa', $row2['s']);

                $seller = '';

                // Определяем статус
                $update_status_link = '<a href="/receipt?act=get_receipt_from_nalog&receipt_id='.$row2["id"].'" class="btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/sync_logo1.png" alt=""></a>';
                $download_receipt = '<a href="/receipt?act=view_receipt&receipt_id='.$row2["id"].'" class="btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/receipt_icon.png" alt=""></a>';
                $accept_manual = '<img class="btn p-0 d-inline-block" style="width: 40px" src="'._IMG_.'/_static/accept_manual.png" alt="">';

                if($row2['manual_type']) $manual = ' (задан вручную) ';
                if($row2['comment']) $comment = ' (комментарий: '.$row2['comment'].') ';

                if($row2['error']){
                    $status = 'Статус: Чек некорректен (id='.$row2["id"].')'.$manual.$comment;
                    $color_status = 'bg-dark';
                    $action_btn = $update_status_link;
                } elseif( $receipt_data = json_decode($row2['json_data'], true) ){
                    // Если есть json c данными
                    $seller = $receipt_data['document']['receipt']['user'];
                    $status = ucfirst_utf8(mb_strtolower($seller));
                    $color_status = 'bg-success';
                    $action_btn = $download_receipt;

                } elseif( $row2['manual_confirm'] ) {
                    // Если чек подтверждён вручную
                    $status = 'Статус: подтверждён вручную';
                    $color_status = 'bg-success';
                    $action_btn = $accept_manual;

                } elseif( $row2['ticket_id'] ){
                    $status = 'Статус: идёт формирование'.$manual.$comment;
                    $color_status = 'bg-warning';
                    $action_btn = $update_status_link;

                } else{
                    $status = 'Статус: новый чек'.$manual.$comment;
                    $color_status = 'bg-danger';
                    $action_btn = $update_status_link;
                }

                $tt2 = set($tt2, 'bg_color', $color_status);     // Задаём цвет фона
                $tt2 = set($tt2, 'action_btn', $action_btn);     // Задаём кнопку статуса
                $tt2 = set($tt2, 'status', $status);             // Задаём статус
                $tt2 = set($tt2, 'receipt_id', $row2['id']);     // id в БД

                $tt = setm($tt, 'receipt_rows', $tt2);
            }



            $tpl = setm($tpl, 'day_rows', $tt);
        }

        $this->render($tpl, []);
    }


    // Сохраняем ticket_id и json_data по чеку
    function _act_get_receipt_from_nalog($id = ''){
        $receipt_id = '';
        if($id){
            $receipt_id = intval($id);
        } elseif($_GET['receipt_id']){
            $receipt_id = intval($_GET['receipt_id']);
        }
        if( !$receipt_id ) die('Не хватает необходимых данных для запроса');

        // Формируем строку с данными по чеку
        $receiptObj = $this->table->byId(intval($receipt_id));

        // Пробуем получить ticket_id из налоговой
        if( !$receiptObj->ticket_id ){

            $qr_str = $this->qrStrByID($receipt_id);             // Получаем строку с данными
            $api_data = $this->nalogApi->get_ticket_id($qr_str); // Посылаем запрос


            // Получаем и сохраняем ticket_id
            if($api_data['status'] == 2 && $api_data['id']) {
                $receiptObj->set('ticket_id', $api_data['id']);
                $receiptObj->set('error', 0);
                $receiptObj->save();
            } else {
                $receiptObj->set('error', 1);
                $receiptObj->save();

                $tpl_error = get_template('', $this->module, 'error_body');
                $err_title = 'При попытке получить ticket_id от api пришла ошибка, что указаны неверные данные в зaпросе';
                $tpl_error = set($tpl_error, 'err_title', $err_title);

                $manual_edit = '<a href=/receipt?act=add_manual&id='.$receipt_id.'>Поправьте данные по чеку</a>';
                $manual_confirm = '<a href=/receipt?act=receipt_manual_confirm&receipt_id='.$receipt_id.'>Подтвердите чек вручную</a>';
                $info = 'Возможные решения: <br> 1. '.$manual_edit.' и повторите попытку синхронизации <br> 2. '.$manual_confirm.' (будет учитываться дата и сумма чека, но не будет данных по покупке)';
                $tpl_error = set($tpl_error, 'info', $info);

                $this->render($tpl_error);
                die();
            }
        }

        // Получаем и сохраняем данные по чеку от налоговой
        if( $receiptObj->ticket_id && !$receiptObj->json_data ) {

            // Отправляем запрос
            $receipt_data = $this->nalogApi->get_ticket($receiptObj->ticket_id);

            // Сохраняем
            if($receipt_data['status'] == 2 && $receipt_data['ticket']) {
                $status_time = time();

                $receiptObj->set('json_data', json_encode($receipt_data['ticket'], JSON_UNESCAPED_UNICODE));
                $receiptObj->set('updatetime', $status_time);
                $receiptObj->save();

                $querytime = new table('nalog_querytime');
                $querytimeObj = $querytime->byId(0);
                $querytimeObj->set('datetime', $status_time);
                $querytimeObj->save();
            } else {
                $link_manual = '<a href=/receipt?act=add_manual&id='.$receipt_id.'>проверьте запрашиваемый чек</a>';
                print_r('Ошибка:<br> На шаге получения json с данными по чеку, '.$link_manual.' и повторите попытку');
                $receiptObj->set('error', 1);
                die();
            }
        }


        _redirect('/receipt?#r'.$receipt_id);
    }

    // Отображаем опеределенный чек
    function _act_view_receipt(){
        $tpl = get_template('', $this->module, 'receipt_view_body');
        $tplr = get_template('', $this->module, 'item_row');
        if( !$_GET['receipt_id'] ) die('Не хватает необходимых данных для запроса');

        // Получаем массив с данными из БД
        $receiptObj = $this->table->byId( intval($_GET['receipt_id']) );
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
        require_once(_ROOT_DIR_.'engine/core/common_api/TCPDF/tcpdf_barcodes_2d.php');
        $qr_code = $this->qrStrByID($receiptObj->id);
        $barcodeobj = new TCPDF2DBarcode($qr_code, 'QRCODE,H');                    // set the barcode content and type
        $qr_code = $barcodeobj->getBarcodeSVGcode(4, 4, 'black');           // output the barcode as SVG inline code
        $tpl = set($tpl, 'qr_code', $qr_code );


        $this->render($tpl);
    }



    // Добавить чек
    function _act_add(){
        $cli_os = getOS();

        // Считываем чек автоматически или вручную
        if(in_array($cli_os, ['android', 'ios'])){
            _redirect('?act=add_qr');
        }

        _redirect('?act=add_manual');
    }

    // Добавить используя камеру на телефоне и QR на чеке
    function _act_add_qr(){
        $tpl = get_template('', $this->module, 'add_qr');

        $this->render($tpl, []);
    }


    // Сохранение распознанного qr кода
    function _ajax_save_receipt_qr(){
        $answer = [];

        if($_POST['qr_scan']) {
            $scan_code = $_POST['qr_scan'];

            // Переводим в массив
            parse_str($scan_code, $receipt_arr);

            // Добавляем комментарий
            if($_POST['comment']) $receipt_arr['comment'] = $_POST['comment'];

            // Проверяем и сохраняем
            $answer = $this->do_save($receipt_arr);

        } else{
             $answer['title'] = 'Ошибка: Это не POST запрос';
             $answer['error'] = true;
        }

        echo  json_encode($answer);
        die();
    }


    // Добавить чек вручную
    function _act_add_manual(){
        $tpl = get_template('', $this->module, 'add_manual');
        
        // Если задан id редактируем имеющийся чек
        if($_GET['id']){
            $receiptObj = $this->table->byId( intval($_GET['id']) );
            $data = $receiptObj->get_vars_values();

            foreach($data as $k => $v){
                if($k == 't'){
                    $v = date('Y-m-d\TH:i', $v);    // Переводим дату из 20211113T122600 в формат 2021-11-03T15:57
                }
                $tpl = set($tpl, $k, $v);
            }

            // Кнопка удаления чека
            $tpl = set($tpl, 'receipt_id', $receiptObj->id);

        } else{
            $tpl = set($tpl, 'hide_del_btn', 'hide');
        }

        $this->render($tpl, []);
    }


    // Удалить чек
    function _act_delete_receipt($id = ''){
        $receipt_id = '';
        if($id){
            $receipt_id = intval($id);
        } elseif($_GET['receipt_id']){
            $receipt_id = intval($_GET['receipt_id']);
        }
        if( !$receipt_id ) die('Не хватает необходимых данных для запроса');

        $receiptObj = $this->table->byId(intval($receipt_id));
        $receiptObj->del();

        _redirect('/receipt');
    }

    // Сохранение чека указанного вручную
    function _act_save_manual(){
        $tpl = get_template('', $this->module, 'save_manual');
        $answer = [];

        if($_POST){
            $receipt_arr = $_POST;
            $tpl = set($tpl, 'receipt_id', $receipt_arr['receipt_id']);

            // Проверяем что сумма указана в копейках
            $receipt_arr['s'] = number_format($receipt_arr['s'], 2, '.', '');

            // Переводим дату из 2021-11-03T15:57 в формат 20211113T122600
            $correct_date = str_replace(['-', ':'], '', $receipt_arr['t']);
            $receipt_arr['t'] = $correct_date;

            // Ставим флаг что чек введён вручную
            $receipt_arr['manual_type'] = true;

            // Проверяем и сохраняем
            $answer = $this->do_save($receipt_arr);

        } else{
             $answer['title'] = 'Ошибка: Это не POST запрос';
             $answer['error'] = true;
        }

        $status_color = $answer['error']? 'alert-danger' : 'alert-success';
        $tpl = set($tpl, 'alert_status', $status_color);
        $tpl = set($tpl, 'status_msg', $answer['title']);

        $this->render($tpl, []);

        return $answer;
    }


    // Сохраняем бумажный чек к БД
    function do_save($receipt_arr){

        $table = new table('receipt');

        /* ОБНОВЛЯЕМ ИМЕЮЩИЙСЯ ЧЕК */
        if( $id = $receipt_arr['receipt_id'] ){
            unset($receipt_arr['receipt_id']);      // Убираем id

            // Если изменяется ключевая информация, то сбрасываем ticket_id и json data
            $receiptObj = $table->byId(intval($id));
            foreach ($receipt_arr as $k => $v) {
                if ($k == 't') {
                    $v = date_timestamp($receipt_arr["t"], "iso8601");
                }
                if ($k == 'createtime') {
                    $v = time();
                }
                $receiptObj->set($k, $v);
            }

            $receiptObj->set('ticket_id', '');     // Удаляем, чтобы заного запросить данные из налоговой
            $receiptObj->set('json_data', '');     // Удаляем, чтобы заного запросить данные из налоговой

            $receiptObj->save();

            $answer['title'] = 'Чек успешно обновлён, данные по чеку из налоговой необходимо будет запросить заного';
        } else{
            /* ВНОСИМ НОВЫЙ ЧЕК */

            // Проверяем что чек кассовый
            $barcode_type = get_type_barcode($receipt_arr);

            if($barcode_type == 'receipt'){

                // Проверка на дубль в БД
                $receiptObj = $table->_DB_->q('SELECT * FROM `receipt` 
                  WHERE `t` = "'.date_timestamp($receipt_arr["t"], "iso8601").'" AND `s` = "'.$receipt_arr["s"].'" AND `fn` = "'.$receipt_arr["fn"].'" AND `i` = "'.$receipt_arr["i"].'" AND `fp` = "'.$receipt_arr["fp"].'" AND `n` = "'.$receipt_arr["n"].'"');

                if( !$row = $receiptObj->fetch_assoc()){
                    $receiptObj = $table->byId(0);
                    $receiptObj->set('t', date_timestamp($receipt_arr["t"],"iso8601") );
                    $receiptObj->set('s', $receipt_arr["s"] );
                    $receiptObj->set('fn', $receipt_arr["fn"] );
                    $receiptObj->set('i', $receipt_arr["i"] );
                    $receiptObj->set('fp', $receipt_arr["fp"] );
                    $receiptObj->set('n', $receipt_arr["n"] );
                    $receiptObj->set('createtime', time() );
                    $receiptObj->set('comment', $receipt_arr["comment"] );
                    $receiptObj->set('manual_type', $receipt_arr["manual_type"] );

                    $receiptObj->save();

                    $answer['title'] = 'Чек успешно добавлен';
                } else{
                    $answer['title'] = 'Этот чек уже есть';
                    $answer['error'] = true;
                }

            } else{
                $answer['title'] = 'Данный чек не кассовый, его формат определен как: ' . $barcode_type;
                $answer['error'] = true;
            }
        }



        return $answer;
    }



      // Обрабатываем внешнее запросы
    function _act_extApi(){

        //        require_once(_MODULES_ . '/api/nalog.api.php');
//        $nalog = new nalogApi();

//        $nalog->register();
//
//        pre('merrrrrrsy');


        global $Db;
        pr('Страница на месте');

        pr($this->params);

//        $inputJSON = file_get_contents('php://input');

        $res = json_decode(file_get_contents('php://input'), true);
//        print_r($data);
//        print_r($inputJSON);
        print_r($_POST);
//
//        if($_POST['qweqwe']){
//            _redirect('/mew');
//        }

//        $input= json_decode( $inputJSON, TRUE );
//
//        print_r(json_encode($input));

//        if($_SERVER["REQUEST_METHOD"]=="POST"){
//
//        }

        $data = $_POST['username'];

//        $res = $Db->query('UPDATE `users` SET `fio` = "'.$data.'" WHERE `id` = 3');
        $res = $this->Db->query('SELECT * FROM `users`');
        while($row = $res->fetch_assoc()){
            $rows[] = $row;
        }
        pr($rows);
    }

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

    // Генерируем QR строку по id
    function qrStrByID($receipt_id){
        // Получаем данные
        $receiptObj = $this->table->byId($receipt_id);
        $data = $receiptObj->get_vars_values();

        // Генерируем массив с данными
        $qr_arr['t'] = date_iso8601($data['t'], 'ts');
        $qr_arr['s'] = $data['s'];
        $qr_arr['fn'] = $data['fn'];
        $qr_arr['i'] = $data['i'];
        $qr_arr['fp'] = $data['fp'];
        $qr_arr['n'] = $data['n'];

        // Трансформируем в GET строку
        $qr_str = http_build_query($qr_arr);

        return $qr_str;
    }


    // Чек подтверждён вручную
    function _act_receipt_manual_confirm($id = ''){
        $receipt_id = '';
        if($id){
            $receipt_id = intval($id);
        } elseif($_GET['receipt_id']){
            $receipt_id = intval($_GET['receipt_id']);
        }
        if( !$receipt_id ) die('Не хватает необходимых данных для запроса');

        // Формируем строку с данными по чеку
        $receiptObj = $this->table->byId($receipt_id);
        $receiptObj->set('error', 0);
        $receiptObj->set('manual_confirm', 1);
        $receiptObj->save();

        _redirect('receipt?#r'.$receipt_id);
    }
}