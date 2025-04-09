<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * Поиск рецептов по ингредиентам
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchRecipes(Request $request): JsonResponse
    {
        $this->validate($request, [
            'ingredients' => 'required|array|min:1',
            'ingredients.*' => 'integer|min:1',
            'strict' => 'sometimes|boolean',
            'limit' => 'sometimes|integer|min:1|max:100'
        ]);

        try {
            // Получаем параметры
            $ingredientIds = $request->input('ingredients');
            $strictMode = $request->input('strict', false);
            $limit = $request->input('limit', 10);

            // 1. Получаем все рецепты из Recipe Service
            $recipesResponse = Http::timeout(3)
                ->get(env('RECIPE_SERVICE_URL') . '/api/recipes');

            if (!$recipesResponse->successful()) {
                Log::error('Recipe service unavailable', [
                    'status' => $recipesResponse->status(),
                    'response' => $recipesResponse->body()
                ]);
                return response()->json(['error' => 'Recipe service unavailable'], 502);
            }

            $allRecipes = $recipesResponse->json();

            // 2. Получаем детали ингредиентов из Ingredient Service
            $ingredientsResponse = Http::timeout(3)
                ->get(env('INGREDIENT_SERVICE_URL') . '/api/ingredients', [
                    'ids' => implode(',', $ingredientIds)
                ]);

            if (!$ingredientsResponse->successful()) {
                Log::error('Ingredient service unavailable', [
                    'status' => $ingredientsResponse->status(),
                    'response' => $ingredientsResponse->body()
                ]);
                return response()->json(['error' => 'Ingredient service unavailable'], 502);
            }

            $ingredientsData = $ingredientsResponse->json();

            // 3. Фильтрация и ранжирование рецептов
            $processedRecipes = collect($allRecipes)->map(function ($recipe) use ($ingredientIds, $ingredientsData) {
                $recipeIngredients = $recipe['ingredients'] ?? [];
                
                // Находим совпадения ингредиентов
                $matchedIds = array_intersect($recipeIngredients, $ingredientIds);
                $matchPercent = count($matchedIds) / max(1, count($recipeIngredients)) * 100;
                
                // Находим недостающие ингредиенты
                $missingIds = array_diff($recipeIngredients, $ingredientIds);
                $missingIngredients = collect($missingIds)->map(function ($id) use ($ingredientsData) {
                    return collect($ingredientsData)->firstWhere('id', $id);
                })->filter()->all();

                return [
                    'id' => $recipe['id'],
                    'name' => $recipe['name'],
                    'description' => $recipe['description'],
                    'match_percent' => round($matchPercent, 2),
                    'missing_ingredients' => $missingIngredients,
                    'original_recipe' => $recipe // Полные данные рецепта
                ];
            })->sortByDesc('match_percent');

            // 4. Применяем strict mode если нужно
            if ($strictMode) {
                $processedRecipes = $processedRecipes->where('match_percent', 100);
            }

            // 5. Применяем лимит
            $result = $processedRecipes->take($limit)->values();

            return response()->json([
                'success' => true,
                'data' => $result,
                'meta' => [
                    'total_recipes' => count($allRecipes),
                    'matched_recipes' => $result->count(),
                    'strict_mode' => $strictMode,
                    'requested_ingredients' => $ingredientIds
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Search error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}