<?php
/** Footer по умолчанию, если нет отдельного footer */

class footer
{

    public static function getFooter(){
        $tpl_path = _ROOT_DIR_ . '/engine/core/base_layers/footer.html';
        $tpl = file_get_contents($tpl_path);

        return $tpl;
    }

}