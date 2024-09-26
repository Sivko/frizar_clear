<?php

namespace App\Storage;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
require_once $_SERVER["DOCUMENT_ROOT"] . "/api/v2/vendor/autoload.php";

use Bitrix\Main\UserTable;
use OAuth2\Storage\Pdo;

class Bitrix extends Pdo
{
    public function __construct($connection, array $config = [])
    {
        $config['user_table'] = 'b_user';

        parent::__construct($connection, $config);
    }

    public function getUser($username="")
    {
        $userInfo = UserTable::getList([
            // 'filter' => ['LOGIN' => $username],
            'select' => ["*"]
        ])->fetch();

        if (!$userInfo) {
            return false;
        }

        return array_merge([
            'user_id' => $userInfo
        ], $userInfo);
    }

    public function setUser($username, $password, $firstName = null, $lastName = null)
    {
        // do nothing
    }

    public function test(){
        return "test";
    }

    protected function checkPassword($user, $password)
    {
        return $password === $user['UF_SMS_CODE'];
    }
}