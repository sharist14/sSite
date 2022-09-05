<?php
/** Всё что связано с авторизацией и доступами */

define( '_ROOT_DIR_', getcwd());
define( '_CORE_', _ROOT_DIR_ . '/engine/core');


class auth {

    var $user_id = 0;
    var $userObj;
    var $userGroupObj;
    var $sessObj;
    var $sitesObj;
    var $username;
    var $fio;
    var $groups = [];           // Подключенные группы доступа
    var $moneyAccount = [];     // Подключенные финансовае счета
    var $sites = [];            // Подключенные сайты


    public function __construct(){
        require_once(_ROOT_DIR_.'/config/secret_common.php');
        $this->userObj = new table($user_table, $repelet_user, $repelet_pass, $repelet_db);
        $this->userGroupObj = new table($userGroup_table, $repelet_user, $repelet_pass, $repelet_db);
        $this->sessObj = new table($sess_table, $repelet_user, $repelet_pass, $repelet_db);
        $this->sitesObj = new table($sites_table, $repelet_user, $repelet_pass, $repelet_db);

        // Logout
        if($_GET['act'] == 'logout'){
            $this->logout();
            die();
        }

        // Проверяем наличие кук сессии
        if(!$this->checkSessCookie()){

            // Проверяем введённые логин и пароль
            if($_POST['username'] && $_POST['password']){
                $username = trim($_POST['username']);
                $password = trim($_POST['password']);

                $db = $this->userObj->getSSelect(' AND `username` ="'.$this->userObj->_DB_->conn->real_escape_string($username).'" 
                                                 AND`password` ="'.sha1($this->userObj->_DB_->conn->real_escape_string($password.$username)).'"');
                if($row = $db->fetch_assoc()){
                    $this->user_id = $row['id'];

                    $curr_sess_id = session_id();

                    // сохраняем в сессию
                    $db = $this->sessObj->getSSelect(' AND `user_id` = "'.$this->user_id.'" AND `session_id` = "'.$curr_sess_id.'"');
                    if($row2 = $db->fetch_assoc()){
                        $this->sessObj->byId($row2['id']);
                    } else{
                        $this->sessObj->byId(0);
                    }

                    $this->sessObj->set('user_id', $this->user_id);
                    $this->sessObj->set('session_id', $curr_sess_id);
                    $this->sessObj->set('ip', $_SERVER['REMOTE_ADDR']);
                    $this->sessObj->set('last_login', time());
                    $this->sessObj->save();

                    // Куки на год
                    setcookie('repeletNervId', $curr_sess_id, time()+60*60*24*365, "/", '.repelet.ru');

                    _redirect('/');
                } else{
                    $this->showForm('Не верный логин или пароль');
                    die();
                }

            } else{
                $this->showForm();
                die();
            }
        }
    }


    /**
     * Выход из УЗ
     * return bool
     */
    private function logout(){

        // Удаляем из сессии
        $db = $this->sessObj->getSSelect(' AND `user_id` = "'.$this->user_id.'" AND `session_id` = "'.$_COOKIE['repeletNervId'].'"');
        if($row = $db->fetch_assoc()){
            $this->sessObj->byId($row['id']);
            $this->sessObj->del();
        }

        // Обнуляем ID
        $this->user_id = 0;

        // Удаляем куки
        setcookie('repeletNervId', $_COOKIE['repeletNervId'], time()-1, "/", '.repelet.ru');

        _redirect('https://www.repelet.ru/login');
    }


    /**
     * Проверка на куки сессии
     * return bool
     */
    private function checkSessCookie(){
        if($_COOKIE['repeletNervId']){

            $session_id = $_COOKIE['repeletNervId'];

            // сохраняем в сессию
            $db = $this->sessObj->getSSelect(' AND `session_id` = "'.$session_id.'"');
            if($row2 = $db->fetch_assoc()){

                // Если мы на странице авторизации, сразу уходим на главную
                $login_url = $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
                preg_match('~www.repelet.ru/login~', $login_url, $match);
                if(!empty($match)){
                    _redirect('/');
                }

                // Пользователь найден
                $this->user_id = $row2['user_id'];

                // Накидываем права
                $this->doAuth();

                return true;
            }
        }

        return false;
    }


    /**
     * Отображение формы авторизации
     */
    private function showForm($error = ''){
        $tpl = file_get_contents( _CORE_.'/base_layers/auth.html' );

        if($error) $tpl = set($tpl, 'error' ,$error);

        print viewController::clear($tpl);
    }


    /**
     * Логинем пользователя
     * return bool
     */
    private function doAuth(){
        if(!$this->user_id) _redirect('https://www.repelet.ru/login');

        $this->userObj->byId($this->user_id);


        /* НАКИДЫВАЕМ ПРАВА */

        $this->username = $this->userObj->username;
        $this->fio = $this->userObj->fio;

        // Группы доступа
        $groups = explode(',', $this->userObj->groups);
        foreach($groups as $groupID){
            $this->userGroupObj->byId($groupID);
            $this->groups[$groupID] = $this->userGroupObj->title;
        }

        // Сайты куда можно ходить
        $sites = explode(',', $this->userObj->sites);
        foreach($sites as $siteID){
            $this->sitesObj->byId($siteID);
            $this->sites[$siteID] = $this->sitesObj->title;
        }

        // Определяем финансовые доступы
        $accounts = explode(',', $this->userObj->money_accounts);
        foreach($accounts as $accountID){
            $this->sitesObj->byId($siteID);
            $this->sites[$siteID] = $this->sitesObj->title;
        }

        $this->moneyAccount = explode(',', $this->userObj->money_accounts);
    }

}