<?php


class _moneyFlow_parent extends parentTemplate {
    var $params = [];                   // Статусы при редиректе

    var $id_account_from;               // id счёта списания
    var $id_account_to;                 // id счёта зачисления
    var $id_type = 0;                   // id тип операции
    var $entity_id = 0;                 // id Юр лица
    var $debt_acc_id;                   // id кредитора

    var $type_ru;                       // заголовок операции ru
    var $type_en;                       // заголовок операции en
    var $uncover_type = false;          // не раскрывать select при редактировании

    var $block_account_from = false;    // блок "Счет списания"
    var $block_account_to = false;      // блок "Счет зачисления"
    var $block_type = false;            // блок "Тип операции"
    var $block_entity = false;          // блок "Юр лицо(вручную)"
    var $block_receipt = false;         // блок "Кассовый чек"
    var $block_debt = false;            // блок "Взять/вернуть долг" (с выбором кредитора)
    var $chb_manual_confirm = false;    // чекбокс "Подтвердить вручную"

    var $str_month_summ = false;        // строка с суммой за месяц
    var $str_day_summ = false;          // строка с суммой за день
    var $frame_info = ['create'    => 'Запись создана',
                       'update'    => 'Запись обновлена',
                       'duplicate' => 'Запись не добавлена - найден дубликат',
                       'delete'    => 'Запись успешно удалена'];


    function __construct($params)    {
        parent::__construct($params);
        if(USE_DB) $this->table = new table('money_flow');
    }


    /**
     * Вывод всех операций
     */
    function _act_(){
        $tpl = get_template('', '_moneyFlow_parent', 'body');
        $tpldr = get_template('', '_moneyFlow_parent', 'day_row');
        $tplpr = get_template('', '_moneyFlow_parent', 'flow_row');

        $tpl = set($tpl, 'type_ru', $this->type_ru );
        $tpl = set($tpl, 'type_en', $this->type_en );
        $tpl = set($tpl, 'type_ru_title', ucfirst_utf8($this->type_ru) );

        $accounts_salary = ACCOUNTS_SALARY;   // Зарплатные счета 1 - наличные, 2 - Карта СПБ(зарплатная)

        $typeObj = new table('money_type');
        $accObj = new table('money_accounts');
        $entityObj = new table('entity');


        // Кнопка сканировать qr чека
        if($this->type_en != 'out') $tpl = set($tpl, 'hide_add_qr', 'hide');


        // Если был редирект с главной для просмотра долгов
        if($_GET['acc1'] && $_GET['acc2'] && $this->type_en == 'transfer'){
            $sql = 'SELECT FROM_UNIXTIME(`t1`.`t`, "%Y%m%d") AS `flow_date`, `t1`.`t` as "flow_date_ts"
                    FROM `money_flow` AS `t1`
                    LEFT JOIN `money_flow` AS `t2` ON `t1`.`linked_id` = `t2`.`id`
                    WHERE `t1`.`status_id` = 6
                    AND `t1`.`is_debt`
                    AND `t1`.`money_account_id` IN("'.$_GET['acc1'].'","'.$_GET['acc2'].'") AND `t2`.`money_account_id` IN("'.$_GET['acc1'].'","'.$_GET['acc2'].'") AND `t1`.`s` > 0
                    GROUP BY FROM_UNIXTIME(`t1`.`t`, "%Y%m%d") ORDER BY flow_date DESC
            ';

            $tpl = set($tpl, 'hide_edit_btn', 'hide' );
            $tpl = set($tpl, 'hide_month_summ', 'hide' );
            $tpl = set($tpl, 'hide_paginator', 'hide' );

        } else {
            // Иначе просто отображаем операции выбранного типа

            // Условия сравнения в зависимости от типа операции
            if (in_array($this->type_en, ['in', 'out'])) {
                $filter_salary_account_linked = ' AND IF((SELECT `linked`.`money_account_id` FROM `money_flow` as `linked` WHERE `linked`.`id` = `money_flow`.`linked_id`) IN(' . implode(',', $accounts_salary) . '),1, 0) = 0 ';

                if ($this->type_en == 'in') {
                    $compare = ' AND `s` >= 0  AND `status_id` != 6 AND `money_account_id` IN(' . implode(',', $accounts_salary) . ') ';
                }

                if ($this->type_en == 'out') {
                    $compare = ' AND `s` < 0  AND `status_id` != 6 AND `money_account_id` IN(' . implode(',', $accounts_salary) . ') ';
                }
            } elseif (in_array($this->type_en, ['transfer'])) {
                $compare = ' AND `status_id` = 6 AND `s` > 0';
            }
            
            // Ищем период
            $db = $this->table->getSSelectF('`t` as `ts`', $compare . ' ORDER BY `t` ASC LIMIT 1');
            $from_time = $db->fetch_assoc();
            $period_map = getPeriod($from_time['ts'], time(), 'P1M', 'm.Y', 'desc');


            // Пагинатор в шапке (по месяцам)
            $page_str = $_GET['page'] ?: date('m.Y');
            foreach($period_map as $period){
                $selected = '';
                if ($period == $page_str) {
                    $selected = 'selected';
                }

                $tpl = setm($tpl, 'page_options', '<option value="' . $period . '" ' . $selected . '>' . $period . '</option>');
            }


            // Показываем на страницы записи только за выбранный месяц
            $page_arr = explode('.', $page_str);
            list($f_month, $f_year) = $page_arr;
            $first_day = mktime(0, 0, 0, $f_month, 1, $f_year);
            $count_day = date("t", $first_day);
            $last_day = mktime(23, 59, 59, $f_month, $count_day, $f_year);


            // Вывод суммы за месяц
            if($this->str_month_summ){
                $month_sum = $this->table->getSum('s', $compare . ' AND (`t` > ' . $first_day . ' AND `t` < ' . $last_day . ') ' . $filter_salary_account_linked);
                $tpl = setm($tpl, 'month_sum', intval($month_sum));
            } else{
                $tpl = set($tpl, 'hide_month_summ', 'hide' );
            }

            // Запрос
            $sql = 'SELECT FROM_UNIXTIME(`t`, "%Y%m%d") AS `flow_date`, `t` as `flow_date_ts`, COUNT(*) AS "cnt", SUM(s) as "day_summ" FROM `money_flow`
                    WHERE 1 ' .$compare. ' AND (`t` > ' .$first_day. ' AND `t` < ' .$last_day. ') ' .$filter_salary_account_linked. '
                    GROUP BY FROM_UNIXTIME(`t`, "%Y%m%d") ORDER BY `flow_date` DESC';
        }


        // Выводим операции
        $cnt = 0;
        $db = $this->table->_DB_->q($sql);
        while($row = $db->fetch_assoc()){

            /* ЗАПОЛНЯЕМ ДАТЫ */
            $flow_date = $row['flow_date'];
            $tt = $tpldr;
            $tt = set($tt, 'title_date', date('d.m.Y', $row['flow_date_ts']));
            $tt = set($tt, 'flow_date', $flow_date);
            $tt = set($tt, 'cnt', $row['cnt']);

            // Вывод суммы за день
            if($this->str_day_summ){
                $tt = set($tt, 'day_summ', mf($row['day_summ'], 'is_penny'));
            } else{
                $tt = set($tt, 'hide_day_summ', 'hide' );
            }

            // День недели
            $num_week = date('w', $row['flow_date_ts']);
            $week = day_of_week($num_week, 'ru_short');
            $tt = set($tt, 'week', $week);


            /* ИЩЕМ ОПЕРАЦИИ ПО ТЕКУЩЕМУ ДНЮ */
            // Если был редирект с главной для просмотра долгов
            if($_GET['acc1'] && $_GET['acc2'] && $this->type_en == 'transfer'){
                $sql = 'SELECT `t1`.*
                    FROM `money_flow` AS `t1`
                    LEFT JOIN `money_flow` AS `t2` ON `t1`.`linked_id` = `t2`.`id`
                    WHERE `t1`.`status_id` = 6
                    AND `t1`.`is_debt`
                    AND `t1`.`money_account_id` IN("'.$_GET['acc1'].'","'.$_GET['acc2'].'") AND `t2`.`money_account_id` IN("'.$_GET['acc1'].'","'.$_GET['acc2'].'") AND `t1`.`s` > 0
                    AND FROM_UNIXTIME(`t1`.`t`, "%Y%m%d") = "'.$flow_date.'"
                    ORDER BY `t1`.`t` DESC
                ';
            } else{
                $sql = $this->table->getSelect($compare .' AND FROM_UNIXTIME(`t`, "%Y%m%d") = "'.$flow_date.'"'.' ORDER BY `t` DESC');
            }

            $db2 = $this->table->_DB_->q($sql);
            while($row2 = $db2->fetch_assoc() ){
                $tt2 = $tplpr;
                $tt2 = set($tt2, 'title_date', date('H:i', $row2['t']));
                $tt2 = set($tt2, 'flow_summa', mf($row2['s'], 'is_penny'));

                // Определяем статус
                $update_status_link = '<a href="/money_'.$this->type_en.'?act=get_receipt_from_nalog&id='.$row2["id"].'" class="btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/sync_logo1.png" alt=""></a>';
                $download_receipt = '<a href="/money_'.$this->type_en.'?act=view_receipt&id='.$row2["id"].'" class="modal_custom btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/receipt_icon.png" alt=""></a>';
                $accept_manual = '<img class="btn p-0 d-inline-block" style="width: 40px; cursor: default" src="'._IMG_.'/_static/accept_manual.png" alt="">';
                $no_receipt_data = '<img class="btn p-0 d-inline-block" style="width: 40px; cursor: default" src="'._IMG_.'/_static/no_receipt_icon.png" alt="">';

                $table_st = new table('money_flow_status');
                $statusObj = $table_st->byId($row2['status_id']);

                switch($statusObj->id){
                    case '1':   // новый
                        $status = 'Статус: '.$statusObj->title;
                        $color_status = 'bg-danger';

                        // Если указаны данные по чеку
                        if($row2['fn'] && $row2['fp'] && $row2['i'] && $row2['n']){
                            $action_btn = $update_status_link;
                        } else{
                            $action_btn = $no_receipt_data;
                        }
                        break;
                    case '2':   // идёт формирование
                        $status = 'Статус: '.$statusObj->title;
                        $color_status = 'bg-warning';
                        $action_btn = $update_status_link;
                        break;
                    case '3':   // данные сформированы
                        $receipt_data = json_decode($row2['json_data'], true);
                        $seller = $receipt_data['document']['money_out']['user'];
                        $status = '';
                        $color_status = 'bg-success';
                        $action_btn = $download_receipt;
                        break;
                    case '4':   // данные некорректны
                        $status = 'Статус: '.$statusObj->title.' (id='.$row2["id"].')';
                        $color_status = 'bg-dark';
                        $action_btn = $update_status_link;
                        break;
                    case '5':   // подтверждён вручную
                        $status = '';
                        $color_status = 'bg-success';
                        $action_btn = $accept_manual;
                        break;
                    case '6':   // Перевод
                        $status = '';
                        $color_status = 'bg-primary';
                        $action_btn = $accept_manual;
                        break;
                    default:
                        $status = '??? Статус: не определён';
                        $color_status = 'bg-dark border-dark border-2';
                }

                $tt2 = set($tt2, 'bg_color', $color_status);                            // Задаём цвет фона
                $tt2 = set($tt2, 'action_btn', $action_btn);                            // Задаём кнопку статуса
                $tt2 = set($tt2, 'status', $status);                                    // Задаём статус
                $tt2 = set($tt2, 'id', $row2['id']);                                    // id в БД
                $tt2 = set($tt2, 'style_div_btns', 'justify-content-center');     // выравниваем по центру
                if($row2['is_debt']) $tt2 = set($tt2, 'bg_dept_color', 'bg-danger');    // операция с долгом


                // Определяем тип операции
                $type_arr = $typeObj->getTitles($row2['money_type_id']);
                $type_title = array_shift($type_arr);

                // Если тип - перевод, определяем направление
                $add_info = '';
                if($row2['linked_id']){
                    $flowObj = $this->table->byId($row2['linked_id']);
                    if( in_array($flowObj->money_account_id, $accounts_salary)) $tt2 = set($tt2, 'bg_dept_color', 'bg-secondary');     // перевод между зарплатными картами

                    if($row2['op_sign'] < 0){
                        $id_acc_from = $row2['money_account_id'];
                        $id_acc_to = $flowObj->money_account_id;
                    } else{
                        $id_acc_from = $flowObj->money_account_id;
                        $id_acc_to = $row2['money_account_id'];
                    }

                    $arr_acc_from = $accObj->getTitles($id_acc_from);
                    $title_acc_from = array_shift($arr_acc_from);

                    $arr_acc_to = $accObj->getTitles($id_acc_to);
                    $title_acc_to = array_shift($arr_acc_to);

                    $add_info = ' <span style="white-space: nowrap; padding-left: 0">"'.$title_acc_from.'" -> "'.$title_acc_to.'" </span>';
                }
                $tt2 = set($tt2, 'type_title', $type_title . $add_info);

                // Лого юр лица
                $fileObj = new table('_files');
                $img_scr = _IMG_.'/entity/no_entity.png';

                if($row2['entity_id']){
                    $entityObj->byId($row2['entity_id']);
                    if($entityObj->file_id){
                        $fileObj->byId(intval($entityObj->file_id));
                        $img_scr = _STATIC_URL_.'/'.$fileObj->url;
                    }
                }

                $entity_logo = '<img style="width: 40px;" src="'.$img_scr.'" alt="">';
                $tt2 = set($tt2, 'entity_logo', $entity_logo);


                // Комментарий
                if($row2['comment']){
                    $tt2 = set($tt2, 'display_comment', 'display: block');
                    $tt2 = set($tt2, 'comment', $row2['comment']);
                } else{
                    $tt2 = set($tt2, 'display_comment', 'display: none');
                }

                // Счёт
                $acc_arr = $accObj->getTitles($row2['money_account_id']);
                $acc_title = array_shift($acc_arr);
                $tt2 = set($tt2, 'account_title', $acc_title);


                /* id какой записи редактировать */
                $edit_link = get_edit_link($row2['id'], $this->type_en);
                $tt2 = set($tt2, 'edit_link', $edit_link);


                $tt = setm($tt, 'profit_rows', $tt2);
            }



            $tpl = setm($tpl, 'day_rows', $tt);

            $cnt++;
        }


        if(!$cnt){
            $tpl = set($tpl, 'day_rows', '<div class="alert alert-warning row">Записей не найдено</div>');
        }


        $this->render($tpl, []);
    }


    /**
     * Форма добавления/редактирования записи
     */
    function _act_edit(){
        global $auth;
        $tpl = get_template('', '_moneyFlow_parent', 'edit_form');

        $uncover_select = [];                                                           // Не раскрывать select-ы с одной записью
        if($this->uncover_type) $uncover_select[] = 'money_type_id';                    // Скрываем тип


        // Заголовок операции
        $tpl = set($tpl, 'type_ru', $this->type_ru );
        $tpl = set($tpl, 'type_en', $this->type_en );
        $tpl = set($tpl, 'type_ru_title', ucfirst_utf8($this->type_ru) );


        // При редактировании записи
        if($_GET['id']){
            $flow_id = intval($_GET['id']);
            $flowObj = $this->table->byId($flow_id);
            $data = $flowObj->get_vars_values();

            // Находим id счета списания и зачисления
            switch($this->type_en){
                case 'transfer':
                    $this->id_account_from = $data['money_account_id'];

                    $linkedObj = $this->table->byId( $data['linked_id'] );
                    $this->id_account_to = $linkedObj->money_account_id;
                    break;
                case 'in':
                    $this->id_account_to = $data['money_account_id'];
                    break;
                case 'to':
                    $this->id_account_from = $data['money_account_id'];
                    break;
            }

            $this->id_type = $data['money_type_id'];                                   // id тип операции
            $this->entity_id = $data['entity_id'];                                     // id Юр лица

            // Заполяем поля данными
            foreach($data as $k => $v){
                if($k == 't'){
                    $v = date('Y-m-d\TH:i', $v);                                // Переводим дату из 20211113T122600 в формат 2021-11-03T15:57
                }
                if($k == 's'){
                    $v = abs($v);                                                       // Переводим дату из 20211113T122600 в формат 2021-11-03T15:57
                }
                if($k == 'is_debt' && $v == 1){
                    $k = 'debt_checked';
                    $v = 'checked';
                }

                $tpl = set($tpl, $k, $v);
            }

            // Отображаем поля с чеком, если есть по нему данные
            if($data['fn'] || $data['i'] || $data['fp']){
                $tpl = set($tpl, 'checked_receipt', 'checked');
            }

            // Отображаем поля с долгом, если есть по нему данные
            $flowObj = $this->table->_DB_->q($this->table->getSelect(' AND `debt_flow_id` = "'.$flow_id.'"'));
            while($row = $flowObj->fetch_assoc()){
                if($row['s'] < 0){
                    $tpl = set($tpl, 'debt_checked', 'checked');
                    $this->debt_acc_id = $row['money_account_id'];
                    $tpl = set($tpl, 'debt_summa', abs($row['s']));
                    $tpl = set($tpl, 'debt_comment', $row['comment']);
                }
            }

            // Подтвердить вручную не отображаем для статусов "данные сформированы" и "подтверждён вручную"
            if(in_array($data['status_id'], [5])){
                $tpl = set($tpl, 'manual_confirm_checked', 'checked');
            }

            $tpl = set($tpl, 'id', $flow_id);                                   // Кнопка удаления записи
        } else{
            $tpl = set($tpl, 'hide_del_btn', 'hide');
        }


        // Счёт списания
        if($this->block_account_from){
            $filter_from = ($this->id_account_from && $this->type_en=='transfer')? ' AND `id` = ' . $this->id_account_from : '';

            $table_acc = new table('money_accounts');
            $moneyAccObj = $table_acc->_DB_->q($table_acc->getSelect($filter_from));
            while($row = $moneyAccObj->fetch_assoc()) {
                $selected = '';
                if($row['id'] == $this->id_account_from){
                    $selected = 'selected';
                }

                // Делаем select серым, если счет всего один
                if($moneyAccObj->num_rows == 1) $uncover_select[] = 'money_account_from';

                // Отображаем баланс на счете
                $balance = '';
                if(in_array($row['id'], $auth->moneyAccount)){
                    $balance = balance_account($row['id']);
                }

                $tpl = setm($tpl, 'account_options_from', '<option data-balance="'.$balance.'" value="'.$row['id'].'" '.$selected.'>'.$row['title'].'</option>');
            }
        } else{
            $tpl = set($tpl, 'hide_account_from', 'hide' );
            $tpl = set($tpl, 'disabled_account_from', 'disabled' );
        }


        // Счёт зачисления
        if($this->block_account_to){
            $filter_to = ($this->id_account_to && $this->type_en=='transfer')? ' AND `id` = ' . $this->id_account_to : '';

            $table_acc = new table('money_accounts');
            $moneyAccObj = $table_acc->_DB_->q($table_acc->getSelect($filter_to));
            while($row = $moneyAccObj->fetch_assoc()) {
                $selected = '';
                if($row['id'] == $this->id_account_to){
                    $selected = 'selected';
                }

                // Делаем select серым, если счет всего один
                if($moneyAccObj->num_rows == 1) $uncover_select[] = 'money_account_to';

                // Отображаем баланс на счете
                $balance = '';
                if(in_array($row['id'], $auth->moneyAccount)){
                    $balance = balance_account($row['id']);
                }

                $tpl = setm($tpl, 'account_options_to', '<option data-balance="'.$balance.'" value="'.$row['id'].'" '.$selected.'>'.$row['title'].'</option>');
            }
        } else{
            $tpl = set($tpl, 'hide_account_to', 'hide' );
            $tpl = set($tpl, 'disabled_account_to', 'disabled' );
        }


        // Тип операции
        if($this->block_type){
            $filter_type = ($this->id_type && $this->type_en=='transfer')? ' AND `id` = ' . $this->id_type : '';

            $table = new table('money_type');
            $cat_obj = $table->_DB_->q($table->getSelect($filter_type . ' AND `direct` = "'.$this->type_en.'"'));
            while($row = $cat_obj->fetch_assoc()){
                $arrs[] = $row;
            }

            $type_options = $this->build_tree($arrs);
            $tpl = set($tpl, 'type_options', implode('',$type_options) );
        } else{
            $tpl = set($tpl, 'hide_type', 'hide' );
        }


        // Юр лицо
        if($this->block_entity){
            $entity_arr = [];
            $entityObj = new table('entity');
            $fileObj = new table('_files');

            $db = $entityObj->getSSelect();
            while($row = $db->fetch_assoc()){
                $correct_title = $row['title_short']?: $row['title'];
                $entity_arr[$row['id']] = ['title' => htmlspecialchars($correct_title), 'file_id' => $row['file_id']];
            }

            // Формируем <option> из юриков
            foreach($entity_arr as $id => $info){
                if($info['file_id']){
                    $fileObj->byId(intval($info['file_id']));
                    $img_scr = $fileObj->url;
                } else{
                    $img_scr = _IMG_.'/entity/no_entity.png';
                }

                $selected = $this->entity_id == $id ? 'selected' : '';
                $tpl = setm($tpl, 'entity_options', '<option class="overflow-hidden" data-content="<img style=\'width: 25px;\' src=\''.$img_scr.'\'></img> '.$info["title"].'" value="'.$id.'"'.$selected.'>'.$info["title"].'</option>');
            }
        } else{
            $tpl = set($tpl, 'hide_entity', 'hide' );
        }

        // Чекбокс "Кассовый чек"
        if(!$this->block_receipt) $tpl = set($tpl, 'hide_receipt', 'hide' );

        // Чекбокс "Взять/вернуть долг"
        if($this->block_debt){
            $table_acc = new table('money_accounts');
            $moneyAccObj = $table_acc->_DB_->q($table_acc->getSelect(' AND `ro`'));
            while($row = $moneyAccObj->fetch_assoc()) {
                $selected = $this->debt_acc_id == $row['id'] ? 'selected' : '';
                $tpl = setm($tpl, 'ro_account_options', '<option value="'.$row['id'].'"'.$selected.'>'.$row['title'].'</option>');
            }
        } else{
            $tpl = set($tpl, 'hide_debt', 'hide' );
        }

        // Чекбокс "Подтвердить вручную"
        if(!$this->chb_manual_confirm) $tpl = set($tpl, 'hide_manual_confirm', 'hide' );

        // Помечаем серым отмеченные select
        $tpl = set($tpl, 'uncover_select', json_encode($uncover_select) );


        // Если есть POST данные, значит нужно заполнить поля этими данными(напр. в расходах эти данные поступают из распознанного qr кода на чеке)
        if($_POST){
            foreach($_POST as $k => $v){
                if($k == 't'){
                    $ts = date_timestamp($v,"iso8601");     // Переводим вначале в timestamp
                    $v = date('Y-m-d\TH:i', $ts);               // Затем в понятный для input формат 2021-11-03T15:57
                }
                $tpl = set($tpl, $k, $v);
            }

            if($this->type_en == 'out') $tpl = set($tpl, 'checked_receipt', 'checked');     // для расходов отображаем поля с чеком
        }


        $this->render($tpl, []);
    }


    /**
     * Удалить операцию из money flow
     */
    function _act_delete_flow($id = '', $redirect = ''){

        // Находим id
        $flow_id = '';
        if($id){
            $flow_id = intval($id);
        } elseif($_GET['id']){
            $flow_id = intval($_GET['id']);
        }
        if( !$flow_id ) die('Нет flow_id, который необходим для удаления записи');

        // находим тип операции
        if($_GET['flow_type']) $redirect = $_GET['flow_type'];

        // удаляем запись
        $table = new table('money_flow');
        $flowObj = $table->byId(intval($flow_id));
        $linked_id = $flowObj->linked_id;
        $flowObj->del();

        // связанная запись
        if($linked_id){
            $linkedObj = $table->byId($linked_id);
            $linkedObj->del();
        }

        flash::add_toast('Money',$this->frame_info['delete'], 2, 'info');
        _redirect('/'.$redirect);
    }


    /**
     * Формируем селект для дерева
     */
    function build_tree($arrs, $parent_id = 0, $level = 0, & $data = [], & $i = 0) {

        foreach ($arrs as $arr) {

            // Для операций Перевод
            if($this->type_en == 'transfer'){
                if ($arr['id'] == $this->id_type) {
                    $selected = 'selected';
                    $option = '<option value="' . $arr['id'] . '" ' . $selected . '>' . str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $level) . str_repeat("-", $level) . " " . $arr['title'] . "</option>";
                    $data[$i++] = $option;
                }
            }

            // Для операций Доход/Расход
            else{
                if ($arr['parent_id'] == $parent_id) {
                    $selected = '';
                    if ($arr['id'] == $this->id_type) {
                        $selected = 'selected';
                    }

                    $disabled = ($level)? '' : 'disabled';

                    $option = '<option value="' . $arr['id'] . '" ' . $selected . ' '.$disabled.'>' . str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", $level) . str_repeat("-", $level) . " " . $arr['title'] . "</option>";
                    $data[$i++] = $option;
                    $this->build_tree($arrs, $arr['id'], $level + 1, $data, $i);
                }
            }
        }

        return $data;
    }


    // Сохраняем
    function do_save($data, $sign = 1){
        $data['op_sign'] = $sign;
        $result['id'] = 0;

        // Переводим дату в ts
        preg_match('~\d{4}-\d{2}-\d{2}T\d{2}:\d{2}~', $data['t'], $match);
        if($match[0]){
            $data['t'] = date_timestamp($data['t'], 'iso8601');
        }

        // Переводим в сумму в сооветсвии со знаком
        if(!empty($data["s"]))  $data["s"] = abs($data["s"]) * $data['op_sign'];


        // Ищем id записи
        if($data['id']){
            $flow_id = intval($data['id']);
            $result['status'] = 'update';
        } else{
            $flowObj = $this->table->_DB_->q($this->table->getSelect(' AND `t` = "'.$data['t'].'" AND `s` = "'.$data["s"].'"'));
            if( $row = $flowObj->fetch_assoc()){
                $flow_id = $row['id'];
                $result['status'] = 'duplicate';
            } else{
                $flow_id = 0;
                $result['status'] = 'create';
                $data['status_id'] = 1;
                $data['createtime'] = time();
            }
        }

        // Статус: подтверждён вручную
        if ($data["manual_confirm"]) $data['status_id'] = 5;

        // Статус: перевод
        if ($data['type_en'] == "transfer") $data['status_id'] = 6;
        unset($data["type_transfer"]);


        // Сохраняем в БД
        if($result['status'] != 'duplicate') {
            $flowObj = $this->table->byId($flow_id);

            // Если сняли галку с подтвержденного ручную, то помечаем операцию как новую
            if($flowObj->status_id == 5 && $data['manual_confirm'] == 0){
                $flowObj->set('status_id', 1);
            }

            // Если сняли галку с долга, то долг удаляем
            if($flowObj->is_debt == 1 && $data['is_debt'] == 0){
                $flowObj2 = $this->table->getSSelect(' AND `debt_flow_id` = "'.$flow_id.'"');
                while($row2 = $flowObj2->fetch_assoc()){
                    $this->table->byID($row2['id']);
                    $this->table->del();
                }
            }

            foreach($data as $k => $v){
                $flowObj->set($k, $v);
            }


            // Сохраняем
            $flowObj->save();

            $result['id'] = $flowObj->id;
            $result['t'] = $flowObj->t;
        }


        return $result;
    }

}