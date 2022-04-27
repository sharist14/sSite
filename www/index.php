<?php
header ("Content-Type: text/html; charset=utf-8");

/* Display all errors like dev */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


/* Подключаем пути */
define( '_ROOT_DIR_', getcwd() . '/' );
require_once(_ROOT_DIR_ . 'engine/core/global_ways.php');


/* Роутер */
$router = new router();
$router->run();




?>