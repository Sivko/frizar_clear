<?php 

ini_set('session.cookie_secure', 1);
header("Access-Control-Allow-Origin: http://localhost:3000");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header('Content-Type: application/json');



require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  // Заголовок ответа на preflight-запрос
  header("HTTP/1.1 200 OK");
  exit();
}

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
  die(json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']));
}

if (!\Bitrix\Main\Loader::includeModule('catalog')) {
  die(json_encode(['error' => 'Ошибка: модуль каталога не подключен']));
}

