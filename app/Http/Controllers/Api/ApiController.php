<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     title="Comments API",
 *     version="1.0.0",
 *     description="API for comments",
 *     @OA\Contact(
 *         name="API Support",
 *         email="support@laravel.com"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 */
class ApiController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api",
     *     summary="Get String,
     *     @OA\Response(response=200, description="Hello, World!")
     * )
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        return response()->json(['message' => 'Hello, World!']);
    }
}

