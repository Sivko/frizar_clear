<?php
require($_SERVER["DOCUMENT_ROOT"] . "/api/v1/headers.php");
header('Content-Type: application/json');
use Bitrix\Main\Mail\Event;

// function UET($EVENT_NAME, $NAME, $LID, $DESCRIPTION)
// {
// 	$et = new CEventType;
// 	$et->Add(array(
// 		"LID"           => $LID,
// 		"EVENT_NAME"    => $EVENT_NAME,
// 		"NAME"          => $NAME,
// 		"DESCRIPTION"   => $DESCRIPTION
// 	));
// }

// UET(
//     "123", "123", "ru",
//     "
//     #EMAIL_TO# - EMail получателя сообщения (#OWNER_EMAIL#)
//     #MESSAGE# - EMail пользователей имеющих роль \"менеджер баннеров\" и \"администратор\"
//     "
// );



$order_id = 1;
$order_account_number_encode = 1;
$order_real_id = 1;
$order_datilyakondryukov8765e = "24.01.2000";
$order_user = "Илья Кондрюков";
$price = 25000;
$email = "limpopo113@gmail.com";
$bcc = "bcc";
$order_list = "1";
$order_public_url = "";
$sale_email = "frizar@bk.ru";







\Bitrix\Main\Mail\Event::sendImmediate(array(
    "EVENT_NAME" => "SALE_NEW_ORDER", 
    "LID" => "s1", 
    "C_FIELDS" => array( 
		"ORDER_ID" => $order_id,
		"ORDER_ACCOUNT_NUMBER_ENCODE" => $order_account_number_encode,
		"ORDER_REAL_ID" => $order_real_id, 
		"ORDER_DATE" => $order_date,
		"ORDER_USER" => $order_user,
		"PRICE" => $price,
		"EMAIL" => $email,
		"BCC" => $bcc,
		"ORDER_LIST" => $order_list,
		"ORDER_PUBLIC_URL" => $order_public_url,
		"SALE_EMAIL" => $sale_email,
    ),
));