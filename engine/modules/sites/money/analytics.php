<?php
/**
 * Графики по расходам
 * User: Andrew
 * Date: 14.06.2022
 * Time: 1:39
 */

class analytics extends _moneyFlow_parent {

    public function _act_(){
        $tpl = get_template('', $this->module, 'body');

        // Добавляем в select года
        $first = true;
        $startYearTs = mktime(0,0,0, 1,1,2021);
        $years = getPeriod($startYearTs, time(), 'P1Y', 'Y');
        foreach($years as $year){
            $selected = '';
            if($first){
                $selected = 'selected';
                $first = false;
            }
            $option = '<option value="'.$year.'" '.$selected.'>'.$year.'</option>';
            $tpl = setm($tpl, 'year_options', $option);
        }

        $tpl = set($tpl, 'cur_year', date('Y'));
        $tpl = set($tpl, 'cur_month', date('m'));


        $this->render($tpl);
    }



    /**
     * Получаем данные для графика пирога
     * return json
     */
    public function _ajax_getDataPie(){
        // Фильтр: Операция (расход/доход)
        $flow_direct = $_POST['_f']['flow_type'];

        // Фильтр: Год (c 2021 по текущий)
        $year_filter = $_POST['_f']['year'];
        $last_year = mktime(0,0,0,1,1,2021);
        $years = getPeriod($last_year, time(), 'P1Y','Y');

        // Фильтр: Месяцы
        $month_filter = $_POST['_f']['month'];


        // Условия запроса
        $where_query = ' AND `money_account_id` IN ('.implode(",", ACCOUNTS_SALARY).')';
        $where_query .= ($flow_direct == 'out')? ' AND `s` < 0' : ' AND `s` > 0';


        // Показываем данные по выбранному месяцу (1 месяц)
        if(is_numeric($year_filter) && is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y%m") = "'.$year_filter.$month_filter.'"';
        }


        // Показываем данные по выбранному году (12 месяцев)
        if(is_numeric($year_filter) && !is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y") = "'.$year_filter.'"';
        }


        // Показываем данные по всем годам (все месяцы)
        if(!is_numeric($year_filter) && !is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y") IN ("'.implode('","', $years).'")';
        }

        // Формируем шаблоны
        $empty_tpl = [];
        $mTypeObj = new table('money_type');
        $db = $mTypeObj->getSSelect(' AND `parent_id` > 0 AND `direct` = "'.$flow_direct.'" ORDER BY `sort` ASC');
        while($row = $db->fetch_assoc()){
            $empty_tpl[$row['id']][0] = $row['title'];
            $empty_tpl[$row['id']][1] = 0;
            $empty_tpl[$row['id']][2] = $row['id'];
        }

        $gchart_data = [];
        $sql = 'SELECT FROM_UNIXTIME(`t`, "%m.%Y") as `fdate`, `money_type_id`, `title`, `is_debt`, SUM(`s`) as `type_sum` FROM `money_flow` 
                                          LEFT JOIN `money_type` ON `money_type`.`id` = `money_flow`.`money_type_id`
                                          WHERE 1 AND `money_account_id` IN ('.implode(",",ACCOUNTS_SALARY).') AND `parent_id` > 0 
                                          '.$where_query.' GROUP BY `fdate`, `money_type_id`, `is_debt`
        ';

        $flowObj = $this->table->_DB_->q($sql);
        while($row = $flowObj->fetch_assoc()){
            list($month, $year) = explode('.', $row['fdate']);

            // тип Переводы
            if($row['money_type_id'] == 6){
                // Всё что брали в долг - учитываем, обычные переводы - нет
                if($row['is_debt']){
                    if($flow_direct == 'in') $row['title'] = 'Взяли в долг';
                    if($flow_direct == 'out') $row['title'] = 'Вернули долг';
                } else continue;
            }

            /*=== ДАННЫЕ ===*/
            if (empty($gchart_data[$year][$month])) {
                $gchart_data[$year][$month] = $empty_tpl;

                if($flow_direct == 'in') $title = 'Взяли в долг';
                if($flow_direct == 'out') $title = 'Вернули долг';

                $gchart_data[$year][$month][6][0] = $title;
                $gchart_data[$year][$month][6][1] = 0;
                $gchart_data[$year][$month][6][2] = 6;
            }

            $gchart_data[$year][$month][$row['money_type_id']][0] = $row['title'];
            $gchart_data[$year][$month][$row['money_type_id']][1] += (int)abs($row['type_sum']);
            $gchart_data[$year][$month][$row['money_type_id']][2] = $row['money_type_id'];
        }


        // Пересчитываем индексы в массиве для корректной работы Google chart
        $data_correct = [];


        // Корректировка для пирога
        foreach($gchart_data as $year => $months){
            foreach($months as $month => $data){
                $data_correct[$year][$month] = array_values($data);
            }
        }

        echo json_encode($data_correct, JSON_UNESCAPED_UNICODE);
        die();
    }


    /**
     * Получаем данные для линейного графика
     * return json
     */
    public function _ajax_getDataLinear(){
        // Фильтр: Тип (пирог/линейный)
        $value_type = $_POST['_f']['value_type'];

        // Фильтр: Операция (расход/доход)
        $flow_direct = $_POST['_f']['flow_type'];

        // Фильтр: Год (c 2021 по текущий)
        $year_filter = $_POST['_f']['year'];
        $last_year = mktime(0,0,0,1,1,2021);
        $years = getPeriod($last_year, time(), 'P1Y','Y');

        // Фильтр: Месяцы
        $month_filter = $_POST['_f']['month'];


        // Условия запроса
        $where_query = ' AND `money_account_id` IN ('.implode(",", ACCOUNTS_SALARY).')';
        $where_query .= ($flow_direct == 'out')? ' AND `s` < 0' : ' AND `s` > 0';


        // Показываем данные по выбранному месяцу (1 месяц)
        if(is_numeric($year_filter) && is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y%m") = "'.$year_filter.$month_filter.'"';
        }


        // Показываем данные по выбранному году (12 месяцев)
        if(is_numeric($year_filter) && !is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y") = "'.$year_filter.'"';
        }


        // Показываем данные по всем годам (все месяцы)
        if(!is_numeric($year_filter) && !is_numeric($month_filter)){
            $where_query .= ' AND FROM_UNIXTIME(`t`, "%Y") IN ("'.implode('","', $years).'")';
        }


        // Находим самую раннюю дату
        $db = $this->table->getSSelectF('MIN(`t`) as `start_ts`', $where_query.' LIMIT 1');
        $row = $db->fetch_assoc();
        $from_ts = $row['start_ts'];


        // Находим самую позднюю дату
        if($year_filter == 'all' || $year_filter == date('Y')){
            $to_ts = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        } else{
            $to_ts = mktime(23, 59, 59, 12, 31, $year_filter);
        }


        // Формируем шаблоны
        $mTypeObj = new table('money_type');
        $empty_tpl = [];
        $period = getPeriod($from_ts, $to_ts, 'P1M', 'm.Y', $order_by = 'asc');
        foreach($period as $fdate){
            list($month, $year) = explode('.', $fdate);

            $db = $mTypeObj->getSSelect(' AND `parent_id` > 0 AND (`direct` = "'.$flow_direct.'" OR `id` = 6) ORDER BY `sort` ASC');
            while($row = $db->fetch_assoc()){
                $empty_tpl[$row['id']]['title'] = $row['title'];
                $empty_tpl[$row['id']]['data'][$month . '.' . $year][0] = $month;
                $empty_tpl[$row['id']]['data'][$month . '.' . $year][1] = $year;
                $empty_tpl[$row['id']]['data'][$month . '.' . $year][2] = 0;
                $empty_tpl[$row['id']]['data'][$month . '.' . $year][3] = $row['id'];
            }
        }


        // Цвета графиков
        $gchart_data = [];
        $colorPallette = ["#3366cc","#dc3912","#ff9900","#109618","#990099","#0099c6","#dd4477","#66aa00","#b82e2e","#316395","#3366cc","#994499","#22aa99","#aaaa11","#6633cc","#e67300","#8b0707","#651067","#329262","#5574a6","#3b3eac","#b77322","#16d620","#b91383","#f4359e","#9c5935","#a9c413","#2a778d","#668d1c","#bea413","#0c5922","#743411"];
        $i = 0;

        $sql = 'SELECT FROM_UNIXTIME(`t`, "%m.%Y") as `fdate`, `money_type_id`, `title`, `is_debt`, SUM(`s`) as `type_sum` FROM `money_flow` 
                                          LEFT JOIN `money_type` ON `money_type`.`id` = `money_flow`.`money_type_id`
                                          WHERE 1 AND `money_account_id` IN ('.implode(",",ACCOUNTS_SALARY).') AND `parent_id` > 0 
                                          '.$where_query.' GROUP BY `fdate`, `money_type_id`, `is_debt` ORDER BY `sort` ASC
        ';

        $flowObj = $this->table->_DB_->q($sql);
        while($row = $flowObj->fetch_assoc()){
            list($month, $year) = explode('.', $row['fdate']);

            // тип Переводы
            if($row['money_type_id'] == 6){
                // Всё что брали в долг - учитываем, обычные переводы - нет
                if($row['is_debt']){
                    if($flow_direct == 'in') $row['title'] = 'Взяли в долг';
                    if($flow_direct == 'out') $row['title'] = 'Вернули долг';
                } else continue;
            }


            /*=== ДАННЫЕ ===*/
            if (empty($gchart_data[$row['money_type_id']])) {
                $gchart_data[$row['money_type_id']] = $empty_tpl[$row['money_type_id']];

                $gchart_data[$row['money_type_id']]['color'] = $colorPallette[$i];
                $i++;
            }

            $gchart_data[$row['money_type_id']]['title'] = $row['title'];
            $gchart_data[$row['money_type_id']]['data'][$month . '.' . $year][0] = $month;
            $gchart_data[$row['money_type_id']]['data'][$month . '.' . $year][1] = $year;
            $gchart_data[$row['money_type_id']]['data'][$month . '.' . $year][2] += (int)abs($row['type_sum']);
            $gchart_data[$row['money_type_id']]['data'][$month . '.' . $year][3] = $row['money_type_id'];
        }


        // Пересчитываем индексы в массиве для корректной работы Google chart
        $data_correct = [];
        $i=0;
        foreach($gchart_data as $cat_id => $arr){
            $data_correct[$i]['data'] = array_values($arr['data']);
            $data_correct[$i]['color'] = $arr['color'];
            $data_correct[$i]['title'] = $arr['title'];
            $i++;
        }


        echo json_encode($data_correct, JSON_UNESCAPED_UNICODE);
        die();
    }


    /**
     * Отдаём данные за месяц по выбранной категории
     * return json
     */
    public function _ajax_getMonthTypeFlow(){
        $data = '';
        $tplfr = get_template('', '_moneyFlow_parent', 'flow_row');

        $accObj = new table('money_accounts');

        $year = $_POST['year'];
        $month = $_POST['month'];
        $type_id = (int) $_POST['type_id'];     // id категории операции
        $flow_direct = $_POST['flow_direct'];   // Операция расход/доход

        // Условия
        $where_query = '';
        $where_query .= ($flow_direct == 'out')? ' AND `money_flow`.`s` < 0' : ' AND `money_flow`.`s` > 0 ';
        $where_query .= ' AND `money_flow`.`money_account_id` IN ('.implode(",",ACCOUNTS_SALARY).') ';
        $where_query .= ' AND FROM_UNIXTIME(`money_flow`.`t`, "%Y%m") = "'.$year.$month.'" ';
        $where_query .= ' AND `money_flow`.`money_type_id` = "'.$type_id.'" ';

        // Если операции по переводам, смотрим только те, что были в долг
        if($type_id == 6){
            $where_query .= ' AND `money_flow`.`is_debt` = 1 ';
        }


        $days = [];     // Даты между опреациями

        $sql = 'SELECT *, `_files`.`url` AS `entity_url`, `money_flow`.`id` AS `flow_id`, `money_accounts`.`title` as `account_title`, `money_type`.`title` as `type_title`  FROM `money_flow` 
                LEFT JOIN `money_type` ON `money_type`.`id` = `money_flow`.`money_type_id` 
                LEFT JOIN `entity` ON `entity`.`id` = `money_flow`.`entity_id` 
                LEFT JOIN `_files` ON `_files`.`id` = `entity`.`file_id`                 
                LEFT JOIN `money_accounts` ON `money_accounts`.`id` = `money_flow`.`money_account_id` 
                WHERE 1 '.$where_query.' ORDER BY `t` DESC';
        $flowObj = $this->table->_DB_->q($sql);
        while($row = $flowObj->fetch_assoc()) {
            $tt = $tplfr;
            $tt = set($tt, 'title_date', date('H:i', $row['t']));
            $tt = set($tt, 'flow_summa', mf($row['s'], 'is_penny'));
            $tt = set($tt, 'display_edit_link', 'hide');
            $tt = set($tt, '_IMG_', _IMG_);
            $tt = set($tt, 'style_div_btns', 'justify-content-end');     // выравниваем по центру
            $tt = set($tt, 'id', $row['flow_id']);                                    // id в БД
            $tt = set($tt, 'account_title', $row['account_title']);

            // Лого юр лица
            $img_scr = _IMG_.'/entity/no_entity.png';

            if($row['entity_url']){
                $img_scr = _STATIC_URL_.'/'.$row['entity_url'];
            }
            $entity_logo = '<img style="width: 40px;" src="'.$img_scr.'" alt="">';
            $tt = set($tt, 'entity_logo', $entity_logo);

            $receipt_link = '/money_'.$row["direct"].'?act=view_receipt&id='.$row["flow_id"];

            // Определяем статус
            $update_status_link = '<a href="/money_'.$row["direct"].'?act=get_receipt_from_nalog&id='.$row["flow_id"].'" class="btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/sync_logo1.png" alt=""></a>';
            $view_receipt = '<a href="'.$receipt_link.'" class="modal_custom btn p-0 d-inline-block"><img style="width: 40px" src="'._IMG_.'/_static/receipt_icon.png" alt=""></a>';
            $accept_manual = '<img class="btn p-0 d-inline-block" style="width: 40px; cursor: default" src="'._IMG_.'/_static/accept_manual.png" alt="">';
            $no_receipt_data = '<img class="btn p-0 d-inline-block" style="width: 40px; cursor: default" src="'._IMG_.'/_static/no_receipt_icon.png" alt="">';
            switch($row['status_id']){
                case '1':   // новый
                    $color_status = 'bg-danger';

                    // Если указаны данные по чеку
                    if($row['fn'] && $row['fp'] && $row['i'] && $row['n']){
                        $action_btn = $update_status_link;
                    } else{
                        $action_btn = $no_receipt_data;
                    }
                    break;
                case '2':   // идёт формирование
                    $color_status = 'bg-warning';
                    $action_btn = $update_status_link;
                    break;
                case '3':   // данные сформированы
                    $receipt_data = json_decode($row['json_data'], true);
                    $seller = $receipt_data['document']['money_out']['user'];
                    $color_status = 'bg-success';
                    $action_btn = $view_receipt;
                    break;
                case '4':   // данные некорректны
                    $color_status = 'bg-dark';
                    $action_btn = $update_status_link;
                    break;
                case '5':   // подтверждён вручную
                    $color_status = 'bg-success';
                    $action_btn = $accept_manual;
                    break;
                case '6':   // Перевод
                    $color_status = 'bg-primary';
                    $action_btn = $accept_manual;
                    break;
                default:
                    $color_status = 'bg-dark border-dark border-2';
            }

            $tt = set($tt, 'bg_color', $color_status);                            // Задаём цвет фона
            $tt = set($tt, 'action_btn', $action_btn);                            // Задаём кнопку статуса


            // Комментарий
            if($row['comment']){
                $tt = set($tt, 'display_comment', 'display: block');
                $tt = set($tt, 'comment', $row['comment']);
            } else{
                $tt = set($tt, 'display_comment', 'display: none');
            }

            // Если тип - перевод, определяем направление
            $add_info = '';
            if($row['linked_id']){
                $flowObj2 = $this->table->byId($row['linked_id']);
                if( in_array($flowObj2->money_account_id, ACCOUNTS_SALARY)) $tt = set($tt, 'bg_dept_color', 'bg-secondary');     // перевод между зарплатными картами

                if($row['op_sign'] < 0){
                    $id_acc_from = $row['money_account_id'];
                    $id_acc_to = $flowObj2->money_account_id;
                } else{
                    $id_acc_from = $flowObj2->money_account_id;
                    $id_acc_to = $row['money_account_id'];
                }

                $arr_acc_from = $accObj->getTitles($id_acc_from);
                $title_acc_from = array_shift($arr_acc_from);

                $arr_acc_to = $accObj->getTitles($id_acc_to);
                $title_acc_to = array_shift($arr_acc_to);

                $add_info = ' <span style="white-space: nowrap; padding-left: 0">"'.$title_acc_from.'" -> "'.$title_acc_to.'" </span>';

                if($row['is_debt']) $tt = set($tt, 'bg_dept_color', 'bg-danger');    // операция с долгом
            }
            $tt = set($tt, 'type_title', $row['type_title'] . $add_info);


            // Даты между операциями
            $day_ts = mktime(0,0,0, date('m', $row['t']), date('d', $row['t']), date('Y', $row['t']));
            if( !in_array($day_ts, $days) ){
                $num_day = date('w', $row['t']);
                $name_day = day_of_week($num_day, 'ru_short');    // День недели
                $data .= '<div class="mt-3" style="font-size: 1.3em; color: #212529"><b>'.date("d.m.Y", $day_ts).' ('.$name_day.')</b></div>';
                $days[] = $day_ts;
            }

            $data .= $tt;
        }


        echo viewController::clear($data);
        die();
    }

}