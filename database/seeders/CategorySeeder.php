<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Category::truncate();
        SubCategory::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $categories = [
            [
                'name' => 'Électronique & Technologies',
                'icon' => '📱',
                'sub_categories' => [
                    ['name' => 'Smartphones & Téléphones', 'description' => 'Smartphones, téléphones portables et accessoires', 'image' => '/assets/electronics/phones.jpg'],
                    ['name' => 'Ordinateurs & Accessoires', 'description' => 'PC portables, ordinateurs de bureau, composants', 'image' => '/assets/electronics/computers.jpg'],
                    ['name' => 'Audio & Écouteurs', 'description' => 'Casques, écouteurs, enceintes Bluetooth', 'image' => '/assets/electronics/audio.jpg'],
                    ['name' => 'Gaming & Console', 'description' => 'Consoles de jeux, manettes, jeux vidéo', 'image' => '/assets/electronics/gaming.jpg']
                ]
            ],
            [
                'name' => 'Mode & Vêtements',
                'icon' => '👗',
                'sub_categories' => [
                    ['name' => 'Vêtements Hommes', 'description' => 'T-shirts, chemises, pantalons, costumes', 'image' => '/assets/fashion/men-clothing.jpg'],
                    ['name' => 'Vêtements Femmes', 'description' => 'Robes, jupes, tops, ensembles', 'image' => '/assets/fashion/women-clothing.jpg'],
                    ['name' => 'Vêtements Enfants', 'description' => 'Vêtements pour bébés et enfants', 'image' => '/assets/fashion/kids-clothing.jpg'],
                    ['name' => 'Chaussures', 'description' => 'Chaussures pour hommes, femmes et enfants', 'image' => '/assets/fashion/shoes.jpg'],
                    ['name' => 'Accessoires Mode', 'description' => 'Sacs, montres, bijoux, ceintures', 'image' => '/assets/fashion/accessories.jpg']
                ]
            ],
            [
                'name' => 'Maison & Jardin',
                'icon' => '🏠',
                'sub_categories' => [
                    ['name' => 'Meubles & Décoration', 'description' => 'Canapés, lits, tables, décoration intérieure', 'image' => '/assets/home/furniture.jpg'],
                    ['name' => 'Électroménager', 'description' => 'Réfrigérateurs, machines à laver, cuisinières', 'image' => '/assets/home/appliances.jpg'],
                    ['name' => 'Cuisine & Art de la Table', 'description' => 'Ustensiles, vaisselle, appareils de cuisine', 'image' => '/assets/home/kitchen.jpg'],
                    ['name' => 'Jardin & Extérieur', 'description' => 'Mobilier de jardin, outils, barbecue', 'image' => '/assets/home/garden.jpg']
                ]
            ],
            [
                'name' => 'Beauté & Santé',
                'icon' => '💄',
                'sub_categories' => [
                    ['name' => 'Cosmétiques & Maquillage', 'description' => 'Produits de beauté, maquillage, soins visage', 'image' => '/assets/beauty/cosmetics.jpg'],
                    ['name' => 'Soins Corporels', 'description' => 'Crèmes, lotions, produits de douche', 'image' => '/assets/beauty/body-care.jpg'],
                    ['name' => 'Parfums & Fragrances', 'description' => 'Parfums, eaux de toilette, diffuseurs', 'image' => '/assets/beauty/fragrances.jpg'],
                    ['name' => 'Santé & Bien-être', 'description' => 'Compléments alimentaires, matériel médical', 'image' => '/assets/beauty/health.jpg']
                ]
            ],
            [
                'name' => 'Sports & Loisirs',
                'icon' => '⚽',
                'sub_categories' => [
                    ['name' => 'Équipement Sportif', 'description' => 'Matériel de fitness, sports collectifs', 'image' => '/assets/sports/equipment.jpg'],
                    ['name' => 'Vêtements Sport', 'description' => 'Tenues de sport, chaussures de running', 'image' => '/assets/sports/clothing.jpg'],
                    ['name' => 'Plein Air & Randonnée', 'description' => 'Tentes, sacs à dos, équipement camping', 'image' => '/assets/sports/outdoor.jpg'],
                    ['name' => 'Vélos & Accessoires', 'description' => 'Vélos, casques, équipement cyclisme', 'image' => '/assets/sports/bikes.jpg']
                ]
            ],
            [
                'name' => 'Enfants & Bébés',
                'icon' => '👶',
                'sub_categories' => [
                    ['name' => 'Jouets & Jeux', 'description' => 'Jouets éducatifs, jeux de société, poupées', 'image' => '/assets/kids/toys.jpg'],
                    ['name' => 'Puériculture', 'description' => 'Poussettes, sièges auto, articles bébé', 'image' => '/assets/kids/baby-care.jpg'],
                    ['name' => 'Fournitures Scolaires', 'description' => 'Cartables, cahiers, stylos, calculatrices', 'image' => '/assets/kids/school.jpg']
                ]
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = Category::create([
                'name' => $categoryData['name'],
                'icon' => $categoryData['icon']
            ]);

            foreach ($categoryData['sub_categories'] as $subCategoryData) {
                SubCategory::create([
                    'name' => $subCategoryData['name'],
                    'description' => $subCategoryData['description'],
                    'image' => $subCategoryData['image'],
                    'category_id' => $category->id
                ]);
            }
        }

        $this->command->info('Catégories créées: ' . Category::count());
        $this->command->info('Sous-catégories créées: ' . SubCategory::count());
    }
}