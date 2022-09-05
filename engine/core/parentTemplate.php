<?php
/** Родительский шаблон от которого наследуются контроллеры */

class parentTemplate
{
    var $params;    // параметры из URL
    var $Db;        // подключение к БД
    var $module;    // подключаемый модуль
    var $include;   // подключаемые файлы стилей, скриптов и т.д.


    function __construct($params = []){

        // Получаем параметры url
        $this->params = $params;

//        // Подключаем БД
//        if(defined('HOST') && defined('USER') && defined('PASS') && defined('DB')){
//            $database = new Database();
//            $this->Db = $database->getConnect();
//        }

        // Подключаем скрипты и стили
         $this->include = $this->getInclude();

        // Получаем имя модуля
        $this->module = $this->getModuleName();

        // Информирование в div и всплывающие подсказки
        flash::view();
    }


    /**
     * Получаем имя шабона(токое же как имя файла)
     */
    public function getParams(){
        $c = new ReflectionClass($this);
        $way_to_file = $c->getFileName();

        $file = basename($way_to_file, '.php');

        return $c;
    }


     /**
     * Подключение скриптов и стилей к странице
     */
    public function getInclude(){
        $include = [];

        return $include;
    }


    /**
     * Получаем имя шабона(токое же как имя файла)
     */
    public function getModuleName(){
        $c = new ReflectionClass($this);
        $way_to_file = $c->getFileName();
        $file = basename($way_to_file, '.php');

        return $file;
    }


    public function render($template, $params = []){
        viewController::display($template, $params);
    }

}