<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResponseService
{
    public function success(mixed $data = null, string $message = 'Operation successful', int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public function error(string $message, int $status = 400, mixed $data = null): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }
}
