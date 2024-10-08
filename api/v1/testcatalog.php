<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

CModule::IncludeModule("iblock");

$iblockId = 9; // ID инфоблока с товарами
$products = [];

// Получаем все элементы инфоблока
$elementsResult = CIBlockElement::GetList(
    [], // сортировка
    ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CODE' => "mikrometr_0_25mm_0_1_0_001_0_00005_tsifrovoy_s_zashchitoy_ip65_4410_1105_a_dasqua"], // фильтр (например, только активные элементы)
    false, // группировка
    false, // пагинация, если нужна
    [] // поля, которые нужно извлечь
);

// Обрабатываем каждый элемент
while ($element = $elementsResult->Fetch()) {
    // Получаем свойства элемента
    $elementProperties = CIBlockElement::GetProperty($iblockId, $element['ID'], [], []);

    $properties = [];
    while ($property = $elementProperties->Fetch()) {
        if (true) {
            $properties[$property['CODE']] = $property['VALUE'];
        }
    }

    // Добавляем свойства к элементу
    $element['PROPERTIES'] = $properties;

    if ($element['PROPERTIES']) {
        // Сохраняем элемент с его свойствами в массив продуктов
        $products[] = $element;
    }
}

// Возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
