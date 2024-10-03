<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use Psr\Http\Message\ResponseInterface as Response;
use App\Response\CustomResponse;

use Bitrix\Main\Loader;
use Bitrix\Catalog;
use Bitrix\Sale\Basket;
use CIBlockElement;
use CCatalogProduct;
use CFile;
use CIBlockSection;

use Bitrix\Iblock\InheritedProperty\IblockValues; //для инфоблока ($iblockId); 
use Bitrix\Iblock\InheritedProperty\SectionValues; //для раздела ($iblockId,$sectionId); 
use Bitrix\Iblock\InheritedProperty\ElementValues; //для элемента ($iblockId,$elementId); 
use CIBlock;

\Bitrix\Main\Loader::includeModule("sale");
\Bitrix\Main\Loader::includeModule("catalog");

class CatalogController
{

  public static function getImages($elements)
  {
    return [
      [CFile::GetPath(956444)]
    ];
    if (!count($elements)) return [];
    // return array_map(function ($element) {
    //   // $obj = CFile::GetFileArray($element);
    //   // $link = "/upload/".$obj["SUBDIR"].$obj["ORIGINAL_NAME"];
    //   // return CFile::GetFileSRC($element);
    //   return CFile::GetByID($element);
    // }, $elements);
  }

  public static function getNotNullProperties($id, $limit = false, $iblockId = false, $filter = [])
  {
    $resp = new CustomResponse();
    $items = CIBlockElement::GetProperty($iblockId ?? $_ENV["ID_IBLOCK_PRODUCT"], $id, 'sort', 'asc', $filter);
    $properties = [];
    while ($item = $items->Fetch()) {
      if ($item["NAME"] == "PDF" && $item["VALUE"]) {
        $properties[] = ["NAME" => "PDF", "VALUE" => CFile::GetPath($item["VALUE"])];
        // $item["VALUE_PDF"] = ;
        // continue;
      }
      // if ($item["NAME"] == "Производитель") {
      //   $properties[] = ["NAME" => "Производитель", "VALUE" => $item["VALUE_ENUM"]];
      //   continue;
      // }
      // if ($item["VALUE"])
      //   $properties[] = ["NAME" => $item["NAME"] ?? $item["DESCRIPTION"], "VALUE" => $item["VALUE"]];
      $properties[] = $item;
    }
    return $limit ? array_slice($properties, 0, $limit) : $properties;
  }


  public static function getCodeCatalogById($id)
  {
    $item =  \CIBlockSection::GetList([], ['ID' => $id], false, ['nPageSize' => 1], ["CODE"]);
    return $item->Fetch()["CODE"];
  }

  public static function checkObject($request)
  {
    $slug = $request->getQueryParams()['slug'];
    $item =  \CIBlockSection::GetList([], ['CODE' => $slug], false, ['nPageSize' => 1], ["ID"]);
    if ($item->Fetch()["ID"]) {
      return ["result" => "catalog"];
    }
    $item =  \CIBlockElement::GetList([], ['CODE' => $slug], false, ['nPageSize' => 1], ["ID"]);
    if ($item->Fetch()["ID"]) {
      return ["result" => "product"];
    }
    return ["result" => "not-found"];
  }

  public static function getFirstProductByFilter($filter)
  {
    $product = \CIBlockElement::GetList(
      [],
      ['IBLOCK_ID' => $_ENV["ID_IBLOCK_PRODUCT"], 'ACTIVE' => 'Y', ...$filter],
      false,
      [],
      ["*", ...explode(",", $_ENV["PRODUCT_POPERTY_FIELDS"])]
      // [""]
    );
    $item = $product->Fetch();

    if (!$item) return null;

    $meta = new ElementValues($_ENV["ID_IBLOCK_PRODUCT"], $item["ID"]);
    $meta = $meta->getValues();

    $propertyValue = CIBlockElement::GetProperty($_ENV["ID_IBLOCK_PRODUCT"], $item["ID"], array(), array());
    while ($value = $propertyValue->fetch()) {
      $properties[] = $value;
    }

    return [
      "product" => [
        ...$item,
        "storage" => CCatalogProduct::GetByID($item["ID"]),
        "properties" => $properties,
        "image" => CFile::GetPath($item["DETAIL_PICTURE"]),
        "images" => self::getImages($item["PROPERTY_MORE_PHOTO_PROPERTY_VALUE_ID"]),
        "price" => $item["PROPERTY_MINIMUM_PRICE_VALUE"],
        "link" => "/catalog/" . self::getCodeCatalogById($item["IBLOCK_SECTION_ID"]) . "/" . $item["CODE"] . "/"
      ],
      'breadcrumbs' => self::getBreadcrumb($item["IBLOCK_SECTION_ID"]),
      "meta" => [
        "meta_title" => $meta["ELEMENT_META_TITLE"] ?? $item["NAME"],
        "meta_description" => $meta["ELEMENT_META_DESCRIPTION"] ?? "",
      ],
    ];
  }


  public static function getCatalogOrProductBySlug($request)
  {

    $slug = $request->getQueryParams()['slug'];

    if (self::checkObject($request)["result"] == "not-found") return ["status" => "not-found"];
    if (self::checkObject($request)["result"] == "product") return self::getFirstProductByFilter(["CODE" => $slug]);

    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = $request->getQueryParams()['page'] ?? 1;
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];

    $catalog = CIBlockSection::GetList([], ['IBLOCK_ID' => $_ENV["ID_IBLOCK_PRODUCT"], 'CODE' => $slug], false, ['*'])->Fetch();
    $catalog_id = $catalog["ID"];


    $productItems = \CIBlockElement::GetList(
      [$orderBy => $order],
      [
        'IBLOCK_ID' => $_ENV["ID_IBLOCK_PRODUCT"],
        'ACTIVE' => 'Y',
        // 'IBLOCK_SECTION_ID' => $catalog_id,
        'SECTION_ID' => $catalog_id,
        'INCLUDE_SUBSECTIONS' => "Y"
        // '>=IBLOCK_SECTION.LEFT_MARGIN' => 587,
        // '<=IBLOCK_SECTION.RIGHT_MARGIN' => 602,
      ],
      false,
      ['nPageSize' => $offset, 'iNumPage' => $page],
      // ["*", "DETAIL_PICTURE", "PROPERTY_MORE_PHOTO", "PROPERTY_MINIMUM_PRICE"]
      ["*", ...explode(",", $_ENV["PRODUCT_POPERTY_FIELDS"])]
    );
    $products = [];
    $total_products = (int)$productItems->SelectedRowsCount();
    $total_pages = (int)(ceil($total_products / $offset) ?? 1);

    while ($item = $productItems->fetch()) {

      $propertyValue = CIBlockElement::GetProperty($_ENV["ID_IBLOCK_PRODUCT"], $item["ID"], array(), array());
      while ($value = $propertyValue->fetch()) {
        $properties[] = $value;
      }

      $products[] = [
        ...$item,
        "storage" => CCatalogProduct::GetByID($item["ID"]),
        "properties" => $properties,
        "image" => CFile::GetPath($item["DETAIL_PICTURE"]),
        "images" => self::getImages($item["PROPERTY_MORE_PHOTO_VALUE"]),
        "price" => $item["PROPERTY_MINIMUM_PRICE_VALUE"],
        "link" => "/catalog/" . self::getCodeCatalogById($item["IBLOCK_SECTION_ID"]) . "/" . $item["CODE"] . "/",
      ];
    }

    $meta = new SectionValues($_ENV["ID_IBLOCK_PRODUCT"], $catalog_id);
    $meta = $meta->getValues();

    return [
      "meta" => [
        "meta_title" => $meta["SECTION_META_TITLE"] ?? $catalog["NAME"],
        "meta_description" => $meta["SECTION_META_DESCRIPTION"] ?? "",
      ],
      "catalog" => $catalog,
      'breadcrumbs' => self::getBreadcrumb($catalog_id),
      'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_products' => $total_products,
        'offset' => $offset,
      ],
      "products" => $page > $total_pages ? [] : $products,
    ];


    return [
      "meta" => [
        "meta_title" => $meta["SECTION_META_TITLE"] ?? $catalog["NAME"],
        "meta_description" => $meta["SECTION_META_DESCRIPTION"] ?? "",
      ],
      "catalog" => $catalog,
      'breadcrumbs' => self::getBreadcrumb($catalog_id),
      'pagination' => [
        'current_page' => $page,
        // 'total_pages' => $total_pages,
        // 'total_products' => $total_products,
        'offset' => $offset,
      ],
      "products" => $page > 4 ? [] : $products,
    ];
  }



  public static function getCatalogBySearch($request)
  {

    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = $request->getQueryParams()['page'] ?? 1;
    $q = $request->getQueryParams()['q'];

    $productItems = \CIBlockElement::GetList(
      [$orderBy => $order],
      ['IBLOCK_ID' => $_ENV["ID_IBLOCK_PRODUCT"], 'ACTIVE' => 'Y', 'NAME' => "%" . $q . "%"],
      false,
      ['nPageSize' => $offset, 'iNumPage' => $page],
      // ["*", "DETAIL_PICTURE", "PROPERTY_MORE_PHOTO", "PROPERTY_MINIMUM_PRICE"]
      ["*", ...explode(",", $_ENV["PRODUCT_POPERTY_FIELDS"])]
      // []
    );
    $products = [];
    $total_products = (int)$productItems->SelectedRowsCount();

    $total_pages = round($total_products / $offset);
    $total_pages = $total_pages == 0 ? 1 : $total_pages;

    while ($item = $productItems->fetch()) {

      $propertyValue = CIBlockElement::GetProperty($_ENV["ID_IBLOCK_PRODUCT"], $item["ID"], array(), array());
      while ($value = $propertyValue->fetch()) {
        $properties[] = $value;
      }

      $products[] = [
        ...$item,
        "storage" => CCatalogProduct::GetByID($item["ID"]),
        "properties" => $properties,
        "image" => CFile::GetPath($item["DETAIL_PICTURE"]),
        "images" => self::getImages($item["PROPERTY_MORE_PHOTO_VALUE"]),
        "price" => $item["PROPERTY_MINIMUM_PRICE_VALUE"],
        "link" => "/catalog/" . self::getCodeCatalogById($item["IBLOCK_SECTION_ID"]) . "/" . $item["CODE"] . "/"
      ];
    }

    return [
      "meta" => [
        "meta_title" => "Поиск товаров",
        "meta_description" => "Поиск товаров",
      ],
      // 'breadcrumbs' => self::getBreadcrumb($item["ID"]),
      'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_products' => $total_products,
        'offset' => $offset,
      ],
      "products" => $page > $total_pages ? [] : $products,
    ];
  }

  public static function test($request)
  {
    return "AAAAA)))";
  }


  public static function getBreadcrumb($sectionId)
  {
    // $sectionId = $request->getQueryParams()['sectionId'];
    // $sectionId = 376;
    $getNavChain = CIBlockSection::GetNavChain(false, $sectionId, ["CODE", "NAME"]);
    while ($item = $getNavChain->fetch()) {
      $result[] = [...$item];
    }
    foreach ($result as $key => $item) {
      if ($key != 0)
        $result[$key]["CODE"] = $result[$key - 1]["CODE"] . '/' . $result[$key]["CODE"];
    }
    // foreach ($result as $key => $item) {
    //   $result[$key]["CODE"] = "/catalog/" . $result[$key]["CODE"];
    // }
    return $result;
  }


  public static function createLinkByRules($rules, $code, $isi = "", $ibclock_id = false)
  {
    if ($ibclock_id == $_ENV['ID_IBLOCK_PRODUCT']) {
      return "/product/" . $code;
    }
    if (!$rules) {
      return "/catalog/" . self::getCodeCatalogById($isi) . "/" . $code;
    }
    $rules = str_replace("#SITE_DIR#", "", $rules);
    $rules = str_replace("#ELEMENT_CODE#/", $code, $rules);
    return $rules;
  }

  public static function getPrice($productID, $quantity)
  {
    $arPrice = CCatalogProduct::GetOptimalPrice($productID, $quantity, []);
    return $arPrice["RESULT_PRICE"]["BASE_PRICE"];
  }

  public static function getItemsByFilter($request, $response)
  {
    $tositemap =  $request->getQueryParams()['tositemap'];
    $resp = new CustomResponse();
    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    if ($tositemap) $offset = 5000;
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = ((int)$request->getQueryParams()['page']) ?? 1;
    $getSections = ((bool)$request->getQueryParams()['sections']) ?? false;
    $q = $request->getQueryParams()['q'];


    $isinfo = $request->getQueryParams()['isinfo'];
    $filter = $request->getQueryParams()['filter'];
    $el_filter = $request->getQueryParams()['el_filter'] ?? [];
    $el_selected_fields = $tositemap ? ["ID", "TIMESTAMP_X_UNIX", "CODE"] : ["*", ...explode(",", $_ENV["PRODUCT_POPERTY_FIELDS"])];

    foreach ($el_filter as &$value) {
      if ($value == "false") $value = false;
      else $value = $value;
    }

    if (!$filter) return $resp->is400Response($response, ["error" => "not params"]);

    $section = $isinfo ?  \CIBlock::GetList([], [...$filter])->Fetch() : CIBlockSection::GetList([], [...$filter])->GetNext();
    $elements_filter = $isinfo ? ["ACTIVE" => "Y", "IBLOCK_ID" => $section["ID"], ...$el_filter] : ["ACTIVE" => "Y", "SECTION_ID" => $section["ID"], ...$el_filter];


    $_items = CIBlockElement::GetList([$orderBy => $order], [...$elements_filter, "INCLUDE_SUBSECTIONS" => "Y", "ACTIVE" => "Y"], false, ['nPageSize' => $offset, 'iNumPage' => $page], $el_selected_fields);

    while ($item = $_items->GetNext()) {
      $items[] = [
        ...$item,
        "storage" => $tositemap ?  "" : CCatalogProduct::GetByID($item["ID"]),
        "properties" => $tositemap ?  "" : CatalogController::getNotNullProperties($item["ID"], 10, $filter["ID"]),
        "image" => $item["PREVIEW_PICTURE"] ? CFile::GetPath($item["PREVIEW_PICTURE"]) : CFile::GetPath($item["DETAIL_PICTURE"]),
        "images" => $tositemap ?  "" : self::getImages($item["PROPERTY_MORE_PHOTO_VALUE"]),
        // "price" => $tositemap ?  "" : $item["PROPERTY_MINIMUM_PRICE_VALUE"],
        // "price" => $tositemap ?  "" : \Bitrix\Catalog\PriceTable::getList(["select" => ["*"], "filter" => ["PRODUCT_ID" => $item["ID"], "CURRENCY" => "RUB"]])->fetch(),
        "price" => self::getPrice($item["ID"], 1),
        "link" => $tositemap ?  "" : self::createLinkByRules($section["DETAIL_PAGE_URL"], $item["CODE"], $item["IBLOCK_SECTION_ID"], $item["IBLOCK_ID"]),
      ];
    }

    $meta = new SectionValues($_ENV["ID_IBLOCK_PRODUCT"], $section["ID"]);
    $meta = $meta->getValues();
    $total_items = (int)$_items->SelectedRowsCount();
    $total_pages = (int)(ceil($total_items / $offset) ?? 1);

    // return $total_pages;
    if ($getSections) {
      $include_sections_filter = $isinfo ? ["IBLOCK_ID" => $section["ID"], "SECTION_ID" => 0] : ["SECTION_ID" => $section["ID"]];
      $_include_sections = CIBlockSection::GetList([], [...$include_sections_filter, "ACTIVE" => "Y",]);
      while ($_section = $_include_sections->GetNext()) {
        $include_sections[] = $_section;
      }
    }
    return $resp->is200Response(
      $response,
      [
        "meta" => [
          "meta_title" => $meta["SECTION_META_TITLE"] ?? $section["NAME"],
          "meta_description" => $meta["SECTION_META_DESCRIPTION"] ?? "",
        ],
        "section" => $section,
        "include_sections" => $getSections ? $include_sections : null,
        'breadcrumbs' => self::getBreadcrumb($section["ID"]),
        'pagination' => [
          'current_page' => $page,
          'total_pages' => $total_pages,
          'total_items' => $total_items,
          'offset' => $offset,
        ],
        "items" => $page > $total_pages ? [] : $items,
      ]
    );
  }

  public static function getElementByFilter($request, $response)
  {
    $resp = new CustomResponse();
    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = ((int)$request->getQueryParams()['page']) ?? 1;
    $q = $request->getQueryParams()['q'];


    $filter = $request->getQueryParams()['filter'];
    $el_filter = $request->getQueryParams()['el_filter'] ?? ["ID" => 0];


    if (!$filter) return $resp->is400Response($response, ["error" => "not params"]);

    $section = CIBlockElement::GetList([$orderBy => $order], [...$filter, "ACTIVE" => "Y"], false, [], ["*", ...explode(",", $_ENV["PRODUCT_POPERTY_FIELDS"])])->GetNext();

    if (!$section) return $resp->is400Response($response, ["error" => "not found"]);

    $section = [
      ...$section,
      "storage" => CCatalogProduct::GetByID($section["ID"]),
      "properties" => CatalogController::getNotNullProperties($section["ID"]),
      "image" => CFile::GetPath($section["DETAIL_PICTURE"]),
      "images" => self::getImages($section["PROPERTY_MORE_PHOTO_VALUE"]),
      // "price" => \Bitrix\Catalog\PriceTable::getList(["select" => ["*"], "filter" => ["PRODUCT_ID" => $section["ID"], "CURRENCY" => "RUB"]])->fetch(),
      "price" => self::getPrice($section["ID"], 1),
      "link" => self::createLinkByRules($section["DETAIL_PAGE_URL"], $section["CODE"], $section["IBLOCK_SECTION_ID"], $section["IBLOCK_ID"]),
    ];
    $_items = CIBlockElement::GetList([$orderBy => $order], [...$el_filter, "INCLUDE_SUBSECTIONS" => "Y", "ACTIVE" => "Y"], false, ['nPageSize' => $offset, 'iNumPage' => $page], ["*"]);

    // return $resp->is200Response($response, $_items);
    // return $resp->is200Response($response, $el_filter);
    while ($item = $_items->GetNext()) {
      $propertyValue = CIBlockElement::GetProperty($section["IBLOCK_ID"], $item["ID"], [], []);
      while ($value = $propertyValue->fetch()) {
        $properties[] = $value;
      }
      $items[] = [
        ...$item,
        "storage" => CCatalogProduct::GetByID($item["ID"]),
        "properties" => $properties,
        "image" => CFile::GetPath($item["DETAIL_PICTURE"]),
        "images" => self::getImages($item["PROPERTY_MORE_PHOTO_VALUE"]),
        // "price" => $item["PROPERTY_MINIMUM_PRICE_VALUE"],
        // "price" => \Bitrix\Catalog\PriceTable::getList(["select" => ["*"], "filter" => ["PRODUCT_ID" => $item["ID"], "CURRENCY" => "RUB"]])->fetch(),
        "price" => self::getPrice($item["ID"], 1),
        "link" => self::createLinkByRules($section["DETAIL_PAGE_URL"], $item["CODE"], $item["IBLOCK_SECTION_ID"]),
      ];
    }

    $meta = new SectionValues($_ENV["ID_IBLOCK_PRODUCT"], $section["ID"]);
    $meta = $meta->getValues();
    $total_items = (int)$_items->SelectedRowsCount();
    $total_pages = (int)(ceil($total_items / $offset) ?? 1);

    // return $total_pages;
    return $resp->is200Response(
      $response,
      [
        "meta" => [
          "meta_title" => $meta["SECTION_META_TITLE"] ? $meta["SECTION_META_TITLE"] : $section["NAME"],
          "meta_description" => $meta["SECTION_META_DESCRIPTION"] ?? "",
        ],
        "section" => $section,
        // 'breadcrumbs' => self::getBreadcrumb($section["IBLOCK_SECTION_ID"]),
        'breadcrumbs' => self::getBreadcrumb($section["IBLOCK_SECTION_ID"]),
        'pagination' => [
          'current_page' => $page,
          'total_pages' => $total_pages,
          'total_items' => $total_items,
          'offset' => $offset,
        ],
        "items" => $page > $total_pages ? [] : $items,
      ]
    );
  }
}
