<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Ingredient::insert([
            ['name' => 'Помидор', 'category' => 'Овощи', 'unit' => 'шт'],
            ['name' => 'Молоко',  'category' => 'Молочные', 'unit' => 'мл'],
            ['name' => 'Яйца',    'category' => 'Бакалея', 'unit' => 'шт']
        ]);
    }
}