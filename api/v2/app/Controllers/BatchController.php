<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Requests\CustomRequestHandler;
use Bitrix\Sale;
use Bitrix\CSaleBasket;

use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;

\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class BatchController {
  static function updateProduct($request){

    // $productId = CustomRequestHandler::getParam($request, "productId");
    // $quantity = CustomRequestHandler::getParam($request, "quantity");

    // $fuser = Sale\Fuser::getId();
    // $basket = Sale\Basket::loadItemsForFUser($fuser, "s1");

    // $basketItem = self::findItem($productId, $basket);

    // if ($quantity < 1) {
    //   $basketItem->delete();
    //   $basket->save();
    //   return "removed";
    // }
    // $basketItem->setField('QUANTITY', $quantity);
    // $basket->save();
    return "updated";

  }
}
// [
// "IPROPERTY_TEMPLATES" => [
// "ELEMENT_META_TITLE" => $element["meta_title"],
// "ELEMENT_META_DESCRIPTION" => $element["meta_title"],
// ]
// ];