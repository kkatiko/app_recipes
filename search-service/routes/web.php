<?php

/** @var Laravel\Lumen\Routing\Router $router */

$router->group(['prefix' => 'api'], function () use ($router) {
    // Поиск рецептов
    $router->get('/recipes/search', 'SearchController@searchRecipes');
    
    // Health check endpoint
    $router->get('/health', function () use ($router) {
        return response()->json([
            'status' => 'ok',
            'services' => [
                'recipe_service' => env('RECIPE_SERVICE_URL'),
                'ingredient_service' => env('INGREDIENT_SERVICE_URL')
            ]
        ]);
    });
});