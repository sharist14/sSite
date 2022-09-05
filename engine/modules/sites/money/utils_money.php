<?php

/**
 * Получить текущий баланс по счёту
 */
function balance_account($id, $where = ''){
    $table = new table('money_flow');
    $flowObj = $table->_DB_->q('SELECT SUM(s) as `balance` FROM `money_flow` WHERE `money_account_id` = "'.intval($id).'"' . $where);
    if($row = $flowObj->fetch_assoc()){

        return $row['balance']? : 0;
    }

    return 0;
}


/**
 * Получить id записи редактирования
 */
function get_edit_link($flow_id, $type_en){
    $table = new table('money_flow');
    $flowObj = $table->byId($flow_id);


    // Для операций перевода между счетами
    if($flowObj->linked_id){

        // Если операция расход
        if($flowObj->op_sign < 0){
            $edit_link = "/money_".$type_en."?act=edit&id=".$flowObj->id;
        } else{
            $edit_link = "/money_".$type_en."?act=edit&id=".$flowObj->linked_id;
        }

    } else{

        // Если операция расход
        if($flowObj->op_sign < 0){
            $edit_link = "/money_out?act=edit&id=".$flowObj->id;
        } else{
            $edit_link = "/money_in?act=edit&id=".$flowObj->id;
        }
    }


    return $edit_link;
}



?>