<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;

use Tymon\JWTAuth\Facades\JWTAuth;
use Exception;
class Helper
{
    public static function getResponseJson($code, $message, $data = [], $action = '', $error =[])
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'action' => $action,
            'error' => $error
        ]);
    }
    
    public static function getUser() {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (Exception $e) {
            return null;
        }
    }
}