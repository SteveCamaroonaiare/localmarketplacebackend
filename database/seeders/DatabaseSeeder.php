<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\ColorVariant;
use App\Models\Size;
use App\Models\VariantImage;
use App\Models\Department;
use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        Schema::disableForeignKeyConstraints();

        Category::truncate();
        SubCategory::truncate();
        DB::table('department_category')->truncate();
        Department::truncate();
        SubscriptionPlan::truncate();

        Schema::enableForeignKeyConstraints();

        // 1. Catégories Principales
        $categories = [
            [
                'name' => 'Électronique & Technologies',
                'icon' => '📱',
                'sub_categories' => [
                    [
                        'name' => 'Smartphones & Téléphones',
                        'description' => 'Smartphones, téléphones portables et accessoires',
                        'image' => '/assets/phone.jfif'
                    ],
                    [
                        'name' => 'Ordinateurs & Accessoires',
                        'description' => 'PC portables, ordinateurs de bureau, composants',
                        'image' => '/assets/computers.jfif'
                    ],
                    [
                        'name' => 'Audio & Écouteurs',
                        'description' => 'Casques, écouteurs, enceintes Bluetooth',
                        'image' => '/assets/audio.jfif'
                    ],
                    [
                        'name' => 'Gaming & Console',
                        'description' => 'Consoles de jeux, manettes, jeux vidéo',
                        'image' => '/assets/gaming.jfif'
                    ]
                ]
            ],
            [
                'name' => 'Mode & Vêtements',
                'icon' => '👗',
                'sub_categories' => [
                    [
                        'name' => 'Vêtements Hommes',
                        'description' => 'T-shirts, chemises, pantalons, costumes',
                        'image' => '/assets/men-clothing.jfif'
                    ],
                    [
                        'name' => 'Vêtements Femmes',
                        'description' => 'Robes, jupes, tops, ensembles',
                        'image' => '/assets/women-clothing.jfif'
                    ],
                    [
                        'name' => 'Vêtements Enfants',
                        'description' => 'Vêtements pour garçons et filles',
                        'image' => '/assets/kids-clothing.jfif'
                    ],
                    [
                        'name' => 'Chaussures',
                        'description' => 'Chaussures hommes, femmes et enfants',
                        'image' => '/assets/shoes.jfif'
                    ],
                    [
                        'name' => 'Accessoires Mode',
                        'description' => 'Sacs, montres, bijoux, ceintures',
                        'image' => '/assets/accessories.jfif'
                    ]
                ]
            ],
            [
                'name' => 'Maison & Décoration',
                'icon' => '🏠',
                'sub_categories' => [
                    [
                        'name' => 'Meubles & Décoration',
                        'description' => 'Meubles, tableaux, accessoires déco',
                        'image' => '/assets/furniture.jfif'
                    ],
                    [
                        'name' => 'Électroménager',
                        'description' => 'Réfrigérateurs, machines à laver, climatiseurs',
                        'image' => '/assets/imagesappliances.jfif'
                    ],
                    [
                        'name' => 'Cuisine & Art de la Table',
                        'description' => 'Ustensiles, vaisselle, appareils de cuisine',
                        'image' => '/assets/kitchen.jfif'
                    ],
                    [
                        'name' => 'Jardin & Extérieur',
                        'description' => 'Plantes, outils de jardinage, mobilier extérieur',
                        'image' => '/assets/garden.jfif'
                    ]
                ]
            ],
            [
                'name' => 'Beauté & Bien-être',
                'icon' => '💄',
                'sub_categories' => [
                    [
                        'name' => 'Cosmétiques & Maquillage',
                        'description' => 'Fond de teint, rouge à lèvres, mascara',
                        'image' => '/assets/cosmetics.jfif'
                    ],
                    [
                        'name' => 'Soins Corporels',
                        'description' => 'Crèmes, lotions, savons, soins cheveux',
                        'image' => '/assets/body-care.jfif'
                    ],
                    [
                        'name' => 'Parfums & Fragrances',
                        'description' => 'Parfums hommes et femmes',
                        'image' => '/assets/fragrances.jfif'
                    ],
                    [
                        'name' => 'Santé & Bien-être',
                        'description' => 'Compléments alimentaires, matériel médical',
                        'image' => '/assets/health.jfif'
                    ]
                ]
            ],
            [
                'name' => 'Sport & Loisirs',
                'icon' => '⚽',
                'sub_categories' => [
                    [
                        'name' => 'Équipement Sportif',
                        'description' => 'Ballons, raquettes, équipements fitness',
                        'image' => '/assets/equipment.jfif'
                    ],
                    [
                        'name' => 'Vêtements Sport',
                        'description' => 'Maillots, shorts, chaussures de sport',
                        'image' => '/assets/clothing.jfif'
                    ],
                    [
                        'name' => 'Plein Air & Randonnée',
                        'description' => 'Tentes, sacs de randonnée, accessoires outdoor',
                        'image' => '/assets/outdoor.jfif'
                    ],
                    [
                        'name' => 'Vélos & Accessoires',
                        'description' => 'Vélos, trottinettes, accessoires cyclisme',
                        'image' => '/assets/bikes.png'
                    ]
                ]
            ],
            [
                'name' => 'Enfants & Bébés',
                'icon' => '🧸',
                'sub_categories' => [
                    [
                        'name' => 'Jouets & Jeux',
                        'description' => 'Jouets éducatifs, jeux de société, peluches',
                        'image' => '/assets/toys.jfif'
                    ],
                    [
                        'name' => 'Puériculture',
                        'description' => 'Poussettes, sièges auto, accessoires bébé',
                        'image' => '/assets/school.jfif'
                    ],
                    [
                        'name' => 'Fournitures Scolaires',
                        'description' => 'Cahiers, stylos, sacs à dos, matériel scolaire',
                        'image' => '/assets/school.jfif'
                    ]
                ]
            ],
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

        // 2. Départements
        $departments = [
            ['name' => 'Électronique', 'slug' => 'electronique', 'order' => 1, 'active' => true],
            ['name' => 'Mode',         'slug' => 'mode',         'order' => 2, 'active' => true],
            ['name' => 'Maison',       'slug' => 'maison',       'order' => 3, 'active' => true],
            ['name' => 'Beauté',       'slug' => 'beaute',       'order' => 4, 'active' => true],
            ['name' => 'Sport',        'slug' => 'sport',        'order' => 5, 'active' => true],
            ['name' => 'Enfants',      'slug' => 'enfants',      'order' => 6, 'active' => true],
        ];

        foreach ($departments as $department) {
            Department::create($department);
        }

        $this->command->info('✅ Départements créés avec succès');

        // 3. Plans d'abonnement
        $this->call([
            SubscriptionPlanSeeder::class,
        ]);

        $this->command->info('Base de données peuplée avec succès!');
        $this->command->info('Catégories: ' . Category::count());
        $this->command->info('Sous-catégories: ' . SubCategory::count());
        $this->command->info('Produits: ' . Product::count());
    }
}