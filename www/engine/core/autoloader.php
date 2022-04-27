<?php
/** Автозагрузка модулей  */

function include_module(){
    spl_autoload_register(function($className) {
        // Где ищем
        $folders = [_MODULES_, _MODULES_.'/abstract_classes'];

        foreach($folders as $folder){
            $file = $folder . '/' . $className . '.php';

            if(file_exists($file)){
                require_once($file);
            }
        }
    });
}
