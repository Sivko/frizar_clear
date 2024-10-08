<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");


// // use App\Models\User;
use App\Requests\CustomRequestHandler;

// use App\Requests\CustomRequestHandler;
// use App\Response\CustomResponse;
// use App\Validation\Validator;
// use Psr\Http\Message\RequestInterface as Request;
// use Psr\Http\Message\ResponseInterface as Response;
// use Respect\Validation\Validator as v;
// use CUser;
use Bitrix\Sale;
use Bitrix\CSaleBasket;


use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;

\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class BasketController
{

  protected $fuser;
  // protected $basket;


  // public function __construct()
  // {
  //   $this->fuser = Sale\Fuser::getId(); //Идентификатор покупателя текущего пользователя
  // }

  public static function getBasket()
  {
    // CSaleBasket::DeleteAll(CSaleBasket::GetBasketUserID());


    $fuser = Sale\Fuser::getId();
    $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    // foreach ($basket as $basketItem) {
    //   $basketItem->delete();
    //   $basketItem->save();
    // }
    // $basket->save();

    return self::printBasket($basket);
  }

  public static function addToBasket($request)
  {
    $productId = CustomRequestHandler::getParam($request, "productId");
    $quantity = CustomRequestHandler::getParam($request, "quantity");

    $fuser = Sale\Fuser::getId();
    $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    $product = array('PRODUCT_ID' => $productId, 'QUANTITY' => $quantity);

    $result = \Bitrix\Catalog\Product\Basket::addProductToBasket($basket, $product, array('SITE_ID' => "s1"));
    if ($result->isSuccess()) {
      $basket->save();
      return (["message" => "Товар успешно добавлен", "success" => true]);
    } else {
      return ["message" => implode(',', $result->getErrorMessages()), "success" => false];
    }
  }

  public static function findItem($productId, $basket)
  {
    foreach ($basket as $basketItem) {
      if ($basketItem->getProductId() == $productId) return $basketItem;
    }
  }

  public static function updateProduct($request)
  {
    $productId = CustomRequestHandler::getParam($request, "productId");
    $quantity = CustomRequestHandler::getParam($request, "quantity");

    $fuser = Sale\Fuser::getId();
    $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    $basketItem = self::findItem($productId, $basket);

    if ($quantity < 1) {
      $basketItem->delete();
      $basket->save();
      return "removed";
    }
    $basketItem->setField('QUANTITY', $quantity);
    $basket->save();
    return "updated";
  }


  public static function createOrder($request)
  {
    $fuser = Sale\Fuser::getId();
    $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    $order = \Bitrix\Sale\Order::create('s1', 1, 'RUB');
    $order->setPersonTypeId(1);
    $order->setBasket($basket);
    $r = $order->save();
    if (!$r->isSuccess()) {
      return $r->getErrorMessages();
    }
    return ["status" => "OK"];
  }

  public static function printBasket($basket)
  {
    $countItems = 0;
    $total = 0;
    $basketItems = [];
    foreach ($basket as $basketItem) {
      $countItems = $countItems + $basketItem->getQuantity();
      $total = $total + $basketItem->getFinalPrice();
      $product =  CatalogController::getFirstProductByFilter(["ID" => $basketItem->getProductId()]);
      $basketItems[] = [
        'PRODUCT_ID' => $basketItem->getProductId(),
        'QUANTITY' => $basketItem->getQuantity(),
        'PRICE' => $basketItem->getPrice(),
        'TOTAL_PRICE' => $basketItem->getFinalPrice(),
        'CURRENCY' => $basketItem->getCurrency(),
        // 'GOGO' => $basketItem->getField('MODULE'),
        'DETAIL_PRODUCT' => $product,
      ];
    }
    // Выводим корзину в формате JSON
    return (["items" => $basketItems, "total" => $total, "count" => $countItems]);
  }

  public static function getBasketByOrderId($orderId)
  {
    $order = \Bitrix\Sale\Order::load($orderId);
    if ($order) {
      $basket = $order->getBasket();
      $basketArray = [];
      foreach ($basket as $basketItem) {
        $basketArray[] = [
          'PRODUCT_ID' => $basketItem->getProductId(),
          'QUANTITY' => $basketItem->getQuantity(),
          'PRICE' => $basketItem->getPrice(),
          'TOTAL_PRICE' => $basketItem->getFinalPrice(),
          'CURRENCY' => $basketItem->getCurrency(),
          // 'DETAIL_PRODUCT' => $product,
        ];
      }
      return $basketArray;
    } else {
      return ["status" => "404", "message" => "Заказ не найден!"];
    }
  }
}
