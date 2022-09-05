<?php
/** Обзор общего финансового баланса */

class index extends parentTemplate
{
    function __construct(array $params = []){
        parent::__construct($params);
    }

    // Отображение финансового отчёта
    function _act_(){
        global $auth;

        $tpl = get_template('', $this->module, 'body');
        $tpl_com = get_template('', $this->module, 'tr_common');
        $tpl_f_row = get_template('', $this->module, 'last_flow_row');
        $tpl_acc_row = get_template('', $this->module, 'money_account_row');
        $tpl_debt_row = get_template('', $this->module, 'money_debt_row');

        $accounts_salary = ACCOUNTS_SALARY;

        // Последний день месяца
        $date = new DateTime();
        $last_day = intval($date->format( 't' ));

        // Начало и конец месяца
        $ts_start_month = mktime(0,0,0,date('m'), 1, date('Y'));
        $ts_end_month = mktime(23,59,59,date('m'), $last_day, date('Y'));

        $table_f = new table('money_flow');
        $table_acc = new table('money_accounts');
        $table_type = new table('money_type');


        /*----- БЛОК СЧЕТА -----*/

        // ЗАРПЛАТНЫЕ СЧЕТА
        $total_money = 0;

        // Получаем данные по счёту
        $account_arr = Flow::getAccountInfo($accounts_salary);
        arsort($account_arr);

        foreach($account_arr as $account){
            $tt = $tpl_acc_row;
            $tt = set($tt, 'title', $account['title']);

            $balance =  floatval($account['balance']);
            $tt = set($tt, 'summ_account', $balance);

            $tpl = setm($tpl, 'salary_account_rows', $tt);

            $total_money += $balance;
        }

        // Все деньги
        $color = $total_money >= 0? 'text-success' : 'text-danger';
        $tpl = set($tpl, 'salary_total_color', $color);
        $tpl = set($tpl, 'salary_total_money', $total_money);


        // ОСТАЛЬНЫЕ СЧЕТА
        $accounts_other = array_diff($auth->moneyAccount, $accounts_salary);

        // Получаем данные по счёту
//        $account_arr = Flow::getAccountInfo($accounts_other);
//        foreach($account_arr as $account){
//            $tt = $tpl_acc_row;
//            $tt = set($tt, 'title', $account['title']);
//
//            $balance =  floatval($account['balance']);
//            $tt = set($tt, 'summ_account', $balance);
//
//            $tpl = setm($tpl, 'other_account_rows', $tt);
//
//            $total_money += $balance;
//        }



        // ДОЛГ
        $is_debt = false;
        $sql = 'SELECT `money_accounts`.*, `money_flow`.`linked_id` as `link`, `money_accounts`.`title` as `acc_title`, `currency`.`sign_rus`, SUM(`money_flow`.`s`) as `summ_acc`, 
                (SELECT `money_account_id` FROM  `money_flow` WHERE `id` = `link`) as `debt_id`,
                (SELECT `title` FROM  `money_accounts` WHERE `id` = `debt_id`) as `debt_title`  
                FROM `money_accounts`
                LEFT JOIN `money_flow` ON(`money_accounts`.`id` = `money_flow`.`money_account_id`)
                LEFT JOIN `currency` ON(`money_accounts`.`currency_id` = `currency`.`id`)
                WHERE `is_debt` 
                GROUP BY `money_accounts`.`id`, `debt_id` HAVING `summ_acc` > 0 
                ORDER BY `money_accounts`.`id` ASC
        ';

        $moneyAccObj = $table_acc->_DB_->q($sql);
        while($row = $moneyAccObj->fetch_assoc()){
            $summ_acc = floatval(abs($row['summ_acc']));
            if(!$summ_acc) continue;
            $tt = $tpl_debt_row;

            // Если сумма > 0, мы назанимали
            if($row['summ_acc'] >= 0){
                $title = '"'. $row['acc_title'].'" -><br>' . '"'. $row['debt_title'].'"';
                $link_title = '<a href="/money_transfer/?acc1='.$row["id"].'&acc2='.$row["debt_id"].'" class="text-decoration-none" style="color: #084298">'.$title.'</a>';
                $tt = set($tt, 'title', $link_title);
                $tt = set($tt, 'summ_account', mf($summ_acc, 'is_penny'));
                $tt = set($tt, 'color_sum', 'text-danger');
            }

            $tpl = setm($tpl, 'debt_account_rows', $tt);

            if(!$is_debt) $is_debt = true;
        }

        if(!$is_debt) $tpl = set($tpl, 'display_debt_block', 'hide');



        /* Блок "Топ 5 крупных операций" */
        
        // по доходу за текущий месяц
        /*$moneyObj = $table_f->_DB_->q($table_f->getSelect(' AND `op_sum` > 0 AND `op_datetime` BETWEEN '.$ts_start_month.' AND '.$ts_end_month.' ORDER BY `op_sum` DESC LIMIT 5'));
        while($row = $moneyObj->fetch_assoc()){
            $tt = $tpl_com;
            $tt = set($tt, 'title', $row['title']);
            $tt = set($tt, 'value', $row['op_sum'].' руб');

            $tpl = setm($tpl, 'profit_op_rows', $tt);
        }*/

        // по расходу за текущий месяц
        /*$moneyObj = $table_f->_DB_->q($table_f->getSelect(' AND `op_sum` < 0 AND `op_datetime` BETWEEN '.$ts_start_month.' AND '.$ts_end_month.' ORDER BY `op_sum` ASC LIMIT 5'));
        while($row = $moneyObj->fetch_assoc()){
            $tt = $tpl_com;
            $tt = set($tt, 'title', $row['title']);
            $tt = set($tt, 'value', $row['op_sum'].' руб');

            $tpl = setm($tpl, 'expends_op_rows', $tt);
        }*/


        // Блок "Вывод последних 50 операций"
        $moneyObj = $table_f->_DB_->q($table_f->getSelect(' AND `money_account_id` IN('.implode(',', $accounts_salary).') ORDER BY `t` DESC LIMIT 50'));
        while ($row = $moneyObj->fetch_assoc()) {
            $tt = $tpl_f_row;
            $tt = set($tt, 'date', date('d.m.Y H:i', $row['t']).' (' .day_of_week(date('w', $row['t']), 'ru_short'). ')');

            // тип операции
            $table_type->byId($row['money_type_id']);
            $tt = set($tt, 'type', $table_type->title);

            // Находим страницу пагинации
            $page = getPageByDate($row['t']);

            if($row['linked_id']) {
                $flowObj = $table_f->byId($row['linked_id']);
                if (in_array($flowObj->money_account_id, $accounts_salary)){  // перевод между зарплатными картами
                    if($row['op_sign'] < 0) continue;                         // только операцию зачисления
                    $color_sum = 'text-secondary';
                } else{
                    $color_sum = ($row['op_sign'] >= 0)? 'text-success' : 'text-danger';
                }

                $href_flow = '/money_transfer/?'.$page.'&#r'.$row['id'];

            } elseif($row['op_sign'] >= 0){
                $color_sum = 'text-success';
                $href_flow = '/money_in/?'.$page.'&#r'.$row['id'];
            } else{
                $color_sum = 'text-danger';
                $href_flow = '/money_out/?'.$page.'&#r'.$row['id'];
            }
            $tt = set($tt, 'color_sum', $color_sum);
            $tt = set($tt, 'href_flow', $href_flow);
            $tt = set($tt, 'op_sum', $row['s']);

            $tpl = setm($tpl, 'rows', $tt);
        }


        $this->render($tpl);
    }



    /**
     * Найти юр лицо по инн
     */
    function get_entity_id($inn, $entity_name = ''){

        $table = new table('entity');
        $db = $table->_DB_->q($table->getSelect(' AND `inn` = "'.$inn.'"'));
        if($row = $db->fetch_assoc()){
            $entity_id = $row['id'];
        } else{
            $table->byId(0);
            $table->set('title', $entity_name);
            $table->set('inn', $inn);
            $table->save();

            $entity_id = $table->id;

        }

        return $entity_id;
    }



}