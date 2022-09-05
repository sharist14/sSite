<?php
/** Header для Money site */

class header
{

    public static function getHeader(){
        global $auth;
        $tpl = get_template('layers', 'header','body');

        // Пользователь зарегистрирован
        if($auth->user_id){
            $tpl = set($tpl, 'username', $auth->userObj->fio);
            $tpl = set($tpl, 'user_icon', '<i class="fal fa-user-shield"></i>');

            $tpl_link = get_template('layers', 'header','user_auth');
            $tpl = set($tpl, 'auth_link', $tpl_link);

        } else{
            $tpl = set($tpl, 'username', 'Гость');
            $tpl = set($tpl, 'user_icon', '<i class="fal fa-user-secret"></i>');

            $tpl_link = get_template('layers', 'header','user_guest');
            $tpl = set($tpl, 'auth_link', $tpl_link);
        }


        // root доступ
        if( array_key_exists(1, $auth->groups) ){

        } else {
            $tpl = set($tpl, 'display_test', 'hide');
        }

        return $tpl;
    }

}