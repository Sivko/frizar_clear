<?php

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Controllers\BasketController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

use App\Controllers\CatalogController;
use App\Controllers\OrderController;
use App\Controllers\UserController;
use App\Controllers\ArticleController;
use App\Controllers\BatchController;
use App\Controllers\BrandsController;
use App\Controllers\MenuController;
use App\Controllers\SertificationController;
use App\Response\CustomResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

// use CIBlockSection;

// \Bitrix\Main\Loader::includeModule("sale");
// \Bitrix\Main\Loader::includeModule("catalog");

return function (App $app) {
    $app->setBasePath('/api/v2');

    $app->add(function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($app): ResponseInterface {
        $response = $handler->handle($request);
        $response = $response
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            // ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Origin', 'https://frizar.ru')
            // ->withHeader('Access-Control-Allow-Origin', 'http://localhost:3000')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        return $response;
    });


    $app->get('/', function ($request, Response $response) {
        $resp = new CustomResponse();
        $result = CatalogController::getTest($request);
        return $resp->is200Response($response, $result);
    });

    $app->get("/catalog", function ($request, $response) {
        $resp = new CustomResponse();
        $responseMessage = CatalogController::getCatalogOrProductBySlug($request, $response);
        return $resp->is200Response($response, $responseMessage);
    });


    $app->group("/sections", function ($app) {
        $app->get("", function ($request, Response $response) {
            return CatalogController::getItemsByFilter($request, $response);
        });
        $app->get("/element", function ($request, Response $response) {
            return CatalogController::getElementByFilter($request, $response);
        });
        $app->get("/test", function ($request, Response $response) {
            $resp = new CustomResponse();
            return $resp->is200Response($response, CatalogController::getFirstProductByFilter(["ID" => "1890"]));
        });
    });

    $app->get("/catalog-fasets", function ($request, Response $response) {
        $resp = new CustomResponse();
        return CatalogController::getFasets($request, $response);
    });

    $app->get("/catalog-meilisearch", function ($request, Response $response) {
        $resp = new CustomResponse();
        return $resp->is200Response($response, CatalogController::getFirstProductByFilter(["ID" => "1890"]));
    });

    $app->get("/search", function ($request, $response) {
        $resp = new CustomResponse();
        $responseMessage = CatalogController::getCatalogBySearch($request, $response);
        return $resp->is200Response($response, $responseMessage);
    });

    $app->get("/check-object", function ($request, Response $response) {
        $resp = new CustomResponse();
        $responseMessage = CatalogController::checkObject($request);
        return $resp->is200Response($response, $responseMessage);
    });

    $app->get("/menu", function ($request, Response $response) {
        $resp = new CustomResponse();
        $responseMessage = MenuController::get($request);
        return $resp->is200Response($response, $responseMessage);
    });


    // $app->get("/brands", function ($request, Response $response) {
    //     $resp = new CustomResponse();
    //     return $responseMessage = BrandsController::getBrands($request, $response);
    // });
    $app->get("/anybrands", function ($request, Response $response) {
        $resp = new CustomResponse();
        return $responseMessage = BrandsController::getAnyBrands($request, $response);
        // return $resp->is200Response($response, $responseMessage);
    });

    $app->get("/sertifications", function ($request, Response $response) {
        $resp = new CustomResponse();
        $responseMessage = SertificationController::get($request);
        return $resp->is200Response($response, $responseMessage);
    });


    $app->group("/product-group", function ($app) {
        $app->get("/", function ($request, Response $response) {
            $resp = new CustomResponse();
            $responseMessage = BasketController::getBasket();
            return $resp->is200Response($response, $responseMessage);
        });
    });

    $app->group("/articles", function ($app) {
        $app->get("", function ($request, Response $response) {
            $resp = new CustomResponse();
            $responseMessage = ArticleController::getArticles($request);
            return $resp->is200Response($response, $responseMessage);
        });
        $app->get("/{slug}", function ($request, Response $response) {
            $resp = new CustomResponse();
            $slug = $request->getAttribute('slug');
            $responseMessage = ArticleController::getArticle($slug);
            return $resp->is200Response($response, $responseMessage);
        });
    });

    $app->group("/basket", function ($app) {
        $app->get("/", function ($request, Response $response) {
            $resp = new CustomResponse();
            $responseMessage = BasketController::getBasket();
            return $resp->is200Response($response, $responseMessage);
        });
        $app->post("/add", function ($request, Response $response) {
            $resp = new CustomResponse();
            $result = BasketController::addToBasket($request);
            return $resp->is200Response($response, $result);
        });
        $app->post("/update", function ($request, Response $response) {
            $resp = new CustomResponse();
            $result = BasketController::updateProduct($request);
            return $resp->is200Response($response, $result);
        });
        $app->post("/create-order", function ($request, Response $response) {
            $resp = new CustomResponse();
            $responseMessage = BasketController::createOrder($request);
            return $resp->is200Response($response, $responseMessage);
        });
    });

    $app->group("/auth", function ($app) {
        $app->post("/check", [\App\Controllers\AuthController::class, "CheckAuth"]);
        $app->post("/login", [\App\Controllers\AuthController::class, "Login"]);
        $app->post("/logout", [\App\Controllers\AuthController::class, "Logout"]);
        $app->post("/register", [\App\Controllers\AuthController::class, "Register"]);
        $app->post("/confirm_email", [\App\Controllers\AuthController::class, "ConfirmEmail"]);

        $app->post("/change_password", [\App\Controllers\AuthController::class, "ChangePassword"]);
    });


    $app->group("/user", function ($app) {
        $app->get("", [\App\Controllers\UserController::class, "getUser"]);
        $app->post("/update", [\App\Controllers\UserController::class, "updateUser"]);
    });


    $app->group("/orders", function ($app) {
        $app->get("", [\App\Controllers\OrderController::class, "getOrdersByUser"]);
        $app->get("/item", [\App\Controllers\OrderController::class, "getOrderById"]);
    });

    $app->group("/batch", function ($app) {
        $app->post("/products", function ($request, Response $response) {
            $resp = new CustomResponse();
            $responseMessage = BatchController::updateProduct($request);
            return $resp->is200Response($response, $responseMessage);
        });
    });
};
