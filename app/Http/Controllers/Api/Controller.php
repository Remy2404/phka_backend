<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;

abstract class Controller extends BaseController
{
    /**
     * Send success response.
     */
    protected function sendResponse($data, $message = null, $status = 200)
    {
        $response = [
            'success' => true,
            'data' => $data,
        ];

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response, $status);
    }

    /**
     * Send error response.
     */
    protected function sendError($message, $status = 500, $data = null)
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($data) {
            $response['data'] = $data;
        }

        return response()->json($response, $status);
    }

    /**
     * Send validation error response.
     */
    protected function sendValidationError($errors, $message = 'Validation failed')
    {
        return $this->sendError($message, 422, $errors);
    }
}