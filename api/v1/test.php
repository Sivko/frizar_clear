<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");
$id = 24487;
//$id = 26801; // ваш ID элемента
$product = \CIBlockElement::GetList(
    [],
    ['ID' => $id, 'IBLOCK_ID' => 9,],
    false,
    [],
    [],
)->Fetch();

$props = \CIBlockElement::GetProperty($product['IBLOCK_ID'], $product['ID'], ['sort' => 'asc']);
while ($prop = $props->Fetch()) {
    // Добавляем свойство в массив
    $product['PROPERTIES'][$prop['CODE']] = $prop['VALUE'];
}

echo "<pre>";
echo json_encode($product, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
