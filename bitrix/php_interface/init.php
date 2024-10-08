<?php

use Bitrix\Main;

CModule::IncludeModule('currency');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, Accept, Origin, Authorization');
header('Access-Control-Allow-Methods: GET, POST');
\Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();



// Функция для создания siteMap (должно запускаться каждый день через агент-контроллер, или по триггеру, например от ТГ бота)
if (!function_exists('createSiteMap')) {
  function createSiteMap()
  {
    $dom = new DOMDocument('1.0', 'utf-8');
    $strXML = '<?xml version="1.0" encoding="utf-8"?><root><item>Первый</item><item>Второй</item></root>';
    $dom->loadXML($strXML);
    $xml = $dom->saveXML();
    echo htmlspecialchars($xml);
    $dom->save(dirname(dirname(__DIR__)).'/doc.xml');
    // echo ;
  }
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
