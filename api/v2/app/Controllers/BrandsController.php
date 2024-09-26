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

class BrandsController
{

  public function __construct() {}

  public static function getBrands($request)
  {
    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = $request->getQueryParams()['page'] ?? 1;
    $q = $request->getQueryParams()['q'];

    $_items = \CIBlockElement::GetList(
      [$orderBy => $order],
      ['IBLOCK_ID' => $_ENV["ID_IBLOCK_BRANDS"], 'ACTIVE' => 'Y', 'NAME' => "%" . $q . "%"],
      false,
      ['nPageSize' => $offset, 'iNumPage' => $page],
      ["*"]
    );
    $items = [];

    $total_items = (int)$_items->SelectedRowsCount();

    $total_pages = round($total_items / $offset);
    $total_pages = $total_pages == 0 ? 1 : $total_pages;

    while ($item = $_items->fetch()) {
      $items[] = [
        ...$item,
        "link" => "/company/brands/" . $item["CODE"] . "/",
        "image"=> CFile::GetPath($item["PREVIEW_PICTURE"])
      ];
    }

    return [
      "meta" => [
        "meta_title" => "Brands",
        "meta_description" => "Brands",
      ],
      'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'offset' => $offset,
      ],
      "brands" => $page > $total_pages ? [] : $items,
    ];
  }
}
