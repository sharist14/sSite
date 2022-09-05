<?php
header ("Content-Type: text/html; charset=utf-8");

// Запускаем сессии
session_start();

/* Подключаем пути */
define( '_ROOT_DIR_', getcwd() );
require_once(_ROOT_DIR_ . '/engine/core/include.php');

/* Авторизация */
require_once(_ROOT_DIR_.'/engine/core/auth.php');
$auth = new auth();

/* Роутер */
$router = new router();
$router->run();




?>