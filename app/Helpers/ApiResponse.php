<?php

namespace App\Helpers;

class ApiResponse
{
    public static function success($data = [], $message = '', $code = 200)
    {
        return response()->json([
            'code' => $code,
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    public static function error($message = '', $code = 500)
    {
        return response()->json([
            'code' => $code,
            'success' => false,
            'data' => null,
            'message' => $message,
        ], $code);
    }
}
