<?php

if (!function_exists('api_success')) {
    function api_success($data = null, $message = "Success", $status = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }
}

if (!function_exists('api_error')) {
    function api_error($errors = null , $message = "Error", $status = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], $status);
    }
}
