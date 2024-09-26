<?php

namespace App\Controllers;

use App\Interfaces\SecretKeyInterface;
use Firebase\JWT\JWT;

class TokenController implements SecretKeyInterface
{
    public static function generateToken($id)
    {
        $now = time();
        $future = $now + 31536000;
        $secretKey = self::JWT_SECRET_KEY;
        $payload = [
            "id" => $id,
            "iat" => $now,
            "exp" => $future
        ];

        return JWT::encode($payload, $secretKey, "HS256");
    }

    public static function decodeToken($token)
    {
        $secretKey = self::JWT_SECRET_KEY;
        $result = JWT::decode($token, $secretKey, ["HS256"]);
        return $result;
    }
}
