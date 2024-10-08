<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");



$props = [
    "user_login" => '151234',
    "user_name" => '123',
    "user_last_name" => '123',
    "user_password" => 'qwerty',
    "user_confirm_password" => 'qwerty',
    "user_email" => 'abc@mail.ru',
];
// $user_site_id = false;
// $user_captcha_word = '';
// $user_captcha_sid = 0;

$user = new CUser;
$arResult = $user->Register(
    $props["user_login"],
    $props["user_name"],
    $props["user_last_name"],
    $props["user_password"],
    $props["user_confirm_password"],
    $props["user_email"],
);

ShowMessage($arResult);
echo $user->GetID();
