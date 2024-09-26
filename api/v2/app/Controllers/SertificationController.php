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
use CFile;


\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class SertificationController
{

  public function __construct() {}

  public static function get($request)
  {

    $_items = \CIBlockElement::GetList([], ['IBLOCK_ID' => $_ENV["ID_IBLOCK_SERTIFICATION"], 'ACTIVE' => 'Y'], false, [], ["*"]);
    $items = [];

    $total_items = (int)$_items->SelectedRowsCount();

    $total_pages = round($total_items / 40);
    $total_pages = $total_pages == 0 ? 1 : $total_pages;

    while ($item = $_items->fetch()) {
      $items[] = [
        ...$item,
        "image" => CFile::GetPath($item["PREVIEW_PICTURE"])
      ];
    }

    return [
      "meta" => [
        "meta_title" => "Наши Сертификаты",
        "meta_description" => "Наши Сертификаты",
      ],
      'pagination' => [
        'current_page' => 1,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'offset' => 40,
      ],
      "sertifications" =>  $items,
    ];
  }
}
