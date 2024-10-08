<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");

use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Catalog;

if (!Loader::includeModule('sale')) {
  echo json_encode(['error' => 'Модуль sale не подключен.']);
  exit;
}

$fuserId = Sale\Fuser::getId();
$siteId = \Bitrix\Main\Context::getCurrent()->getSite();

$basket = Sale\Basket::loadItemsForFUser($fuserId, $siteId);

$productId = isset($_GET['productId']) ? $_GET['productId'] : null;
$quantity = isset($_GET['quantity']) ? $_GET['quantity'] : 1;



function print_basket($basket, $fuserId)
{
  $countItems = 0;
  $total = 0;
  foreach ($basket as $basketItem) {
    $countItems = $countItems + $basketItem->getQuantity();
    $total = $total + $basketItem->getPrice();
    $basketItems[] = [
      'PRODUCT_ID' => $basketItem->getProductId(),
      'NAME' => $basketItem->getField('NAME'),
      'QUANTITY' => $basketItem->getQuantity(),
      'PRICE' => $basketItem->getPrice(),
      'CURRENCY' => $basketItem->getCurrency(),
      'uID' => $fuserId,
    ];
  }
  // Выводим корзину в формате JSON
  echo json_encode(["items" => $basketItems, "total" => $total, "count" => $countItems], JSON_UNESCAPED_UNICODE);
}

switch ($action) {
  case 'add':
    //проверка, есть ли в корзине
    $basketItems = $basket->getExistsItems('catalog', $productId);
    if (!empty($basketItems)) {
      // Если товар уже есть в корзине, обновляем количество
      $basketItem = reset($basketItems);
      $basketItem->setField('QUANTITY', $quantity);
    } else {
      $item = $basket->createItem('catalog', $productId);
      $item->setFields([
        'QUANTITY' => $quantity,
        'CURRENCY' => \Bitrix\Currency\CurrencyManager::getBaseCurrency(),
        'LID' => $siteId,
        'PRODUCT_PROVIDER_CLASS' => 'CCatalogProductProvider',
      ]);
    }
    $basket->save();
    print_basket($basket, $fuserId);
    break;

  case 'update':
    $basketItems = $basket->getExistsItems('catalog', $productId);
    if (!empty($basketItems)) {
      $basketItem = reset($basketItems);
      $basketItem->setField('QUANTITY', $quantity);
      print_basket($basket, $fuserId);
    }
    print_basket($basket, $fuserId);
    break;

  case 'delete':
    $basketItems = $basket->getExistsItems('catalog', $productId);
    if (!empty($basketItems)) {
      $basketItem = reset($basketItems);
      $result = $basketItem->delete();
      $basket->save();
      print_basket($basket, $fuserId);
    }
    print_basket($basket, $fuserId);
    break;
  default:
    $basketItems = [];
    print_basket($basket, $fuserId);
}
