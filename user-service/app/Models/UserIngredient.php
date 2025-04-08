<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;

class UserIngredient extends Model
{
    protected $table = 'user_ingredients';
    protected $fillable = ['user_id', 'ingredient_id', 'quantity', 'unit'];
    protected $casts = [
        'ingredient_id' => 'integer',
        'quantity' => 'float'
    ];

    /**
     * Отношение к пользователю (внутри сервиса)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * "Виртуальное" отношение к ингредиенту через API
     */
    public function ingredient()
    {
        return new class($this->ingredient_id) {
            private $ingredientId;

            public function __construct($ingredientId)
            {
                $this->ingredientId = $ingredientId;
            }

            public function get()
            {
                try {
                    $response = Http::timeout(3)
                        ->get("http://ingredient-service/api/ingredients/{$this->ingredientId}");
                    
                    return $response->successful() ? $response->json() : null;
                } catch (\Exception $e) {
                    return null;
                }
            }
        };
    }

    /**
     * Форматирование для API
     */
    public function toApiFormat()
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'ingredient' => $this->ingredient()->get(),
            'quantity' => $this->quantity,
            'unit' => $this->unit,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}