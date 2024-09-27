<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die(json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']));
}

// Задаем ID инфоблока
$iblockId = 17; 
$sortField = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'SORT';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';

// Формируем массив фильтров
$filter = [
    'IBLOCK_ID' => $iblockId,
    'ACTIVE' => 'Y',
    'ID' => (string)$id,
    'INCLUDE_SUBSECTIONS' => 'N',
];

// Получаем все разделы инфоблока
$sections = [];
$sectionIterator = \CIBlockSection::GetList(
    [$sortField => $sortOrder], // Сортировка
    $filter, // Фильтры
    false, // Не требуется подсчет элементов
    ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'CODE', 'INLCUDE_SUBSECTIONS'] // Поля для выборки http://clean.frizar.ru/catalog/underwear/
);

while ($section = $sectionIterator->Fetch()) {
    $sections[] = $section;
}

header('Content-Type: application/json');
echo json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>