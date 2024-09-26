<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Psr\Http\Message\ResponseInterface as Response;
use App\Response\CustomResponse;

use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;
use CFile;
use CIBlockSection;

use Bitrix\Iblock\InheritedProperty\IblockValues; //для инфоблока ($iblockId); 
use Bitrix\Iblock\InheritedProperty\SectionValues; //для раздела ($iblockId,$sectionId); 
use Bitrix\Iblock\InheritedProperty\ElementValues; //для элемента ($iblockId,$elementId); 


\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class ArticleController
{

  public static function getArticles($request)
  {
    // $slug = $request->getQueryParams()['slug'];
    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = $request->getQueryParams()['page'] ?? 1;
    $q = $request->getQueryParams()['q'];

    $_items = \CIBlockElement::GetList(
      [$orderBy => $order],
      ['IBLOCK_ID' => $_ENV["ID_IBLOCK_ARTICLES"], 'ACTIVE' => 'Y', 'NAME' => "%" . $q . "%"],
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
        "link" => "/stati/" . $item["CODE"] . "/",
        "image"=> CFile::GetPath($item["PREVIEW_PICTURE"])
      ];
    }

    return [
      "meta" => [
        "meta_title" => "Поиск товаров",
        "meta_description" => "Поиск товаров",
      ],
      'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_items' => $total_items,
        'offset' => $offset,
      ],
      "articles" => $page > $total_pages ? [] : $items,
    ];
  }

  public static function getArticle($slug)
  {
    // $slug = $request->getQueryParams()['slug'];
    $items = \CIBlockElement::GetList(
      [],
      ['IBLOCK_ID' => $_ENV["ID_IBLOCK_ARTICLES"], 'ACTIVE' => 'Y', 'CODE' => $slug],
      false,
      [],
      ["*"]
    );
    $item = $items->Fetch();

    if (!$item) return [];

    $meta = new ElementValues($_ENV["ID_IBLOCK_ARTICLES"], $item["ID"]);
    $meta = $meta->getValues();

    return [
      "article" => [
        ...$item,
        "link" => "/stati/" . $item["CODE"] . "/"
      ],
      "meta" => [
        "meta_title" => $meta["ELEMENT_META_TITLE"] ?? $item["NAME"],
        "meta_description" => $meta["ELEMENT_META_DESCRIPTION"] ?? "",
      ],
    ];
  }
}
