<?php
/** Операции с налоговой */
require_once(_MODULES_ . '/api/nalog.api.php');

class nalog_token extends parentTemplate
{

    function _act_(){
        $tpl = get_template('', $this->module, 'body');
        $tplr = get_template('', $this->module, 'row');

        $first = true;

        $table = new table('nalog_token');
        $tokenObj = $table->_DB_->q( $table->getSelect(' ORDER BY `id` DESC') );
        while($row = $tokenObj->fetch_assoc()){

            // Активный токен
            if($first){
                $token_expired = intval($row['datetime']) + (60 * 60 * 24); // время жизни токена - сутки

                if(time() < $token_expired){
                    $status = '<div class="bg-success p-2 text-light rounded">В порядке до '.date('d.m.Y H:i', $token_expired).'</div>';
                } else{
                    $status = '<div class="bg-danger p-2 text-light rounded">Был просрочен '.date('d.m.Y H:i', $token_expired).'</div>';
                }

                $tpl = set($tpl, 'status', $status);
                $tpl = set($tpl, 'saved_phone', NALOG_AUTH_PHONE);
                $tpl = set($tpl, 'saved_inn', NALOG_AUTH_INN);

                $first = false;
            }

            $tt = $tplr;
            $tt = set($tt, 'id', $row['id']);
            $tt = set($tt, 'source', $row['source']);
            $tt = set($tt, 'date', date('d.m.Y H:i', $row['datetime']));

            $tpl = setm($tpl, 'rows', $tt);
        }

        $this->render($tpl);
    }


    function _act_refresh_token(){
        $api = new nalogApi();
        $api->load_token();

        if($api->token){
            _redirect('/nalog_token');
        } else{
            flash::add_toast('Money', 'Не удалось обновить токен по токену обновления', 10, 'danger');
        }
    }

    function _act_send_auth_sms(){
        $api = new nalogApi();
        $res = $api->register_by_phone();

        if($res){
            _redirect('/nalog_token');
        } else{
            die('Ошибка при попытке запроса кода подтверждения');
        }
    }

    function _act_get_token_by_phone(){
        $code = $_POST['code'];

        if($code){
            $api = new nalogApi();
            $api->get_new_token_by_phone($code);

            if($api->token){
                _redirect('/nalog_token');
            } else{
                die('Не удалось получить новый токен через инн');
            }
        }
    }

    function _act_get_token_by_inn(){
        $api = new nalogApi();
        $api->get_new_token_by_inn();

        if($api->token){
            _redirect('/nalog_token');
        } else{
            die('Не удалось получить новый токен по inn');
        }
    }


}