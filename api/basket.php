<?php
use Bitrix\Main\Loader;
use Bitrix\Sale;
use Bitrix\Catalog;

header('Content-Type: application/json');
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (!Loader::includeModule('sale')) {
    echo json_encode(['error' => 'Модуль sale не подключен.']);
    exit;
}

$action = isset($_GET['action']) ? $_GET['action'] : 'GET';
$fuserId = Sale\Fuser::getId();
$siteId = \Bitrix\Main\Context::getCurrent()->getSite();

$basket = Sale\Basket::loadItemsForFUser($fuserId, $siteId);

$productId = isset($_GET['productId']) ? $_GET['productId'] : null;
$quantity = isset($_GET['quantity']) ? $_GET['quantity'] : 0;


function print_basket($basket) {
    foreach($basket as $basketItem) {
        $basketItems[] = [
            'PRODUCT_ID' => $basketItem->getProductId(),
            'NAME' => $basketItem->getField('NAME'),
            'QUANTITY' => $basketItem->getQuantity(),
            'PRICE' => $basketItem->getPrice(),
            'CURRENCY' => $basketItem->getCurrency(),
        ];
    }
    // Выводим корзину в формате JSON
    header('Content-Type: application/json');
    echo json_encode($basketItems, JSON_UNESCAPED_UNICODE); 
}

switch ($action) {
    case 'GET':
        $basketItems = [];
        print_basket($basket);
        break;
    
    case 'POST':
        //проверка, есть ли в корзине
        $basketItems = $basket->getExistsItems('catalog', $productId);
        if (!empty($basketItems)) {
            // Если товар уже есть в корзине, обновляем количество
            $basketItem = reset($basketItems);
            $basketItem->setField('QUANTITY', $basketItem->getQuantity() + $quantity);
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
        print_basket($basket);
        break;

    case 'UPDATE':
        $basketItems = $basket->getExistsItems('catalog', $productId);
        if (!empty($basketItems)) {
            $basketItem = reset($basketItems);
            $basketItem->setField('QUANTITY', $quantity);
            print_basket($basket);
        } else {
            // Выводим корзину в формате JSON
            header('Content-Type: application/json');
            echo json_encode(['result'=>'Товар не найден в корзине'], JSON_UNESCAPED_UNICODE);
        }

        break;

    case 'DELETE':
        $basketItems = $basket->getExistsItems('catalog', $productId);
        if (!empty($basketItems)) {
            $basketItem = reset($basketItems);
            $result = $basketItem->delete();
            $basket->save();
            print_basket($basket);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['result'=>'Товар не найден в корзине'], JSON_UNESCAPED_UNICODE);
        }
        break;
    default:
        echo "Что-то пошло не так, такой action не существует";
        break;
}
?>
