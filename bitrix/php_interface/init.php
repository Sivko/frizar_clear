<?php


use Bitrix\Main;
use CIBlockElement;

use Bitrix\Catalog\Model\Event;

$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler(
    'catalog',
    'Bitrix\Catalog\Model\Price::OnAfterAdd',
    ['ToEvents', 'onSetMinimumPrice']
);
$eventManager->addEventHandler(
    'catalog',
    'Bitrix\Catalog\Model\Price::OnAfterUpdate',
    ['ToEvents', 'onSetMinimumPrice']
);

CModule::IncludeModule('currency');
CModule::IncludeModule('iblock');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST');
\Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();

$iblock_id = 20;
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"] . "/log.txt");
// AddMessage2Log(json_encode($arFields["PRODUCT_ID"]));


// Вызывается один раз для при инициализации БД
function updateMinimumPriceAllProducts()
{
    global $iblock_id;
    $filter = ["IBLOCK_ID" => $iblock_id, "ACTIVE" => "Y", "TYPE" => "1"];
    $res = CIBlockElement::GetList(array(), $filter, false, false, ["ID"]);
    while ($el = $res->GetNext()) {
        CIBlockElement::SetPropertyValuesEx($el['ID'], false, ['VAT_INCLUDED' => 'N']);
        $minimum_price = ToEvents::getPrice($el["ID"]);
        CIBlockElement::SetPropertyValuesEx($el['ID'], false, ['MINIMUM_PRICE' => $minimum_price]);
    }
    echo "OK";
}

class ToEvents
{
    public static function getPrice($productID)
    {
        $arPrice = CCatalogProduct::GetOptimalPrice($productID, 1, []);
        // AddMessage2Log($arPrice["RESULT_PRICE"]["BASE_PRICE"]);
        return $arPrice["RESULT_PRICE"]["BASE_PRICE"];
    }
    public static function onSetMinimumPrice(Event $event): void
    {
        //убирает галочку - НДС включен в цену и добавляет цену для сортировки
        $arFields  =  $event->getParameter('fields');
        CIBlockElement::SetPropertyValuesEx($arFields['PRODUCT_ID'], false, ['VAT_INCLUDED' => 'N']);
        $minimum_price = self::getPrice($arFields["PRODUCT_ID"]);
        CIBlockElement::SetPropertyValuesEx($arFields['PRODUCT_ID'], false, ['MINIMUM_PRICE' => $minimum_price]);
    }
}

function createSiteMap()
{
    global $iblock_id;
    global $USER;
    $USER = new CUser;
    $USER->Authorize(1);
    $filter = ["IBLOCK_ID" => $iblock_id];
    $fields = ["CODE", "TIMESTAMP_X"];
    $result = CIBlockElement::GetList(array(), $filter, false, false, $fields);
    while ($item = $result->Fetch()) {
        $elements[] = $item;
    }
    $dom = new DOMDocument('1.0', 'UTF-8');
    $strXML = '<?xml version="1.0" encoding="utf-8"?>';
    $strXML .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($elements as $element) {
        $strXML .= '<url>';
        $strXML .= '<loc>' . 'https://frizar.ru/product/' . $element["CODE"] . '</loc>';
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



function generateYMLAgent()
{
    global $iblock_id;
    // Инициализируем глобальный объект $USER
    global $USER;
    $USER = new CUser;

    // Задаем авторизованного пользователя вручную (например, ID = 1)
    $USER->Authorize(1);
    $filter = ["IBLOCK_ID" => $iblock_id];
    $fields = ["ID", "CODE", "NAME", "IBLOCK_SECTION_ID", "DETAIL_TEXT", "DETAIL_PICTURE"];
    $result = CIBlockElement::GetList(array(), $filter, false, false, $fields);
    while ($item = $result->Fetch()) {
        $item['PRICE'] = ToEvents::getPrice($item["ID"]);
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

    $ymlCatalog = $dom->createElement('yml_catalog');
    $ymlCatalog->setAttribute('date', date('Y-m-d\TH:i:sP'));

    // Создаем корневой элемент
    $shop = $dom->createElement('shop');
    $ymlCatalog->appendChild($shop);

    $dom->appendChild($ymlCatalog);

    // Получаем категории
    $categories = [];
    $sectionFilter = ["IBLOCK_ID" => $iblock_id];
    $sectionFields = ["ID", "NAME"];
    $sectionResult = CIBlockSection::GetList([], $sectionFilter, true, $sectionFields);

    while ($section = $sectionResult->Fetch()) {
        $categories[$section['ID']] = htmlspecialchars($section['NAME']);
    }

    // Добавляем необходимые элементы
    $shop->appendChild($dom->createElement('name', 'frizar.ru'));
    $shop->appendChild($dom->createElement('company', 'ООО Фризар'));
    $shop->appendChild($dom->createElement('url', 'https://frizar.ru'));
    $shop->appendChild($dom->createElement('currencies', 'RUB'));
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
        $offer->appendChild($dom->createElement('url', htmlspecialchars(CFile::GetPath($product["DETAIL_PICTURE"]))));

        $offer->appendChild($dom->createElement('name', htmlspecialchars($product['NAME'])));
        $offer->appendChild($dom->createElement('picture', $product['PICTURE']));
        $offer->appendChild($dom->createElement('vendor', $product['BRAND']));
        $offer->appendChild($dom->createElement('description', strip_tags($product["DETAIL_TEXT"])));
        $offer->appendChild($dom->createElement('currencyId', 'RUB'));
        $offer->appendChild($dom->createElement('categoryId', $product['IBLOCK_SECTION_ID']));
        $offer->appendChild($dom->createElement('store', false));
        $offer->appendChild($dom->createElement('pickup', true));
        $offer->appendChild($dom->createElement('delivery', true));

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
    //return "generateYMLAgent();"; 
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


function createImagesSitemap()
{
    global $iblock_id;
    global $USER;
    $USER = new CUser;
    $USER->Authorize(1);
    $filter = ["IBLOCK_ID" => $iblock_id];
    $fields = ["CODE", "TIMESTAMP_X"];

    // Параметры для запроса
    $arFilter = [
        'IBLOCK_ID' => $iblock_id, // Замените на ID вашего инфоблока
        'ACTIVE' => 'Y',
    ];
    $arSelect = ['ID', 'NAME', 'DETAIL_PICTURE', 'PROPERTY_IMAGE_URL']; // Укажите нужные поля

    $result = CIBlockElement::GetList([], $arFilter, false, false, $arSelect);
    while ($item = $result->Fetch()) {
        $elements[] = $item;
    }
    $dom = new DOMDocument('1.0', 'UTF-8');

    // Создание XML документа
    $strXML = '<?xml version="1.0" encoding="UTF-8"?>';
    $strXML .=  '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">';
    $strXML .=  '<url>';
    $strXML .=  '<loc>' . htmlspecialchars('https://frizar.ru') . '</loc>';
    $strXML .=  '<lastmod>' . date('Y-m-d') . '</lastmod>';
    $strXML .=  '<changefreq>daily</changefreq>';
    $strXML .=  '<priority>1.0</priority>';
    foreach ($elements as $element) {

        $strXML .=  '<image:image xmlns:image="http://www.google.com/schemas/sitemap-image">';
        $strXML .=  '<image:loc>' . htmlspecialchars(CFile::GetPath($element["DETAIL_PICTURE"])) . '</image:loc>';
        $strXML .=  '<image:title>' . htmlspecialchars($element['NAME']) . '</image:title>';
        $strXML .=  '</image:image>';
    }
    $strXML .=  '</url>';
    $strXML .=  '</urlset>';

    $dom->loadXML($strXML);
    $xml = $dom->saveXML();
    echo $xml;
    $dom->save(dirname(dirname(__DIR__)) . '/images_sitemap.xml');
    return $elements;
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
