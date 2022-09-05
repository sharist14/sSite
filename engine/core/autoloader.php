<?php
/** Автозагрузка модулей  */

function include_module(){
    spl_autoload_register(function($className) {
        // Где ищем
        $folders = [_CORE_, _MODULES_, _MODULES_.'/abstract_classes'];

        foreach($folders as $folder){
            $file = $folder . '/' . $className . '.php';

            if(file_exists($file)){
                require_once($file);
            }

            $file_core = $folder . '/' . strtolower($className) . '.core.php';
            if(file_exists($file_core)){
                require_once($file_core);
            }
        }
    });
}
