<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!\Bitrix\Main\Loader::includeModule('iblock')) {
    die(json_encode(['error' => 'Ошибка: модуль инфоблоков не подключен']));
}

// Задаем ID инфоблока
$iblockId = 17;

$sortField = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'SORT';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
//$sectionId = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;
$id = isset($_GET['id']) ? intval($_GET['id']) : null;

// Формируем массив фильтров
$filter = [
    'IBLOCK_ID' => $iblockId,
    'ACTIVE' => 'Y',
    'ID' => (string)$id,
    'INCLUDE_SUBSECTIONS' => 'N',
];

// if ($sectionId !== null) {
//     $filter['IBLOCK_SECTION_ID'] = (string)$sectionId;
// }

// Получаем все разделы инфоблока
$sections = [];
$sectionIterator = \CIBlockSection::GetList(
    [$sortField => $sortOrder], // Сортировка
    $filter, // Фильтры
    false, // Не требуется подсчет элементов
    ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'CODE', 'INLCUDE_SUBSECTIONS'] // Поля для выборки
);

while ($section = $sectionIterator->Fetch()) {
    $sections[] = $section;
}

function generateFullSlugs(&$node, $parentSlug = '') {
    // Формируем новый slug для текущего узла
    $node['fullslug'] = trim($parentSlug . '/' . $node['slug'], '/');

    // Если у узла есть дочерние элементы, рекурсивно вызываем функцию для них
    if (!empty($node['childs'])) {
        foreach ($node['childs'] as &$child) {
            generateFullSlugs($child, $node['fullslug']);
        }
    }
}


//first
$items = [];
foreach ($sections as $item) {
    $items[$item['ID']] = [
        'id' => $item['ID'],
        'name'=> $item['NAME'],
        'slug'=>$item['CODE'],
        'childs' => null
    ];
}

// Строим дерево
$tree = [];
foreach ($sections as $item) {
    if ($item['IBLOCK_SECTION_ID'] === null) {
        // Если нет родителя, добавляем элемент в корень дерева
        $tree[] = &$items[$item['ID']];
    } else {
        // Если есть родитель, добавляем элемент в его дочерние элементы
        $items[$item['IBLOCK_SECTION_ID']]['childs'][] = &$items[$item['ID']];
    }
}

foreach($tree as $_item){
    generateFullSlugs($_item);
}


//end 

// Выводим иерархию в формате JSON
header('Content-Type: application/json');
// echo json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo json_encode($tree, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
