<?php

namespace  App\Response;

class CustomResponse
{

    public function is200Response($response, $responseMessage)
    {
        $responseMessage = json_encode($responseMessage);
        $response->getBody()->write($responseMessage);
        return $response->withHeader("Content-Type", "application/json")
            ->withStatus(200);
    }

    public function is200ResponseStatus($response, $responseMessage)
    {
        $responseMessage = json_encode(["success" => true, "message" => $responseMessage]);
        $response->getBody()->write($responseMessage);
        return $response->withHeader("Content-Type", "application/json")
            ->withStatus(200);
    }

    public function is400Response($response, $responseMessage)
    {
        $responseMessage = json_encode(["success" => false, "message" => $responseMessage]);
        $response->getBody()->write($responseMessage);
        return $response->withHeader("Content-Type", "application/json")
            ->withStatus(400);
    }
    

    public function is422Response($response, $responseMessage)
    {
        $responseMessage = json_encode(["success" => true, "response" => $responseMessage]);
        $response->getBody()->write($responseMessage);
        return $response->withHeader("Content-Type", "application/json")
            ->withStatus(422);
    }
}
