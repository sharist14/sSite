<?php
/** Операции с переводами */


class money_transfer extends _moneyFlow_parent
{
    var $table;

    var $id_type = 6;                   // тип операции - Перевод
    var $type_ru = 'перевод';           // заголовок операции
    var $type_en = 'transfer';          // заголовок операции
    var $uncover_type = true;           // не раскрывать select при редактировании

    var $block_account_from = true;     // блок "Счет списания"
    var $block_account_to = true;       // блок "Счет зачисления"
    var $block_type = true;             // блок "Тип операции"
    var $block_entity = false;          // блок "Юр лицо(вручную)"
    var $block_receipt = false;          // блок "Кассовый чек"
    var $block_debt = true;             // блок "Взять/вернуть в долг" (с выбором кредитора)
    var $chb_manual_confirm = false;    // чекбокс "Подтвердить вручную"

    var $str_month_summ = false;        // строка с суммой за месяц
    var $str_day_summ = false;          // строка с суммой за день


    function __construct(array $params = []){
        parent::__construct($params);
    }

    /**
     * Сохраняем перевод в БД
     */
    function _act_save($data = [], $return_result = false) {
        if(empty($data) && !empty($_POST)) $data = $_POST;
        if (!$data) die('Нет данных для сохранения');


        // ИСХОДЯЩЕЕ ПОСТУПЛЕНИЕ
        $data['money_account_id'] = $data['money_account_from'];
        unset($data['money_account_from']);
        $result_from = $this->do_save($data, -1);


        // ВХОДЯЩЕЕ ПОСТУПЛЕНИЕ
        if($data['id'] && $data['linked_id']){     // если редактируем, то меняем у второй записи id и связанную id
            $id = $data['id'];
            $lid = $data['linked_id'];
            $data['id'] = $lid;
            $data['linked_id'] = $id;
        }

        $data['money_account_id'] = $data['money_account_to'];
        unset($data['money_account_to']);

        // Переводим дату в ts
        preg_match('~\d{4}-\d{2}-\d{2}T\d{2}:\d{2}~', $data['t'], $match);
        if($match[0]){
            $data['t'] = date_timestamp($data['t'], 'iso8601');
        }
        $data['t'] += 60;    // добавим минуту чтобы время отличалось
        $result_to = $this->do_save($data);


        // Сохраняем связанные записи
        if($result_from['status'] == 'create' && $result_to['status'] == 'create'){
            $data = ['id' => $result_from['id'], 'linked_id' => $result_to['id'] ];
            $this->do_save($data, -1);

            $data = ['id' => $result_to['id'], 'linked_id' => $result_from['id'] ];
            $this->do_save($data);
        } else{
            add_log('При переводе не удалось сохранить связанность записей id'.$result_from['id'].' и id'.$result_to['id'], '', 3);
        }


        // Пагинация
        $page = '';
        switch($result_from['status']){
            case 'create':
            case 'update':
                // Находим страницу пагинации
                $page = getPageByDate($result_from['t']);
                break;
            case 'duplicate':
                break;
        }


        if(!$return_result){
            flash::add_toast('Money', $this->frame_info[$result_from['status']], 3);
            _redirect('/money_transfer/?'.$page.'&#r'.$result_from['id']);
        } else{
            return true;
        }
    }



}