<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use App\Validation\Validator;
use Bitrix\Iblock\InheritedProperty\ElementValues;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;
use CUser;
use CIBlockPropertyEnum;
use CIBlockElement;
use CCatalogProduct;
use CFile;
use CIBlockSection;

use Bitrix\Iblock\InheritedProperty\SectionValues; //для раздела ($iblockId,$sectionId); 


\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class BrandsController
{

  public function __construct() {}

  public static function getAnyBrands($request, $response)
  {
    $resp = new CustomResponse();

    $IBLOCK_ID = $request->getQueryParams()['IBLOCK_ID'];
    $PROPERTY_ID = $request->getQueryParams()['PROPERTY_ID'];

    $_items = CIBlockElement::GetList([], ["IBLOCK_ID"=>$IBLOCK_ID, "ACTIVE" => "Y"], false, [], ["NAME"]);

    while ($item = $_items->GetNext()) {
      $brandNames[] = $item["NAME"];
      $items[] = $item;
    };

    //add Any brands
    $data = CIBlockPropertyEnum::GetList(["SORT" => "ASC", "VALUE" => "ASC"], ["PROPERTY_ID" => $PROPERTY_ID]);
    while ($item = $data->GetNext()) {
      // if (!in_array($item["VALUE"], $brandNames)) {
        $result[] = [
          "NAME" => $item["VALUE"],
          "link" => "/company/brands/" . urlencode($item["VALUE"])
        ];
      // }
    }


    return $resp->is200Response($response, $result);
  }
}
