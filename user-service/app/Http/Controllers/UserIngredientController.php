<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserIngredient;
use Illuminate\Http\Request;

class UserIngredientController extends Controller
{
    /**
     * Добавление ингредиента пользователю
     */
    public function store(Request $request, $userId)
    {
        $validated = $this->validate($request, [
            'ingredient_id' => 'required|integer',
            'quantity' => 'required|numeric',
            'unit' => 'required|string'
        ]);

        $user = User::findOrFail($userId);
        $user->ingredients()->attach($validated['ingredient_id'], [
            'quantity' => $validated['quantity'],
            'unit' => $validated['unit']
        ]);

        return response()->json(['status' => 'success'], 201);
    }

    /**
     * Получение списка ингредиентов пользователя
     */
    public function index($userId)
    {
        return User::with('ingredients')->findOrFail($userId);
    }
}