<?php


use Bitrix\Main;
use CIBlockElement;

CModule::IncludeModule('currency');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST');
\Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();

function createSiteMap()
{

  // Инициализируем глобальный объект $USER
  global $USER;
  $USER = new CUser;

  // Задаем авторизованного пользователя вручную (например, ID = 1)
  $USER->Authorize(1);
  $filter = ["IBLOCK_ID" => 10];
  $fields = ["CODE", "TIMESTAMP_X"];
  $result = CIBlockElement::GetList(array(), $filter, false, false, $fields);
  while ($item = $result->Fetch()) {
    $elements[] = $item;
  }
  $dom = new DOMDocument('1.0', 'utf-8');
  $strXML = '<?xml version="1.0" encoding="utf-8"?>';
  $strXML .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
  foreach ($elements as $element) {
    $strXML .= '<url>';
    $strXML .= '<loc>' . 'https://vs113.ru/product/' . $element["CODE"] . '</loc>';
    $strXML .= '<lastmod>' . date("Y-m-d", strtotime($element['TIMESTAMP_X'])) . '</lastmod>';
    $strXML .= '<changefreq>' . 'monthly' . '</changefreq>';
    $strXML .= '<priority>' . '0.8' . '</priority>';
    $strXML .= '</url>';
  }
  $strXML .= '</urlset>';
  $dom->loadXML($strXML);
  $xml = $dom->saveXML();
  $dom->save(dirname(dirname(__DIR__)) . '/sitemap.xml');
  return $elements;
}

function getPrice($productID)
{
  $arPrice = CCatalogProduct::GetOptimalPrice($productID, 1, []);
  return $arPrice["RESULT_PRICE"]["BASE_PRICE"];
}

function generateYML()
{
  // Инициализируем глобальный объект $USER
  global $USER;
  $USER = new CUser;

  // Задаем авторизованного пользователя вручную (например, ID = 1)
  $USER->Authorize(1);
  $filter = ["IBLOCK_ID" => 10];
  $fields = ["ID", "CODE", "NAME", "IBLOCK_SECTION_ID", "DETAIL_TEXT", "DETAIL_PICTURE"];
  $result = CIBlockElement::GetList(array(), $filter, false, false, $fields);
  while ($item = $result->Fetch()) {
    $item['PRICE'] = getPrice($item["ID"]);
    $item['PICTURE'] = CFile::GetPath($item["DETAIL_PICTURE"]);
    $properties = CIBlockElement::GetProperty(10, $item["ID"], ["sort" => "asc"], ["ACTIVE" => "Y"]);
    while ($prop = $properties->Fetch()) {
        if ($prop['NAME'] === 'Бренд') { 
            $item['BRAND'] = $prop['VALUE_ENUM'];
        }
    }

    $products[] = $item;
  }
  header("Content-Type: text/xml; charset=utf-8");

  $dom = new DOMDocument('1.0', 'utf-8');
  $dom->formatOutput = true;

  // Создаем корневой элемент
  $shop = $dom->createElement('shop');
  $dom->appendChild($shop);

  // Получаем категории
  $categories = [];
  $sectionFilter = ["IBLOCK_ID" => 10];
  $sectionFields = ["ID", "NAME"];
  $sectionResult = CIBlockSection::GetList([], $sectionFilter, true, $sectionFields);
  
  while ($section = $sectionResult->Fetch()) {
      $categories[$section['ID']] = htmlspecialchars($section['NAME']);
  }

  // Добавляем необходимые элементы
  $shop->appendChild($dom->createElement('name', 'frizar.ru'));
  $shop->appendChild($dom->createElement('company', 'ООО Фризар'));
  $shop->appendChild($dom->createElement('url', 'https://vs113.ru'));

  // Добавляем категории
  $categoriesElement = $dom->createElement('categories');
  $shop->appendChild($categoriesElement);
  
  foreach ($categories as $id => $name) {
      $category = $dom->createElement('category', $name);
      $category->setAttribute('id', $id);
      $categoriesElement->appendChild($category);
  }

  // Добавляем товары
  $offers = $dom->createElement('offers');
  $shop->appendChild($offers);

  foreach ($products as $product) {
    $offer = $dom->createElement('offer');

    $offer->appendChild($dom->createElement('id', $product['ID']));
    $offer->appendChild($dom->createElement('name', htmlspecialchars($product['NAME'])));
    $offer->appendChild($dom->createElement('picture', $product['PICTURE']));
    $offer->appendChild($dom->createElement('vendor', $product['BRAND']));
    $offer->appendChild($dom->createElement('description', $product["DETAIL_TEXT"]));
    $offer->appendChild($dom->createElement('currencyId', 'RUB'));
    $offer->appendChild($dom->createElement('categoryId', $product['IBLOCK_SECTION_ID']));

    //TODO: не выводит price, хотя он есть в product (вроде бы)
    $offer->appendChild($dom->createElement('price', $product['PRICE']));

    // // Дополнительные параметры
    // if (isset($product['param'])) {
    //   foreach ($product['param'] as $paramName => $paramValue) {
    //     $param = $dom->createElement('param', htmlspecialchars($paramValue));
    //     $param->setAttribute('name', htmlspecialchars($paramName));
    //     $offer->appendChild($param);
    //   }
    // }

    $offers->appendChild($offer);
  }
  $dom->save(dirname(dirname(__DIR__)) . '/yml_export.xml');
  //$dom->save('yml_export.xml'); // Сохраняем в файл
}

// Функция для обновления курса валюты (должно запускаться каждый день через агент-контроллер)
if (!function_exists('getCurrency')) {
  function getCurrency()
  {
    $http = new Main\Web\HttpClient();
    $http->setRedirect(true);
    $data = $http->get('http://www.cbr.ru/scripts/XML_daily.asp');
    $xml = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOCDATA);
    $json = json_encode($xml);
    $array = json_decode($json, TRUE);
    foreach ($array["Valute"] as $key => $item) {
      if ($item["CharCode"] == "EUR" || $item["CharCode"] == "CNY") {
        $value = str_replace(",", ".", $item["Value"]);
        $arFields = array("RATE" => $value, "RATE_CNT" => 1, "CURRENCY" => $item["CharCode"], "DATE_RATE" => date("d.m.Y"));
        print_r($item) . "<br/>";
        CCurrencyRates::Add($arFields);
      }
    }
  }
}



// Переназначает дефолтного отправителя в из bitrix 
function custom_mail($to, $subject, $message, $additionalHeaders = '')
{
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/PHPMailer.php';
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/SMTP.php';
  require_once $_SERVER["DOCUMENT_ROOT"] . '/api/v2/vendor/phpmailer/phpmailer/src/Exception.php';

  $mail = new PHPMailer\PHPMailer\PHPMailer();
  $mail->isSMTP();
  // $mail->Host   = 'smtp.beget.com';  
  $mail->Host   = 'smtp.yandex.ru';
  $mail->SMTPAuth   = true;
  // $mail->Username   = 'abc@frizar.ru';
  $mail->Username   = 'limpopo113@yandex.ru';
  // $mail->Password   = '!@#$1234QWERasdf';    
  $mail->Password   = 'dbhschdvciddxjoh';
  // $mail->Password   = '';
  $mail->SMTPSecure = 'ssl';
  $mail->Port   = 465;
  $mail->CharSet = "UTF-8";


  $to = str_replace(' ', '', $to);
  $address = explode(',', $to);
  foreach ($address as $addr)
    $mail->addAddress($addr);

  $mail->isHTML(true); // Устанавливаем формат HTML

  $mail->Subject = $subject;
  $mail->Body    = $message;
  // $mail->From    = 'abc@frizar.ru';
  $mail->From    = 'limpopo113@yandex.ru';
  // $mail->FromName = 'Your Name'; // Если хотите добавить имя отправителя
  $mail->FromName = 'FromName'; // Если хотите добавить имя отправителя

  $mail->send();
}
