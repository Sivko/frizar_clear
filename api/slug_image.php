<?php

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die(json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']));
}

if (!\Bitrix\Main\Loader::includeModule('catalog')) {
    die(json_encode(['error' => 'Ошибка: модуль каталога не подключен']));
}

// Параметры пагинации
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Текущая страница
$iblockId = isset($_GET['iblock_id']) ? intval($_GET['iblock_id']) : 17; // Текущая страница
$slug = isset($_GET['slug']) ? $_GET['slug'] : 'freza_2x4x40x6x9_4_z2_shponochnaya_powermill_vhm_19988_2_000';


$limit = 10; // Количество элементов на странице
$offset = ($page - 1) * $limit; // Смещение для запроса

$sortField = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'SORT';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

$dbItems = \CIBlockElement::GetList(
    [$sortField => $sortOrder],
    ['IBLOCK_ID'=>$iblockId, 'CODE'=> $slug],
    false,
    ['nPageSize' => $limit, 'iNumPage' => $page],
    ['ID', 'IBLOCK_ID', 'IBLOCK_SECTION_ID', 'NAME', 'CODE']
); 

$items = [];
while ($item = $dbItems->Fetch()) {
    $item['PROPERTIES'] = [];
    // Запрашиваем свойства для каждого элемента
    $dbProps = \CIBlockElement::GetProperty($item['IBLOCK_ID'], $item['ID'], ['sort' => 'asc'], []);
    while ($prop = $dbProps->Fetch()) {
        // Добавляем свойство в массив
        $item['PROPERTIES'][$prop['CODE']] = $prop['VALUE'];
    }
    $items[] = $item;
}

// Выводим данные в формате JSON
header('Content-Type: application/json');
echo json_encode($items[0], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>