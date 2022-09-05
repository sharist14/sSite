<?php
/** Класс для для работы с деньгами */

class Flow extends parentTemplate {

    /**
     * Получить информацию о счёте
     * @param array $acc_arr - массив с аккаунтами
     * @return array
     */
    public static function getAccountInfo($acc_arr = []){
        $filter_acc = '';
        if( !empty($acc_arr) ) $filter_acc = ' AND `money_accounts`.`id` IN('.implode(',', $acc_arr).') ';

        $result = [];
        $table_acc = new table('money_accounts');
        $sql = 'SELECT `money_accounts`.*,`money_accounts`.`title` as `title`, `currency`.`sign_rus`, SUM(`money_flow`.`s`) as `balance` FROM `money_accounts`
                LEFT JOIN `money_flow` ON(`money_accounts`.`id` = `money_flow`.`money_account_id`) 
                LEFT JOIN `currency` ON(`money_accounts`.`currency_id` = `currency`.`id`) 
                WHERE 1 '.$filter_acc.' 
                GROUP BY `money_accounts`.`id` 
                ORDER BY `money_accounts`.`id` ASC
        ';
        $moneyAccObj = $table_acc->_DB_->q($sql);
        while($row = $moneyAccObj->fetch_assoc()) {
            $result[$row['id']] = $row;
        }

        return $result;
    }
}