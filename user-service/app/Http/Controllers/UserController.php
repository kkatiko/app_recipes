<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use App\Models\UserIngredient;

class UserController extends Controller
{
    /**
     * Регистрация нового пользователя
     */
    public function register(Request $request): JsonResponse
    {
        $this->validate($request, [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6'
        ]);

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => app('hash')->make($request->password)
            ]);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Registration failed',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Добавление ингредиента пользователю
     */
    public function addIngredient(Request $request, $userId): JsonResponse
    {
        $this->validate($request, [
            'ingredient_id' => 'required|integer',
            'quantity' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:10'
        ]);

        // Проверяем существование пользователя
        if (!User::find($userId)) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Проверяем существование ингредиента через Ingredient Service
        try {
            $ingredientResponse = Http::get(
                env('INGREDIENT_SERVICE_URL') . "/ingredients/{$request->ingredient_id}"
            );

            if ($ingredientResponse->status() !== 200) {
                return response()->json(['error' => 'Ingredient not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ingredient service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }

        // Сохраняем ингредиент пользователя
        try {
            $userIngredient = UserIngredient::updateOrCreate(
                [
                    'user_id' => $userId,
                    'ingredient_id' => $request->ingredient_id
                ],
                [
                    'quantity' => $request->quantity ?? 1,
                    'unit' => $request->unit ?? 'шт'
                ]
            );

            return response()->json($userIngredient, 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to save ingredient',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение ингредиентов пользователя
     */
    public function getUserIngredients($userId): JsonResponse
    {
        if (!User::find($userId)) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $userIngredients = UserIngredient::where('user_id', $userId)->get();

        if ($userIngredients->isEmpty()) {
            return response()->json([], 200);
        }

        // Получаем детали ингредиентов
        try {
            $ingredientIds = $userIngredients->pluck('ingredient_id')->implode(',');
            $ingredientsResponse = Http::get(
                env('INGREDIENT_SERVICE_URL') . "/ingredients",
                ['ids' => $ingredientIds]
            );

            if ($ingredientsResponse->status() !== 200) {
                return response()->json([
                    'error' => 'Failed to fetch ingredient details'
                ], 502);
            }

            $ingredientsDetails = collect($ingredientsResponse->json());

            // Объединяем данные
            $result = $userIngredients->map(function ($item) use ($ingredientsDetails) {
                $detail = $ingredientsDetails->firstWhere('id', $item->ingredient_id);
                return [
                    'id' => $item->id,
                    'user_id' => $item->user_id,
                    'ingredient_id' => $item->ingredient_id,
                    'name' => $detail['name'] ?? 'Unknown',
                    'category' => $detail['category'] ?? null,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'created_at' => $item->created_at,
                    'updated_at' => $item->updated_at
                ];
            });

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }
    }

    /**
     * Удаление ингредиента пользователя
     */
    public function removeIngredient($userId, $ingredientId): JsonResponse
    {
        $deleted = UserIngredient::where('user_id', $userId)
            ->where('ingredient_id', $ingredientId)
            ->delete();

        if ($deleted) {
            return response()->json(['message' => 'Ingredient removed']);
        }

        return response()->json(['error' => 'Ingredient not found'], 404);
    }

    public function findRecipes($userId): JsonResponse
    {
        // Получаем ингредиенты пользователя
        $userIngredients = UserIngredient::where('user_id', $userId)->get();
        
        if ($userIngredients->isEmpty()) {
            return response()->json([], 200);
        }

        // Отправляем запрос в Search Service
        try {
            $response = Http::get(env('SEARCH_SERVICE_URL') . '/api/search/recipes', [
                'ingredient_ids' => $userIngredients->pluck('ingredient_id')->toArray(),
                'partial_match' => true
            ]);

            return response()->json($response->json(), $response->status());
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Search service unavailable',
                'message' => $e->getMessage()
            ], 503);
        }
    }
}