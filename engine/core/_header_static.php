<?php
/** Header по умолчанию, если нет отдельного header */

class header
{

    public static function getHeader(){
        $tpl_path = _ROOT_DIR_ . '/engine/core/base_layers/header.html';
        $tpl = file_get_contents($tpl_path);

        return $tpl;
    }

}