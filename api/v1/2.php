<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

use Bitrix\Catalog\PriceTable;
use Bitrix\Catalog\ProductTable;
use Bitrix\Main\Loader;

Loader::includeModule('catalog');

$iblockId = 9; // ID инфоблока

$elementId = 22114; // ID элемента
$elementId = 24081;

// Получаем информацию о продукте
$product = ProductTable::getList([
    'filter' => ['ID' => $elementId],
])->fetch();

// Получаем цены товара
$priceList = PriceTable::getList([
    'select' => ['*'],
    'filter' => []
])->fetchAll();
header('Content-Type: application/json');
echo json_encode($priceList, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
