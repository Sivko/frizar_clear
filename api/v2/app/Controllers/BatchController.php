<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Requests\CustomRequestHandler;
use Bitrix\Sale;
use Bitrix\CSaleBasket;

use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;
// use InheritedProperty\ElementTemplates;
use Bitrix\Iblock\InheritedProperty;


\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class BatchController
{
  static function updateProduct($request)
  {

    $id = CustomRequestHandler::getParam($request, "id");
    $metaTitle = CustomRequestHandler::getParam($request, "metaTitle");
    $metaDescription = CustomRequestHandler::getParam($request, "metaDescription");
    $h1 = CustomRequestHandler::getParam($request, "h1");

    // return "updated".$id.$metaTitle;
    $ipropTemplates = new InheritedProperty\ElementTemplates($_ENV["NEXT_PUBLIC_ID_PRODUCT"], $id);
    //Установить шаблон для элемента
    $ipropTemplates->set(array(
      "ELEMENT_META_TITLE" => $metaTitle,
      "ELEMENT_META_DESCRIPTION" => $metaDescription
    ));

    return "updated?";
  }
}
// [
// "IPROPERTY_TEMPLATES" => [
// "ELEMENT_META_TITLE" => $element["meta_title"],
// "ELEMENT_META_DESCRIPTION" => $element["meta_title"],
// ]
// ];