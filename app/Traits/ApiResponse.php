<?php

namespace App\Traits;

trait ApiResponse
{
    //200
    public function baseSuccessResponse($data = null, $message = 'action success', $code = 200)
    {
        return response()->json([
            'message' => $message,
            'data' => $data
        ]);
    }

    //400
    public function errorResponse($message = null, $code = 400)
    {
        
    }

    public function unauthorizedResponse($message = 'User not authenticated')
    {
        return response()->json([
            'status' => false,
            'message' => $message
        ], 401);
    }

    public function notFoundResponseStory($message = 'Stories Not Found')
    {
        return response()->json([
            'message' => $message
        ], 404);
    }

    //500
    public function internalServerError($message = 'Internal Server Error')
    {
        return response()->json([
            'message' => $message
        ], 500);
    }    

}
