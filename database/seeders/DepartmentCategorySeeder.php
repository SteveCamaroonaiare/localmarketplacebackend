<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class DepartmentCategorySeeder extends Seeder
{
    public function run()
    {
        DB::table('department_category')->truncate();

        $departments = Department::where('slug', '!=', 'all')->get()->keyBy('slug');
        $categories = Category::all()->keyBy('name');

        // MAPPING COMPLET DÉPARTEMENTS ↔ CATÉGORIES
        $mapping = [
            'hommes' => [
                'Mode Homme', 'Bijoux & Montres', 'Sports & Loisirs', 
                'Automobile & Accessoires', 'Beauté & Santé'
            ],
            'femmes' => [
                'Mode Femme', 'Bijoux & Montres', 'Sports & Loisirs', 
                'Beauté & Santé', 'Maison & Jardin', 'Livres & Papeterie'
            ],
            'enfants' => [
                'Mode Enfant', 'Jouets & Jeux', 'Sports & Loisirs',
                'Livres & Papeterie'
            ],
            'bijoux' => [
                'Bijoux & Montres'
            ],
            'electronique' => [
                'Électronique & Technologies', 'Automobile & Accessoires'
            ],
            'sport' => [
                'Sports & Loisirs', 'Mode Homme', 'Mode Femme', 'Mode Enfant'
            ],
            'maison' => [
                'Maison & Jardin', 'Beauté & Santé', 'Animaux & Accessoires'
            ],
            'beaute' => [
                'Beauté & Santé', 'Mode Femme', 'Bijoux & Montres'
            ],
            'automobile' => [
                'Automobile & Accessoires', 'Électronique & Technologies'
            ],
            'livres' => [
                'Livres & Papeterie', 'Jouets & Jeux'
            ],
            'jouets' => [
                'Jouets & Jeux', 'Mode Enfant'
            ],
            'animaux' => [
                'Animaux & Accessoires', 'Maison & Jardin'
            ],
        ];

        $associationCount = 0;

        foreach ($mapping as $deptSlug => $categoryNames) {
            $department = $departments[$deptSlug] ?? null;
            if (!$department) {
                $this->command->warn("Département $deptSlug non trouvé!");
                continue;
            }
            
            foreach ($categoryNames as $index => $categoryName) {
                $category = $categories[$categoryName] ?? null;
                if (!$category) {
                    $this->command->warn("Catégorie $categoryName non trouvée!");
                    continue;
                }
                
                // Associer la catégorie au département
                $department->categories()->attach($category->id, ['order' => $index + 1]);
                $associationCount++;
                
                $this->command->info("✅ Associé: {$department->name} -> {$category->name}");
            }
        }

        $this->command->info("🎉 $associationCount associations département-catégorie créées");
        
        // Afficher le résumé
        $this->command->info("\n📊 RÉSUMÉ DES ASSOCIATIONS:");
        foreach ($departments as $department) {
            $count = $department->categories()->count();
            $this->command->info("   {$department->name}: $count catégories");
        }
    }
}