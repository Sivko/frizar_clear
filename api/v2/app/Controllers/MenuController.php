<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('iblock');

class MenuController
{


  public static function generateFullSlugs(&$node, $parentSlug = '')
  {
    // Формируем новый slug для текущего узла
    $node['fullslug'] = '/catalog/' . trim(str_replace('/catalog/', '', $parentSlug) . '/' . $node['slug'], '/');

    // Если у узла есть дочерние элементы, рекурсивно вызываем функцию для них
    if (!empty($node['childs'])) {
      foreach ($node['childs'] as &$child) {
        self::generateFullSlugs($child, $node['fullslug']);
      }
    }
  }


  public static function get($request)
  {
    $productId = $request->getQueryParams()['productId'];

    $filter = [
      'IBLOCK_ID' => $productId,
      'ACTIVE' => 'Y',
      'INCLUDE_SUBSECTIONS' => 'N',
    ];
    $sections = [];
    $sectionIterator = \CIBlockSection::GetList(
      [],
      $filter, // Фильтры
      false, // Не требуется подсчет элементов
      ['ID', 'NAME', 'DEPTH_LEVEL', 'IBLOCK_SECTION_ID', 'CODE', 'INLCUDE_SUBSECTIONS'] // Поля для выборки
    );
    while ($section = $sectionIterator->Fetch()) {
      $sections[] = $section;
    }

    $items = [];
    foreach ($sections as $item) {
      $items[$item['ID']] = [
        'id' => $item['ID'],
        'name' => $item['NAME'],
        'slug' => $item['CODE'],
        'fullslug' => '/catalog' . '/' . $item['CODE'],
        'level' => (int)$item['DEPTH_LEVEL'],
        'childs' => null,
        'parentId' => $item['IBLOCK_SECTION_ID']
      ];
    }

    $tree = [];
    foreach ($sections as $item) {
      if ($item['IBLOCK_SECTION_ID'] === null) {
        // Если нет родителя, добавляем элемент в корень дерева
        $tree[] = &$items[$item['ID']];
      } else {
        // Если есть родитель, добавляем элемент в его дочерние элементы
        $items[$item['IBLOCK_SECTION_ID']]['childs'][] = &$items[$item['ID']];
      }
    }
    foreach ($tree as $_item) {
      self::generateFullSlugs($_item);
    }
    return [
      "id" => "0",
      "name" => "",
      "slug" => "",
      "level" => 0,
      "childs" => $tree
    ];
  }
}
