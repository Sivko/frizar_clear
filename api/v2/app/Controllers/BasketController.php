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
use CUser;
use Bitrix\Sale;
use Bitrix\CSaleBasket;
use Bitrix\Sale\PaySystem\Manager;

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
    $user = new CUser;
    $fuser = Sale\Fuser::getId();
    $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    $name = CustomRequestHandler::getParam($request, "name");
    $phone = CustomRequestHandler::getParam($request, "phone");
    $comment = CustomRequestHandler::getParam($request, "comment");
    $city = CustomRequestHandler::getParam($request, "city");
    $address = CustomRequestHandler::getParam($request, "address");
    $inn = CustomRequestHandler::getParam($request, "inn");
    $kpp = CustomRequestHandler::getParam($request, "kpp");
    $personalTypeId = CustomRequestHandler::getParam($request, "personalTypeId");

    // $order = \Bitrix\Sale\Order::create('s1', $user->isAuthorized() ? $user->GetID() : 1, 'RUB');

    // $order->setPersonTypeId(1);
    // $order->setBasket($basket);
    // $propertyCollection = $order->getPropertyCollection();
    // $phoneProp = $propertyCollection->getPhone();
    // $phoneProp->setValue($phone);
    // $nameProp = $propertyCollection->getPayerName();
    // $nameProp->setValue($name);

    // $r = $order->save();
    // if (!$r->isSuccess()) {
    //   return $r->getErrorMessages();
    // }
    // return ["status" => "OK"];

    // Допустим некоторые поля приходит в запросе
    $siteId = "s1";
    $currencyCode = "RUB";

    // Создаёт новый заказ
    $order = \Bitrix\Sale\Order::create($siteId, $user->isAuthorized() ? $user->GetID() : 1);
    $order->setPersonTypeId($personalTypeId);
    $order->setField('CURRENCY', $currencyCode);
    if ($comment) {
      $order->setField('USER_DESCRIPTION', $comment); // Устанавливаем поля комментария покупателя
    }

    $order->setBasket($basket);

    // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)
    // $shipmentCollection = $order->getShipmentCollection();
    // $shipment = $shipmentCollection->createItem();
    // $service = Delivery\Services\Manager::getById(Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
    // $shipment->setFields(array(
    //   'DELIVERY_ID' => $service['ID'],
    //   'DELIVERY_NAME' => $service['NAME'],
    // ));
    // $shipmentItemCollection = $shipment->getShipmentItemCollection();
    // $shipmentItem = $shipmentItemCollection->createItem($item);
    // $shipmentItem->setQuantity($item->getQuantity());

    // Создаём оплату со способом #1
    $paymentCollection = $order->getPaymentCollection();
    $payment = $paymentCollection->createItem();
    $paySystemService = Manager::getObjectById(1);
    $payment->setFields(array(
      'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
      'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
    ));

    // Устанавливаем свойства
    $propertyCollection = $order->getPropertyCollection();
    $phoneProp = $propertyCollection->getPhone();
    $phoneProp->setValue($phone);
    $nameProp = $propertyCollection->getPayerName();
    $nameProp->setValue($name);
    $adressProperty = $propertyCollection->getAddress();
    $adressProperty->setValue($address);

    // Сохраняем
    $order->doFinalAction(true);
    $result = $order->save();
    $orderId = $order->getId();
    return ["RULST" => $result, "ID" => $orderId];
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
        $props = \CIBlockElement::GetList([], ["ID" => $basketItem->getProductId()], false, [], ["NAME", "CODE"])->Fetch();
        $basketArray[] = [
          'PRODUCT_ID' => $basketItem->getProductId(),
          'property' => $props,
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
