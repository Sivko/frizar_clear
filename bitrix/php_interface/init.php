<?php

use Bitrix\Main;

CModule::IncludeModule('currency');

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
