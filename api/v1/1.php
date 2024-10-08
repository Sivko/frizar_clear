<?php

use Bitrix\Main\Loader;
use Bitrix\Iblock\ElementTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Catalog\StoreProductTable;

require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

Loader::includeModule('iblock');
Loader::includeModule('catalog');

$iblockId = 9; // ID инфоблока с товарами
$products = [];

// Получаем все элементы инфоблока
$elementsResult = ElementTable::getList([
    'select' => ['ID', 'NAME', 'CODE'],
    'filter' => [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        //'CODE' => 'mikrometr_0_25mm_0_1_0_001_0_00005_tsifrovoy_s_zashchitoy_ip65_4410_1105_a_dasqua',
        'CODE' => 'sverlo_0_4x20x5_spiralnoe_118_hsco_din338_305_0_400',

    ]
]);

while ($element = $elementsResult->fetch()) {
    // Получаем количество товара из ProductTable
    $product = ProductTable::getList([
        'filter' => ['ID' => $element['ID']], // Фильтрация по наличию на складе
        'select' => ['QUANTITY']
    ])->fetch();

    if ($product) {
        // Получаем свойства элемента через GetProperty
        $properties = [];
        $propertyResult = \CIBlockElement::GetProperty($iblockId, $element['ID'], [], []);

        while ($property = $propertyResult->Fetch()) {
            $properties[$property['CODE']] = $property['VALUE'];
        }

        // Получаем количество на складах (для многоскладского учета)
        $storeQuantities = [];
        $storeResult = StoreProductTable::getList([
            'filter' => ['PRODUCT_ID' => $element['ID']],
            'select' => ['STORE_ID', 'AMOUNT']
        ]);

        while ($store = $storeResult->fetch()) {
            $storeQuantities[] = [
                'STORE_ID' => $store['STORE_ID'],
                'AMOUNT' => $store['AMOUNT']
            ];
        }

        // Формируем данные товара
        $element['PROPERTIES'] = $properties;
        $element['QUANTITY'] = $product['QUANTITY'];
        $element['STORES'] = $storeQuantities;

        // Добавляем элемент в массив товаров
        $products[] = $element;
    }
}

// Возвращаем данные в формате JSON
header('Content-Type: application/json');
echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
