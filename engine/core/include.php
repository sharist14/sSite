<?php
/* Display all errors like dev */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/* Display PROD errors */
//ini_set('display_errors', 1);           // Включение протоколирования ошибок
//ini_set('display_startup_errors', 0);
//error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

/* Глобальные настройки и включения */
require_once(_ROOT_DIR_ . '/engine/core/router.php');
require_once(_ROOT_DIR_ . '/engine/core/viewController.php');
include_once(_ROOT_DIR_ . '/engine/core/utils.php');
require_once(_ROOT_DIR_ . '/engine/core/table.php');

?>