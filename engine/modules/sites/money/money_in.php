<?php
/** Операции с доходами */


class money_in extends _moneyFlow_parent
{
    var $table;

    var $id_type = 7;                 // тип операции - ЗП
    var $type_ru = 'доход';           // заголовок операции
    var $type_en = 'in';              // заголовок операции

    var $block_account_from = false;  // блок "Счет списания"
    var $block_account_to = true;     // блок "Счет зачисления"
    var $block_type = true;           // блок "Тип операции"
    var $block_entity = true;         // блок "Юр лицо(вручную)"
    var $block_receipt = false;       // блок "Кассовый чек"
    var $block_debt = false;          // блок "Взять вернуть в долг"
    var $chb_manual_confirm = false;  // чекбокс "Подтвердить вручную"

    var $str_month_summ = true;       // строка с суммой за месяц
    var $str_day_summ = true;         // строка с суммой за день

    function __construct(array $params = []){
        parent::__construct($params);
    }


    /**
    * Сохраняем доход в БД
    */
    function _act_save() {
        if (!$_POST) die('Нет данных для сохранения');
        $data = $_POST;

        // Определяем на каком счете будет операция
        $data['money_account_id'] = $data['money_account_to'];
        unset($data['money_account_to']);

        // Для доходов все записи по умолч подтверждены вручную
        $data['manual_confirm'] = 1;

        // Сохраняем в БД
        $result = $this->do_save($data);

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
        _redirect('/money_in/?'.$page.'&#r'.$result['id']);
    }


}