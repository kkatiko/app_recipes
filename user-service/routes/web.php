<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->get('/', function () use ($router) {
    return $router->app->version();
});

/*
$router->group(['prefix' => 'api/users'], function () use ($router) {
    $router->post('/{id}/ingredients', 'UserIngredientController@store');
    $router->get('/{id}/ingredients', 'UserIngredientController@index');
});
*/

$router->group(['prefix' => 'api/users'], function () use ($router) {
    $router->post('/', 'UserController@register');
    $router->post('/{userId}/ingredients', 'UserController@addIngredient');
    $router->get('/{userId}/ingredients', 'UserController@getUserIngredients');
    $router->delete('/{userId}/ingredients/{ingredientId}', 'UserController@removeIngredient');
});