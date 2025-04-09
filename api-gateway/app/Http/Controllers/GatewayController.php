<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class GatewayController extends Controller
{
    public function routeRequest(Request $request, $service, $path = ''): JsonResponse
    {
        $serviceUrls = [
            'users' => env('USER_SERVICE_URL'),
            'ingredients' => env('INGREDIENT_SERVICE_URL'),
            'recipes' => env('RECIPE_SERVICE_URL'),
            'search' => env('SEARCH_SERVICE_URL'),
        ];

        if (!isset($serviceUrls[$service])) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        try {
            $url = rtrim($serviceUrls[$service], '/') . '/' . $path;
            $method = strtolower($request->method());

            $response = Http::withHeaders($request->headers->all())
                ->{$method}($url, $request->all());

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }
    }
}