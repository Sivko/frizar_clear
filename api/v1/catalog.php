<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$limit = isset($_GET['limit']) ? $_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

$sortField = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'SORT';
$sortOrder = isset($_GET['order']) ? $_GET['order'] : 'ASC';
// $section_slug = isset($_GET['section_slug']) ? $_GET['section_slug'] : null;

$section_slug = 'razvertki_mashinnye_nasadnye_guhring_germaniya';

$response = [];
$breadcrumbs = [];
$url = '';


if ($section_slug) {

    

    $arFilter = [
        'IBLOCK_ID' => 17,
        'CODE' => $section_slug, // UF_SLUG - это код пользовательского свойства раздела
        'ACTIVE' => 'Y'
    ];

    $section = CIBlockSection::GetList(array(), $arFilter, false, ['NAME', 'CODE', 'ID'])->Fetch();

    $currentSectionId = $section['ID'];

    $APPLICATION->IncludeComponent(
        "bitrix:catalog.section",
        "",
        array(
            "IBLOCK_TYPE" => "catalog", // Тип инфоблока
            "IBLOCK_ID" => $iblockId, // ID инфоблока
            "SECTION_ID" => $currentSectionId, // ID выбранного раздела
            "SECTION_CODE" => "", // Можно также использовать слаг, но если у нас есть ID, это предпочтительнее
            "SECTION_USER_FIELDS" => array(),
            "ELEMENT_SORT_FIELD" => "sort", // Поле для сортировки элементов
            "ELEMENT_SORT_ORDER" => "asc", // Порядок сортировки элементов
            "FILTER_NAME" => "", // Название фильтра, если используется
            "INCLUDE_SUBSECTIONS" => "Y", // Включать подразделы
            "SHOW_ALL_WO_SECTION" => "N", // Не показывать элементы без разделов
            "PAGE_ELEMENT_COUNT" => "20", // Количество элементов на странице
            "PROPERTY_CODE" => array(), // Массив кодов свойств, которые нужно выводить
            // другие параметры компонента...
        ),
        false
    );


    while ($currentSectionId) {
        $section = CIBlockSection::GetByID($currentSectionId)->GetNext();
        array_unshift($breadcrumbs, [
            "name" => $section["NAME"],
            "href" => $section["SECTION_PAGE_URL"]
        ]);
        $currentSectionId = $section["IBLOCK_SECTION_ID"];
    }

    // Запрашиваем элементы каталога с учетом пагинации
    $dbItems = \CIBlockElement::GetList(
        [$sortField => $sortOrder],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE'=>'Y'],
        false,
        ['nPageSize' => $limit, 'iNumPage' => $page],
        ['ID', 'IBLOCK_ID', 'NAME', 'SORT', 'IBLOCK_SECTION_ID']
    );
    $items = [];
    while ($item = $dbItems->Fetch()) {
        $item['PROPERTIES'] = [];
    
        // Запрашиваем свойства для каждого элемента
        $dbProps = \CIBlockElement::GetProperty($item['IBLOCK_ID'], $item['ID'], ['sort' => 'asc']);
        while ($prop = $dbProps->Fetch()) {
            // Добавляем свойство в массив
            $item['PROPERTIES'][$prop['CODE']] = $prop['VALUE'];
        }
        
        $items[] = $item;
    }

    $response = [
        'items' => $items,
        "meta" => [
          "metatitle"=> $section['NAME'],
          "metadescription"=> '',
          "title"=> '',
          "description"=> "Описание на самой странице",
          "broadcrumbs"=> $breadcrumbs,
        ],
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            //'total_items' => (int)$dbItems->SelectedRowsCount(),
            'limit' => $limit,
        ],
    ];

}   else {
    // Запрашиваем элементы каталога с учетом пагинации
    $dbItems = \CIBlockElement::GetList(
        [$sortField => $sortOrder],
        ['IBLOCK_ID' => $iblockId, 'ACTIVE'=>'Y'],
        false,
        ['nPageSize' => $limit, 'iNumPage' => $page],
        ['ID', 'IBLOCK_ID', 'NAME', 'SORT', 'IBLOCK_SECTION_ID']
    );
    $items = [];
    while ($item = $dbItems->Fetch()) {
        $item['PROPERTIES'] = [];
    
        // Запрашиваем свойства для каждого элемента
        $dbProps = \CIBlockElement::GetProperty($item['IBLOCK_ID'], $item['ID'], ['sort' => 'asc']);
        while ($prop = $dbProps->Fetch()) {
            // Добавляем свойство в массив
            $item['PROPERTIES'][$prop['CODE']] = $prop['VALUE'];
        }
    
        $items[] = $item;
    }
    // Подсчет количества страниц
    $totalPages = ceil($dbItems->SelectedRowsCount() / $limit);
    $response = [
        'items' => $items,
        "meta" => [
          "metatitle"=> '',
          "metadescription"=> '',
          "title"=> '',
          "description"=> "Описание на самой странице",
          "broadcrumbs"=> $breadcrumbs,
        ],
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => (int)$dbItems->SelectedRowsCount(),
            'limit' => $limit,
        ],
    ];
}

echo "<pre>";
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
echo "</pre>";