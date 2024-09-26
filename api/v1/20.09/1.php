<?php

require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

$iblockId = 9;
$dateTwoMonthsAgo = date("d.m.Y H:i:s", strtotime("-1 months"));

$productItems = \CIBlockElement::GetList(
    [],
    [
        'IBLOCK_ID' => $iblockId,
        'ACTIVE' => 'Y',
        '>=DATE_CREATE' => $dateTwoMonthsAgo,
    ],
    false,
    false,
    ["ID", "NAME", "DATE_CREATE"],
);

$products = [];
while ($item = $productItems->Fetch()) {
    $products[] = $item;
}

header('Content-Type: application/json');
echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
