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

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        
        // Nettoyer les tables
        Category::truncate();
        SubCategory::truncate();
        Product::truncate();
        ColorVariant::truncate();
        Size::truncate();
        VariantImage::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
            Department::truncate();
        DB::table('department_category')->truncate();

        // 1. Catégories Principales
        $categories = [
            [
                'name' => 'Électronique & Technologies',
                'icon' => '📱',
                'sub_categories' => [
                    [
                        'name' => 'Smartphones & Téléphones',
                        'description' => 'Smartphones, téléphones portables et accessoires',
                        'image' => '/assets/electronics/phones.jpg'
                    ],
                    [
                        'name' => 'Ordinateurs & Accessoires',
                        'description' => 'PC portables, ordinateurs de bureau, composants',
                        'image' => '/assets/electronics/computers.jpg'
                    ],
                    [
                        'name' => 'Audio & Écouteurs',
                        'description' => 'Casques, écouteurs, enceintes Bluetooth',
                        'image' => '/assets/electronics/audio.jpg'
                    ],
                    [
                        'name' => 'Gaming & Console',
                        'description' => 'Consoles de jeux, manettes, jeux vidéo',
                        'image' => '/assets/electronics/gaming.jpg'
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
                        'image' => '/assets/fashion/men-clothing.jpg'
                    ],
                    [
                        'name' => 'Vêtements Femmes',
                        'description' => 'Robes, jupes, tops, ensembles',
                        'image' => '/assets/fashion/women-clothing.jpg'
                    ],
                    [
                        'name' => 'Vêtements Enfants',
                        'description' => 'Vêtements pour bébés et enfants',
                        'image' => '/assets/fashion/kids-clothing.jpg'
                    ],
                    [
                        'name' => 'Chaussures',
                        'description' => 'Chaussures pour hommes, femmes et enfants',
                        'image' => '/assets/fashion/shoes.jpg'
                    ],
                    [
                        'name' => 'Accessoires Mode',
                        'description' => 'Sacs, montres, bijoux, ceintures',
                        'image' => '/assets/fashion/accessories.jpg'
                    ]
                ]
            ],
            [
                'name' => 'Maison & Jardin',
                'icon' => '🏠',
                'sub_categories' => [
                    [
                        'name' => 'Meubles & Décoration',
                        'description' => 'Canapés, lits, tables, décoration intérieure',
                        'image' => '/assets/home/furniture.jpg'
                    ],
                    [
                        'name' => 'Électroménager',
                        'description' => 'Réfrigérateurs, machines à laver, cuisinières',
                        'image' => '/assets/home/appliances.jpg'
                    ],
                    [
                        'name' => 'Cuisine & Art de la Table',
                        'description' => 'Ustensiles, vaisselle, appareils de cuisine',
                        'image' => '/assets/home/kitchen.jpg'
                    ],
                    [
                        'name' => 'Jardin & Extérieur',
                        'description' => 'Mobilier de jardin, outils, barbecue',
                        'image' => '/assets/home/garden.jpg'
                    ]
                ]
            ],
            [
                'name' => 'Beauté & Santé',
                'icon' => '💄',
                'sub_categories' => [
                    [
                        'name' => 'Cosmétiques & Maquillage',
                        'description' => 'Produits de beauté, maquillage, soins visage',
                        'image' => '/assets/beauty/cosmetics.jpg'
                    ],
                    [
                        'name' => 'Soins Corporels',
                        'description' => 'Crèmes, lotions, produits de douche',
                        'image' => '/assets/beauty/body-care.jpg'
                    ],
                    [
                        'name' => 'Parfums & Fragrances',
                        'description' => 'Parfums, eaux de toilette, diffuseurs',
                        'image' => '/assets/beauty/fragrances.jpg'
                    ],
                    [
                        'name' => 'Santé & Bien-être',
                        'description' => 'Compléments alimentaires, matériel médical',
                        'image' => '/assets/beauty/health.jpg'
                    ]
                ]
            ],
            [
                'name' => 'Sports & Loisirs',
                'icon' => '⚽',
                'sub_categories' => [
                    [
                        'name' => 'Équipement Sportif',
                        'description' => 'Matériel de fitness, sports collectifs',
                        'image' => '/assets/sports/equipment.jpg'
                    ],
                    [
                        'name' => 'Vêtements Sport',
                        'description' => 'Tenues de sport, chaussures de running',
                        'image' => '/assets/sports/clothing.jpg'
                    ],
                    [
                        'name' => 'Plein Air & Randonnée',
                        'description' => 'Tentes, sacs à dos, équipement camping',
                        'image' => '/assets/sports/outdoor.jpg'
                    ],
                    [
                        'name' => 'Vélos & Accessoires',
                        'description' => 'Vélos, casques, équipement cyclisme',
                        'image' => '/assets/sports/bikes.jpg'
                    ]
                ]
            ],
            [
                'name' => 'Enfants & Bébés',
                'icon' => '👶',
                'sub_categories' => [
                    [
                        'name' => 'Jouets & Jeux',
                        'description' => 'Jouets éducatifs, jeux de société, poupées',
                        'image' => '/assets/kids/toys.jpg'
                    ],
                    [
                        'name' => 'Puériculture',
                        'description' => 'Poussettes, sièges auto, articles bébé',
                        'image' => '/assets/kids/baby-care.jpg'
                    ],
                    [
                        'name' => 'Fournitures Scolaires',
                        'description' => 'Cartables, cahiers, stylos, calculatrices',
                        'image' => '/assets/kids/school.jpg'
                    ]
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

        // Désactiver les contraintes FK
    Schema::disableForeignKeyConstraints();

    // Nettoyer les tables
    DB::table('department_category')->truncate();
    Department::truncate();
    // Réactiver les FK
    Schema::enableForeignKeyConstraints();
        // Créer les départements
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

        // Associer les catégories aux départements
    
        // Récupérer les départements par slug
 $departmentElectronique = Department::where('slug', 'electronique')->firstOrFail();
        $departmentMode         = Department::where('slug', 'mode')->firstOrFail();
        $departmentMaison       = Department::where('slug', 'maison')->firstOrFail();
        $departmentBeaute       = Department::where('slug', 'beaute')->firstOrFail();
        $departmentSports        = Department::where('slug', 'sport')->firstOrFail();
        $departmentEnfants      = Department::where('slug', 'enfants')->firstOrFail(); 
    
    // 2. Produits avec variantes détaillées
        $productsData = [
            // ÉLECTRONIQUE - Smartphones
            [
                'name' => 'Smartphone Samsung Galaxy S24 Ultra',
                'description' => 'Flagship Samsung avec écran Dynamic AMOLED 2X, appareil photo 200MP, processeur Snapdragon 8 Gen 3. Parfait pour la photographie professionnelle et le gaming intensif.',
                'price' => '750000',
                'image' => '/assets/products/samsung-s24.jpg',
                'rating' => 4.8,
                'reviews' => 342,
                'seller' => 'TechStore Cameroun',
                'location' => 'Douala, Cameroun',
                'badge' => 'Livraison Express',
                'category_id' => 1,
                'sub_category_id' => 1,
                'department_id' => $departmentElectronique->id,
                'stock_quantity' => 45,
                'sexe' => null, 
                'age_group' => null,
                'restock_frequency' => '2 semaines',
                'return_policy' => true,
                'payment_on_delivery' => true,
                'has_color_variants' => true,
                'default_color' => 'Noir Phantom',
                'default_color_code' => '#000000',
                'variants' => [
                    [
                        'color_name' => 'Noir Phantom',
                        'color_code' => '#000000',
                        'price' => '750000',
                        'original_price' => '850000',
                        'stock_quantity' => 20,
                        'images' => [
                            '/assets/products/samsung-s24-black-1.jpg',
                            '/assets/products/samsung-s24-black-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '256GB', 'price' => '750000', 'original_price' => '850000', 'stock_quantity' => 10],
                            ['name' => '512GB', 'price' => '820000', 'original_price' => '920000', 'stock_quantity' => 8],
                            ['name' => '1TB', 'price' => '950000', 'original_price' => '1050000', 'stock_quantity' => 2]
                        ]
                    ],
                    [
                        'color_name' => 'Bleu Titanium',
                        'color_code' => '#4682B4',
                        'price' => '760000',
                        'original_price' => '860000',
                        'stock_quantity' => 15,
                        'images' => [
                            '/assets/products/samsung-s24-blue-1.jpg',
                            '/assets/products/samsung-s24-blue-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '256GB', 'price' => '760000', 'original_price' => '860000', 'stock_quantity' => 8],
                            ['name' => '512GB', 'price' => '830000', 'original_price' => '930000', 'stock_quantity' => 5],
                            ['name' => '1TB', 'price' => '960000', 'original_price' => '1060000', 'stock_quantity' => 2]
                        ]
                    ]
                ]
            ],

            // MODE - Vêtements Hommes
            [
                'name' => 'Costume Business Classique Homme',
                'description' => 'Costume élégant pour occasions professionnelles. Tissu en laine premium, coupe moderne, confort exceptionnel. Idéal pour entretiens et réunions importantes.',
                'price' => '85000',
                'original_price' => '120000',
                'image' => '/assets/products/men-suit.jpg',
                'rating' => 4.6,
                'reviews' => 189,
                'seller' => 'Fashion Elite',
                'location' => 'Yaoundé, Cameroun',
                'badge' => 'Top Vente',
                'category_id' => 2,
                'sub_category_id' => 5,
                'department_id' => $departmentMode->id,   
                'stock_quantity' => 78,
                'sexe' => 'H', // HOMME
                'age_group' => 'adult',
                'restock_frequency' => '1 mois',
                'return_policy' => true,
                'payment_on_delivery' => true,
                'has_color_variants' => true,
                'default_color' => 'Bleu Navy',
                'default_color_code' => '#000080',
                'variants' => [
                    [
                        'color_name' => 'Bleu Navy',
                        'color_code' => '#000080',
                        'price' => '85000',
                        'original_price' => '120000',
                        'stock_quantity' => 30,
                        'images' => [
                            '/assets/products/suit-navy-1.jpg',
                            '/assets/products/suit-navy-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => 'S', 'price' => '85000', 'original_price' => '120000', 'stock_quantity' => 8],
                            ['name' => 'M', 'price' => '85000', 'original_price' => '120000', 'stock_quantity' => 10],
                            ['name' => 'L', 'price' => '85000', 'original_price' => '120000', 'stock_quantity' => 7],
                            ['name' => 'XL', 'price' => '87000', 'original_price' => '122000', 'stock_quantity' => 5]
                        ]
                    ],
                    [
                        'color_name' => 'Noir Classique',
                        'color_code' => '#000000',
                        'price' => '88000',
                        'original_price' => '125000',
                        'stock_quantity' => 25,
                        'images' => [
                            '/assets/products/suit-black-1.jpg',
                            '/assets/products/suit-black-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => 'S', 'price' => '88000', 'original_price' => '125000', 'stock_quantity' => 6],
                            ['name' => 'M', 'price' => '88000', 'original_price' => '125000', 'stock_quantity' => 8],
                            ['name' => 'L', 'price' => '88000', 'original_price' => '125000', 'stock_quantity' => 7],
                            ['name' => 'XL', 'price' => '90000', 'original_price' => '127000', 'stock_quantity' => 4]
                        ]
                    ]
                ]
            ],

            // MAISON - Électroménager
            [
                'name' => 'Réfrigérateur Samsung Twin Cooling Plus',
                'description' => 'Réfrigérateur américain 500L avec technologie Twin Cooling, distributeur d\'eau et de glace, classe énergétique A++. Design élégant et fonctionnalités avancées.',
                'price' => '650000',
                'original_price' => '780000',
                'image' => '/assets/products/fridge-samsung.jpg',
                'rating' => 4.7,
                'reviews' => 234,
                'seller' => 'ElectroHome',
                'location' => 'Douala, Cameroun',
                'badge' => 'Économie d\'énergie',
                'category_id' => 3,
                'sub_category_id' => 10,
                'department_id' => $departmentMaison->id,
                'stock_quantity' => 12,
                 'sexe' => null, 
                'age_group' => null,
                'restock_frequency' => '2 mois',
                'return_policy' => true,
                'payment_on_delivery' => false,
                'has_color_variants' => true,
                'default_color' => 'Inox Brossé',
                'default_color_code' => '#C0C0C0',
                'variants' => [
                    [
                        'color_name' => 'Inox Brossé',
                        'color_code' => '#C0C0C0',
                        'price' => '650000',
                        'original_price' => '780000',
                        'stock_quantity' => 8,
                        'images' => [
                            '/assets/products/fridge-stainless-1.jpg',
                            '/assets/products/fridge-stainless-2.jpg'
                        ],
                        'sizes' => []
                    ],
                    [
                        'color_name' => 'Noir Mat',
                        'color_code' => '#333333',
                        'price' => '670000',
                        'original_price' => '800000',
                        'stock_quantity' => 4,
                        'images' => [
                            '/assets/products/fridge-black-1.jpg',
                            '/assets/products/fridge-black-2.jpg'
                        ],
                        'sizes' => []
                    ]
                ]
            ],

            // BEAUTÉ - Parfums
            [
                'name' => 'Parfum Channel N°5 Eau de Parfum',
                'description' => 'Parfum iconique aux notes florales-aldéhydées. Flacon collector, sillage exceptionnel, tenue longue durée. Le parfum de légende depuis 1921.',
                'price' => '125000',
                'original_price' => '150000',
                'image' => '/assets/products/chanel-perfume.jpg',
                'rating' => 4.9,
                'reviews' => 456,
                'seller' => 'Luxury Beauty',
                'location' => 'Yaoundé, Cameroun',
                'badge' => 'Produit Premium',
                'category_id' => 4,
                'sub_category_id' => 16,
                'department_id' => $departmentBeaute->id,
                'stock_quantity' => 25,
                 'sexe' => null, 
                'age_group' => null,
                'restock_frequency' => '3 semaines',
                'return_policy' => false,
                'payment_on_delivery' => true,
                'has_color_variants' => false,
                'default_color' => 'Ambre',
                'default_color_code' => '#D4AF37',
                'variants' => [],
                'images' => [
                    '/assets/products/chanel-perfume-1.jpg',
                    '/assets/products/chanel-perfume-2.jpg',
                    '/assets/products/chanel-perfume-3.jpg'
                ]
            ],

            // SPORTS - Équipement
            [
                'name' => 'Tapis de Course Pliable Electrique',
                'description' => 'Tapis de course motorisé 2.5HP, pliable, écran LCD, 12 programmes automatiques. Parfait pour fitness à domicile, supporte jusqu\'à 120kg.',
                'price' => '320000',
                'original_price' => '450000',
                'image' => '/assets/products/treadmill.jpg',
                'rating' => 4.5,
                'reviews' => 167,
                'seller' => 'Sport Equipment',
                'location' => 'Douala, Cameroun',
                'badge' => 'Promo Fitness',
                'category_id' => 5,
                'sub_category_id' => 17,
                'department_id' => $departmentSports->id,
                'stock_quantity' => 8,
                 'sexe' => null, 
                'age_group' => null,
                'restock_frequency' => '1 mois',
                'return_policy' => true,
                'payment_on_delivery' => false,
                'has_color_variants' => true,
                'default_color' => 'Noir et Rouge',
                'default_color_code' => '#FF0000',
                'variants' => [
                    [
                        'color_name' => 'Noir et Rouge',
                        'color_code' => '#FF0000',
                        'price' => '320000',
                        'original_price' => '450000',
                        'stock_quantity' => 5,
                        'images' => [
                            '/assets/products/treadmill-red-1.jpg',
                            '/assets/products/treadmill-red-2.jpg'
                        ],
                        'sizes' => []
                    ],
                    [
                        'color_name' => 'Noir et Bleu',
                        'color_code' => '#0000FF',
                        'price' => '325000',
                        'original_price' => '455000',
                        'stock_quantity' => 3,
                        'images' => [
                            '/assets/products/treadmill-blue-1.jpg',
                            '/assets/products/treadmill-blue-2.jpg'
                        ],
                        'sizes' => []
                    ]
                ]
            ],

            // ENFANTS - Jouets
            [
                'name' => 'Set de Construction LEGO City',
                'description' => 'Set LEGO 1500 pièces avec figurines, véhicules et bâtiments. Développe la créativité et la motricité fine. Âge recommandé: 6-12 ans.',
                'price' => '45000',
                'original_price' => '65000',
                'image' => '/assets/products/lego-set.jpg',
                'rating' => 4.8,
                'reviews' => 289,
                'seller' => 'Toy World',
                'location' => 'Yaoundé, Cameroun',
                'badge' => 'Éducatif',
                'category_id' => 6,
                
                'sub_category_id' => 22,
                'department_id' => $departmentEnfants->id,
                'stock_quantity' => 34,
                 'sexe' => null, 
                'age_group' => 'child',
                'restock_frequency' => '2 semaines',
                'return_policy' => true,
                'payment_on_delivery' => true,
                'has_color_variants' => false,
                'default_color' => 'Multicolore',
                'default_color_code' => null,
                'variants' => [],
                'images' => [
                    '/assets/products/lego-1.jpg',
                    '/assets/products/lego-2.jpg',
                    '/assets/products/lego-3.jpg'
                ]
            ]
        ];

        // Création des produits
        foreach ($productsData as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'original_price' => $productData['original_price']?? null,
                'image' => $productData['image'],
                'rating' => $productData['rating'],
                'reviews' => $productData['reviews'],
                'seller' => $productData['seller'],
                'location' => $productData['location'],
                'badge' => $productData['badge'],
                'category_id' => $productData['category_id'],
                'sub_category_id' => $productData['sub_category_id'],
                'department_id' => $productData['department_id'],
                'stock_quantity' => $productData['stock_quantity'],
                'restock_frequency' => $productData['restock_frequency'],
                 'sexe' => $productData['sexe'] ?? null, 
                'age_group' => $productData['age_group'] ?? null,
                'return_policy' => $productData['return_policy'],
                'payment_on_delivery' => $productData['payment_on_delivery'],
                'has_color_variants' => $productData['has_color_variants'],
                'default_color' => $productData['default_color'],
                'default_color_code' => $productData['default_color_code'],
            ]);

            // Gestion des variantes de couleur
            if ($productData['has_color_variants'] && !empty($productData['variants'])) {
                foreach ($productData['variants'] as $variantData) {
                    $variant = ColorVariant::create([
                        'product_id' => $product->id,
                        'color_name' => $variantData['color_name'],
                        'color_code' => $variantData['color_code'],
                        'price' => $variantData['price'],
                        'stock_quantity' => $variantData['stock_quantity'],
                        'available' => true
                    ]);

                    // Images pour la variante
                    foreach ($variantData['images'] as $index => $imageUrl) {
                        VariantImage::create([
                            'color_variant_id' => $variant->id,
                            'image_url' => $imageUrl,
                            'is_main' => ($index === 0) ? 1 : 0
                        ]);
                    }

                    // Tailles pour la variante
                    foreach ($variantData['sizes'] as $sizeData) {
                        Size::create([
                            'product_id' => $product->id,
                            'color_variant_id' => $variant->id,
                            'name' => $sizeData['name'],
                            'price' => $sizeData['price'],
                            'stock_quantity' => $sizeData['stock_quantity'],
                            'available' => true
                        ]);
                    }
                }
            }

            // Images pour produits sans variantes
            if (!$productData['has_color_variants'] && isset($productData['images'])) {
                foreach ($productData['images'] as $index => $imageUrl) {
                    VariantImage::create([
                        'product_id' => $product->id,
                        'image_url' => $imageUrl,
                        'is_main' => ($index === 0) ? 1 : 0
                    ]);
                }
            }
        }

        // 3. Produits supplémentaires pour chaque sous-catégorie
        $additionalProducts = [
            // Smartphones supplémentaires
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'price' => '950000',
                'original_price' => '1100000',
                'category_id' => 1,
                'sub_category_id' => 1,
                
                'stock_quantity' => 15,
                 'sexe' => null, 
                'age_group' => null,
            ],
            [
                'name' => 'Xiaomi Redmi Note 13 Pro',
                'price' => '280000',
                'original_price' => '350000',
                'category_id' => 1,
                'sub_category_id' => 1,
                'stock_quantity' => 40,
                 'sexe' => null, 
                'age_group' => null,
            ],

            // Vêtements supplémentaires
            [
                'name' => 'Chemise Homme Coton Premium',
                'price' => '25000',
                'original_price' => '35000',
                'category_id' => 2,
                'sub_category_id' => 5,
                'stock_quantity' => 100,
                 'sexe' => 'H', 
                'age_group' => null,
            ],
            [
                'name' => 'Robe Soirée Élégante Femme',
                'price' => '75000',
                'original_price' => '95000',
                'category_id' => 2,
                'sub_category_id' => 6,
                'stock_quantity' => 25,
                'sexe' => 'F', // FEMME
                'age_group' => 'adult', // ADULTE
            ],
             [
                'name' => 'Chemise Homme Coton Premium',
                'price' => '25000',
                'original_price' => '35000',
                'category_id' => 3,
                'sub_category_id' => 6,
                'stock_quantity' => 100,
                'sexe' => 'H', // HOMME
                'age_group' => 'adult', // ADULTE
            ],
          
            [
                'name' => 'T-shirt Enfant Batman',
                'price' => '15000',
                'original_price' => '20000',
                'category_id' => 6,
                'sub_category_id' => 7,
                'stock_quantity' => 50,
                'sexe' => 'H', // MIXTE
                'age_group' => 'child', // ENFANT
            ],


            // Électroménager supplémentaires
            [
                'name' => 'Machine à Laver LG 8kg',
                'price' => '320000',
                'original_price' => '400000',
                'category_id' => 3,
                'sub_category_id' => 10,
                'stock_quantity' => 18,
                 'sexe' => null, 
                'age_group' => null,
                
            ]
        ];

        foreach ($additionalProducts as $productData) {
            Product::create([
                'name' => $productData['name'],
                'description' => 'Produit de qualité premium, livraison rapide partout au Cameroun.',
                'price' => $productData['price'],
                'original_price' => $productData['original_price'],
                'image' => '/placeholder.svg',
                'rating' => rand(40, 50) / 10,
                'reviews' => rand(50, 300),
                'seller' => 'Market237 Store',
                'location' => 'Douala, Cameroun',
                'badge' => 'Nouveau',
                'category_id' => $productData['category_id'],
                'sub_category_id' => $productData['sub_category_id'],
                'stock_quantity' => $productData['stock_quantity'],
                'sexe' => $productData['sexe'] ,
                'age_group' => $productData['age_group'] ,
                'restock_frequency' => '1-2 semaines',
                'return_policy' => true,
                'payment_on_delivery' => true,
                'has_color_variants' => false,
                'default_color' => 'Standard',
                'default_color_code' => null
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command->info('Base de données peuplée avec succès!');
        $this->command->info('Catégories: ' . Category::count());
        $this->command->info('Sous-catégories: ' . SubCategory::count());
        $this->command->info('Produits: ' . Product::count());
        $this->command->info('Variantes: ' . ColorVariant::count());
    }

}



        
    

    
    