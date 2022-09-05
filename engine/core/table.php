<?php

class table {

    function __construct($table_name = '', $user='', $pass='', $db=''){
        $this->_TABLE_ = $table_name;
        $this->_DB_ = new Database($user, $pass, $db);
    }


    // Ищем строку в БД по её id
    function byId($id){
        $obj = $this->_DB_->q('SELECT * FROM `'.$this->_TABLE_.'` WHERE `id`= "'.intval($id).'"');
        if( $row = $obj->fetch_assoc() ){
            foreach($row as $field_name => $value){
                $this->{$field_name} = $value;
            }

            return $this;
        } else{

            // Ничего не нашли
            if( !$id ){

                // Возвращаем поля таблицы
                return $this->get_field_name();
            } else{
                return false;
            }
        }
    }

    // Задаём в классе свойства с именами как у полей в БД
    function get_field_name(){
        $obj = $this->_DB_->q('SELECT COLUMN_NAME FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`="'.$this->_DB_->cur_dbname.'" AND `TABLE_NAME`="'.$this->_TABLE_.'"');
        while($row = $obj->fetch_row()){

            // Задаём имена свойств класса такие же как имена полей таблицы
            $this->{$row[0]} = false;
        }

        return $this;
    }


    // Задать значение свойству
    function set($field, $value){

        // Поле id не меняем через этот метод
        if($field == 'id') return;

        // Если в классе есть такое свойство, задаём ему значение
        if(isset($this->$field)){
            $this->$field = $value;
        }
    }


    // Получая на входе значение WHERE, генерируем строку запроса
    function getSelect($where = ''){
        return 'SELECT * FROM `' .$this->_TABLE_.'` WHERE 1' . $where;
    }

    // Получая на входе значение WHERE, делаем запрос в БД
    function getSSelect($where = '', $debug = false){
        $res = $this->_DB_->q($this->getSelect($where));

        if($debug) pr($this->getSelect($where));

        return $res;
    }

    // Получая на входе поле поиска и значение WHERE, генерируем строку запроса
    function getSelectF($field = '',$where = ''){
        if( !$field ) $field = '*';
        return 'SELECT '.$field.' FROM `' .$this->_TABLE_.'` WHERE 1' . $where;
    }

     // Получая на входе поле поиска и значение WHERE, делаем запрос к БД
    function getSSelectF($field = '',$where = '', $debug = false){
        $res = $this->_DB_->q($this->getSelectF($field, $where));

        if($debug) pr($this->getSelectF($field, $where));

        return $res;
    }

    
    // Получаем количество строк
    function getCount($where){
        $db = $this->_DB_->q('SELECT * FROM `' .$this->_TABLE_.'` WHERE 1' . $where);
        return $db->num_rows;
    }

    // Получаем сумму по выбранному полю
    function getSum($field, $where = ''){
        $db = $this->_DB_->q('SELECT SUM(`'.$field.'`) as `res_sum` FROM `' .$this->_TABLE_.'` WHERE 1' . $where);
        $row = $db->fetch_row();

        return $row[0];
    }


    /** Сохраняем данные в БД */
    function save(){

        // Получаем массив с текущими свойстами
        $vars_arr = $this->get_vars_values();


        // Если id задан, то обновляем имеющуюсю строку
        if($vars_arr['id']){
            $sql_data = [];

            $id = $vars_arr['id'];      // Запоминаем id
            unset($vars_arr['id']);     // Удаляем из списка атрибутов(чтобы не обновлять id)

            // Формируем данные для обновления
            foreach($vars_arr as $field_name => $value){

                if(json_decode($value)){
                    // для json данных
                    $sql_data[] = '`' . $field_name . '`="'. $this->_DB_->conn->real_escape_string($value).'"';
                } else{
                    $sql_data[] = '`' . $field_name . '`="'.$this->_DB_->conn->real_escape_string($value).'"';
                }
            }

            // Сохраняем
            if( !empty($sql_data) ){
                $this->_DB_->q('UPDATE `' .$this->_TABLE_. '` SET '.implode(",", $sql_data).' WHERE `id` = "' .intval($id) . '"');
            }
        }

        // Иначе добавляем новую строку
        else{
            $fields = [];
            $values = [];

            // Формируем данные для обновления
            foreach($vars_arr as $field_name => $value){

                if(!empty($value)){
                    if(json_decode($value)){
                        // для json данных
                        $fields[] = '`'.$field_name.'`';
                        $values[] = '"'.$this->_DB_->conn->real_escape_string($value).'"';
                    } else{
                        $fields[] = '`'.$field_name.'`';
                        $values[] = '"'.$this->_DB_->conn->real_escape_string($value).'"';
                    }
                }
            }

            // Сохраняем
            if( !empty($fields) && !empty($values)){
                $this->_DB_->q( 'INSERT INTO `' .$this->_TABLE_. '` ('.implode(",", $fields).') VALUES(' .implode(",", $values) .')' );

                // Запоминаем id созданной записи
                $this->id = mysqli_insert_id($this->_DB_->conn);
            }
        }
    }

    /** Удаляем строку из БД */
    function del(){
        if($this->id){
            $this->_DB_->q( 'DELETE FROM `' .$this->_TABLE_. '` WHERE `id` = "'.intval($this->id).'"' );
            return true;
        }

        return false;
    }



    // Получаем массив из свойств класса
    function get_vars_values(){

        // Получаем свойства класса
        $vars_arr = get_object_vars($this);

        // Убираем служебные переменные
        unset($vars_arr['_TABLE_']);
        unset($vars_arr['_DB_']);
        unset($vars_arr['_DB_NAME_']);

        // Если id не задан, убираем его
        if(empty($vars_arr['id'])){
            unset($vars_arr['id']);
        }

        return $vars_arr;
    }

    // Получаем значения поля title
    function getTitles($id = ''){
        $where = $id? ' WHERE `id` = '.intval($id) : '';
        $arr = [];
        $db = $this->_DB_->q('SELECT * FROM `'.$this->_TABLE_.'`'.$where);
        while($row = $db->fetch_assoc()){
            if(key_exists('title', $row)){
                $arr[$row['id']] = $row['title'];
            }
        }

        return $arr;
    }



}