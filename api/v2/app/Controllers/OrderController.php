<?php

namespace App\Controllers;

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

use App\Requests\CustomRequestHandler;
use App\Response\CustomResponse;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use App\Controllers\BasketController;
use App\Controllers\CatalogController;
use Bitrix\Sale\Order;
use Bitrix\Main\Loader;
use CUser;

Loader::includeModule('sale');



class OrderController
{

  protected $customResponse;

  public static function getOrdersByUserId($request,  $response)
  {

    $orderBy = $request->getQueryParams()['orderBy'] ?? $_ENV["ORDER_BY_DEFAULT"];
    $offset = ((int)($request->getQueryParams()['offset'] ?? $_ENV["OFFSET_DEFAULT"]));
    $offset = $offset < 50 ? $offset : $_ENV["OFFSET_DEFAULT"];
    $order = $request->getQueryParams()['order'] ?? $_ENV["ORDER_DEFAULT"];
    $page = max(1, (int)($request->getQueryParams()['page'] ?? 1));
    $resp = new CustomResponse();

    // Получаем список заказов пользователя через ORM
    $userId = CustomRequestHandler::getParam($request, "userId");


    $total_items = Order::getList([
      'filter' => ['USER_ID' => $userId],
      'select' => ['ID'],
    ])->getSelectedRowsCount();
    $total_pages = ceil($total_items / $offset);


    $orders = [];
    $orderList = Order::getList([
      'filter' => ['USER_ID' => $userId],
      'order' => ['DATE_INSERT' => 'DESC'], // Сортировка по дате
      'select' => ['ID', "STATUS_ID", "PAYED", "PRICE", "DATE_INSERT"],
      'limit' => $offset,
      'offset' => ($page - 1) * $offset,
    ]);
    while ($order = $orderList->fetch()) {
      $tmp = Order::load($order["ID"]);

      $shipmentCollection = $tmp->getShipmentCollection();

      // Инициализируем массив для хранения данных о доставке
      $deliveryInfoArray = [];

      foreach ($shipmentCollection as $shipment) {
        if (!$shipment->isSystem()) {
          // Получаем данные о доставке
          $shipmentData = [
            'id' => $shipment->getId(),
            'delivery_id' => $shipment->getDeliveryId(), // ID службы доставки
            'delivery_name' => $shipment->getDeliveryName(), // Название службы доставки
            'price' => $shipment->getPrice(), // Стоимость доставки
            'status' => $shipment->getField('STATUS_ID'), // Статус доставки
            // Добавьте другие необходимые поля
          ];

          // Добавляем информацию о доставке в массив
          $deliveryInfoArray[] = $shipmentData;
        }
      }
      $order['DELIVERY_STATUS'] = $deliveryInfoArray;
      $result = BasketController::getBasketByOrderId($order["ID"]);
      $order["DATE_INSERT"] = $order["DATE_INSERT"]->format("Y-m-d H:i:s");
      $order["TOTAL_PRODUCTS"] = count($result);
      $order["BASKET"] = $result;
      $orders[] = $order;
    }

    $items = $page > $total_pages ? [] : $orders;

    return $resp->is200Response(
      $response,
      [
        "meta" => [
          "meta_title" => "Заказы пользователя",
          "meta_description" => "Список заказов пользователя $userId",
        ],
        'pagination' => [
          'current_page' => $page,
          'total_pages' => $total_pages,
          'total_items' => $total_items,
          'offset' => $offset,
        ],
        "items" => $page > $total_pages ? [] : $items,
      ]
    );
  }

  public function updateOrder(Request $request, Response $response)
  {
    $this->customResponse = new CustomResponse();
    $orderId = CustomRequestHandler::getParam($request, "orderId");
    $comments = CustomRequestHandler::getParam($request, "comments");
    $status = CustomRequestHandler::getParam($request, "status");
    $order = Order::load($orderId);
    if ($order) {
      $order->setField("USER_DESCRIPTION", $comments);
      $order->setField('STATUS_ID', $status);
      $result = $order->save();
      if ($result->isSuccess()) {
        return $this->customResponse->is200Response($response, "Изменения прошли успешно");
      } else {
        return $this->customResponse->is400Response($response, implode(", ", $result->getErrorMessages()));
      }
    } else {
      return $this->customResponse->is400Response($response, "Нет заказа с таким ID");
    }
  }
}
