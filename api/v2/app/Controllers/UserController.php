<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");


use Bitrix\UI\EntityEditor\ReturnsEditorFields;
use Bitrix\Sale\Order;
use Bitrix\Main\Loader;

Loader::includeModule('sale');
Loader::includeModule('main');

use CUser;
use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Bitrix\Sale;

class UserController
{
  protected $BXUser;
  protected $email;
  protected $password;
  protected $id;
  protected $token;
  protected $customResponse;

  public function __construct()
  {
    // include $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
    // $this->$USER = new $ACUser;
    // if (!is_object($USER)) 
    $this->BXUser = new CUser;
    $this->customResponse = new CustomResponse();
  }

  public function getUser(Request $request, Response $response)
  {
    $result = [
      "id" => $this->BXUser->GetID(),
      "name" => $this->BXUser->GetFirstName(),
      "lastName" => $this->BXUser->GetLastName(),
      "email" => $this->BXUser->GetEmail(),
      "login" => $this->BXUser->GetLogin()
    ];
    return $this->customResponse->is200Response($response, $result);
  }

  public function getIdByEmail($email = "")
  {
    $res = CUser::GetList(['ID' => 'DESC'], [], ["email" => $email ?? $this->email], ["NAV_PARAMS" => ['nTopCount' => 1, 'nOffset' => 1], "FIELDS" => ["ID"]], ['ID']);
    $id =  $res->fetch()["ID"];
    $this->id = $id;
    return $id;
  }


  public function verifyAccount($password, $email)
  {
    $authResult = $this->BXUser->Login($email, $password, "N");
    if ($authResult === true) {
      $this->email = $email;
      $this->password = $password;
      return true;
    }
    return false;
  }

  public function createToken()
  {
    $this->getIdByEmail($this->email);
    $this->token = TokenController::generateToken($this->id);
    $this->BXUser->Update($this->id, ["UF_JWT_TOKEN" => $this->token], false);
    return ["token" => $this->token];
  }


  public function getUserByToken($token)
  {
    $res = CUser::GetList(['ID' => 'DESC'], [], ["UF_JWT_TOKEN" => $token ?? $this->email], ["NAV_PARAMS" => ['nTopCount' => 1, 'nOffset' => 1], "FIELDS" => ["ID"]], ['ID']);
    $id =  $res->fetch()["ID"];
    $this->id = $id;
    if (!$id) return false;
    return $id;
  }


  public function verifyToken($token)
  {
    $_userId = CUser::GetList(['ID' => 'DESC'], [], ["UF_JWT_TOKEN" => $token], ["NAV_PARAMS" => ['nTopCount' => 1, 'nOffset' => 1], "FIELDS" => ["ID"]], ['ID']);
    $userId = $_userId->fetch()["ID"];
    return $userId;
    // $this->BXUser->Update($userId, ["UF_JWT_TOKEN" => $token], false);
  }

  public function updateUser(Request $request, Response $response)
  {
    $userId = CustomRequestHandler::getParam($request, "userId");
    $fields = CustomRequestHandler::getParam($request, "fields");
    $user = new CUser();
    if ($user) {
      $result = $user->Update($userId, $fields);
      if ($result) {
        return $this->customResponse->is200Response($response, "Изменения прошли успешно");
      } else {
        return $this->customResponse->is400Response($response, $user['LAST_ERROR']);
      }
    } else {
      return $this->customResponse->is400Response($response, "Нет пользователя с таким Email");
    }
  }
}
