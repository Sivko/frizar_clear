<?php

namespace  App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use App\Validation\Validator;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;
use CUser;





class AuthController
{

    protected $user;
    protected $customResponse;
    protected $validator;
    protected $USER;


    public function __construct()
    {
        // include $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";
        // $this->$USER = new $ACUser;
        // if (!is_object($USER)) 
        // $this->USER = new CUser;
        $this->customResponse = new CustomResponse();
        $this->validator = new Validator();
        // $this->user = new UserController();
        $this->user = new CUser();
    }

    public function CheckAuth(Request $request, Response $response)
    {
        // $result = $this->user->IsAuthorized();
        $result = $this->user->GetFirstName();
        return $this->customResponse->is200Response($response, $result);
    }

    public function Register(Request $request, Response $response)
    {
        $user_login = CustomRequestHandler::getParam($request, "email");
        $user_name = CustomRequestHandler::getParam($request, "name");
        $user_last_name = CustomRequestHandler::getParam($request, "last_name");
        $user_password = CustomRequestHandler::getParam($request, "password");
        $user_confirm_password = CustomRequestHandler::getParam($request, "confirm_password");
        $user_email = CustomRequestHandler::getParam($request, "email");

        $result = $this->user->Register(
            $user_login,
            $user_name,
            $user_last_name,
            $user_password,
            $user_confirm_password,
            $user_email,
        );
        // $ID = $this->user->Add([
        //     "EMAIL"             => $user_email,
        //     "LOGIN"             => $user_login,
        //     "ACTIVE"            => "Y",
        //     "GROUP_ID"          => array(6, 10),
        //     "PASSWORD"          => $user_password,
        //     "CONFIRM_PASSWORD"  => $user_confirm_password,
        // ]);

        // if (intval($ID) > 0) {
        //     $this->user->Authorize($ID);
        //     $responseMessage = ["success" => true, "message" => "Регистрация прошла успешно!"];
        //     return $this->customResponse->is200Response($response, $responseMessage);
        // } else {
        //     // $responseMessage = strip_tags($result["MESSAGE"]);
        //     $responseMessage = strip_tags($this->user->LAST_ERROR);
        //     return $this->customResponse->is400Response($response, $responseMessage);
        // }
        if ($this->user->IsAuthorized()) {
            $responseMessage = strip_tags($result["MESSAGE"]);
            return $this->customResponse->is200Response($response, $responseMessage);
        } else {
            $responseMessage = strip_tags($result["MESSAGE"]);
            return $this->customResponse->is400Response($response, $responseMessage);
        }
    }

    public function Login(Request $request, Response $response)
    {
        $password = CustomRequestHandler::getParam($request, "password");
        $login = CustomRequestHandler::getParam($request, "login");
        $result = $this->user->Login($login, $password, "Y", "Y");

        if ($result === true && $this->user->IsAuthorized()) {
            $responseMessage = ["success" => true, "message" => "Успешная авторизация"];
            \Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();
            return $this->customResponse->is200Response($response, $responseMessage);
        } else {
            $responseMessage = "Не удалось авторизоваться. " . strip_tags($result["MESSAGE"]);
            return $this->customResponse->is400Response($response, $responseMessage);
        }
    }

    public function Logout(Request $request, Response $response)
    {
        $this->user->Logout();
        \Bitrix\Main\Context::getCurrent()->getResponse()->writeHeaders();
        $responseMessage = ["message" => "Вы вышли из аккаунта"];
        return $this->customResponse->is200Response($response, $responseMessage);
    }

    public function GetUserByToken(Request $request, Response $response)
    {
        $token = CustomRequestHandler::getParam($request, "token");
        // $responseMessage = $this->user->getUserByToken($token);
        $responseMessage = "tmp";
        return $this->customResponse->is200Response($response, $responseMessage);
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function ChangePassword(Request $request, Response $response)
    {
        $userEmail = CustomRequestHandler::getParam($request, "userEmail");
        $login = CustomRequestHandler::getParam($request, "login");
        if ($userEmail && $login) {
            $user = new CUser();
            $user->SendPassword($login, $userEmail);
            return $this->customResponse->is200Response($response, "ok");
        } else {
            return $this->customResponse->is400Response($response, "Нет пользователя с таким Email");
        }
    }

    //     public function EmailExist($email)
    //     {
    //         $count = $this->user->where(['email' => $email])->count();
    //         if ($count == 0) {
    //             return false;
    //         }
    //         return true;
    //     }
}
