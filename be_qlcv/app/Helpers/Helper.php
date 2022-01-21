<?php // Code within app\Helpers\Helper.php

namespace App\Helpers;

use Ramsey\Uuid\Type\Integer;

class Helper
{
    public static function getResponseArray($code, $message, $data = [], $action = '', $error =[])
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'action' => $action,
            'error' => $error
        ]);
    }
}