<?php
/** Default site */

class index extends parentTemplate
{
    function _act_(){
        $tpl = get_template('', $this->module, 'body');



        $this->render($tpl);
    }

}