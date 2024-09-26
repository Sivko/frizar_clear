<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die(json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']));
}

if (!\Bitrix\Main\Loader::includeModule('catalog')) {
    die(json_encode(['error' => 'Ошибка: модуль каталога не подключен']));
}

// ID инфоблока основного каталога
$iblockId = 1; // Убедитесь, что этот ID соответствует вашему каталогу

// Параметры пагинации
$page = isset($_GET['page']) ? intval($_GET['page']) : 1; // Текущая страница
$limit = 10; // Количество элементов на странице
$offset = ($page - 1) * $limit; // Смещение для запроса

// Получаем общее количество элементов
$totalItems = \CIBlockElement::GetList(
    [],
    ['IBLOCK_ID' => $iblockId],
    [],
    false,
    ['ID']
);

$sortField = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'SORT';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Запрашиваем элементы каталога с учетом пагинации
$dbItems = \CIBlockElement::GetList(
    [$sortField => $sortOrder],
    ['IBLOCK_ID' => $iblockId],
    false,
    ['nPageSize' => $limit, 'iNumPage' => $page],
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

// Подсчет количества страниц
$totalPages = ceil($totalItems / $limit);

// Формируем ответ с данными и информацией о пагинации
$response = [
    'items' => $items,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $limit,
    ],
];

// Выводим данные в формате JSON
header('Content-Type: application/json');
echo json_encode($items, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>