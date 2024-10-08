<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

$slug = isset($_GET['slug']) ? $_GET['slug'] : null;

if ($slug) {
  $product = \CIBlockElement::GetList(
    [],
    ['CODE'=>$slug, 'IBLOCK_ID'=> 17, ],
    false,
    ['nPageSize' => $limit, 'iNumPage' => $page],
    ['ID', 'CODE', 'IBLOCK_SECTION_ID'],
  )->Fetch();

  $props = \CIBlockElement::GetProperty($product['IBLOCK_ID'], $product['ID'], ['sort' => 'asc']);
    while ($prop = $props->Fetch()) {
        // Добавляем свойство в массив
        $product['PROPERTIES'][$prop['CODE']] = $prop['VALUE'];
    }
  $section = CIBlockSection::GetByID($product["IBLOCK_SECTION_ID"])->GetNext();
  $response = [
    "product" => $product,
    "meta" => [
      "metatitle"=> $section['NAME'],
      "metadescription"=> '',
      "title"=> $product[''],
      "description"=> "Описание на самой странице",
      "broadcrumbs"=> create_url($product['ID']),
    ],
  ];
  echo json_encode($response, JSON_UNESCAPED_UNICODE);

} else {
  echo json_encode(['product'=>[]], JSON_UNESCAPED_UNICODE);
}


function create_url($productId) {
  $product = \CIBlockElement::GetList(
    [],
    ['ID'=>$productId, 'IBLOCK_ID'=> 17],
    false,
    [],
  )->Fetch();

  $sectionId = $product['IBLOCK_SECTION_ID'];
  $url = '/';
  $broadcrumbs = [
    ['name'=>$product['NAME'],
    'href'=>$url,]
  ];

  while ($sectionId) {
    $section = CIBlockSection::GetByID($sectionId)->GetNext();
    $broadcrumbs[] = ['name'=>$section['NAME'], 'href' => '/' . $section['CODE'] . $url];
    $url = '/' . $section['CODE'] . $url; 
    $sectionId = $section['IBLOCK_SECTION_ID'];
  }
  return $broadcrumbs;
}
