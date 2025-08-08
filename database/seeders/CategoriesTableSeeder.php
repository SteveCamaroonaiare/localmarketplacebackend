<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category; // ✅ Ajout à faire ici


class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
{
    Category::create([
        ['name' => 'Électronique', 'icon' => '📱'],
        ['name' => 'Mode & Beauté', 'icon' => '👗'],
        ['name' => 'Maison & Jardin', 'icon' => '🏠'],
        ['name' => 'Sports & Loisirs', 'icon' => '⚽'],
        ['name' => 'Alimentation', 'icon' => '🍎'],
        ['name' => 'Santé & Bien-être', 'icon' => '💊'],
    ]);
}
}
