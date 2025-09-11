<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ColorVariant;
use App\Models\Size;
use App\Models\VariantImage;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Product::truncate();
        ColorVariant::truncate();
        Size::truncate();
        VariantImage::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $productsData = [
            // ÉLECTRONIQUE - Smartphones
            [
                'name' => 'Smartphone Samsung Galaxy S24 Ultra',
                'description' => 'Flagship Samsung avec écran Dynamic AMOLED 2X, appareil photo 200MP, processeur Snapdragon 8 Gen 3. Parfait pour la photographie professionnelle et le gaming intensif.',
                'price' => '750000',
                'original_price' => '850000',
                'image' => '/assets/products/samsung-s24.jpg',
                'rating' => 4.8,
                'reviews' => 342,
                'seller' => 'TechStore Cameroun',
                'location' => 'Douala, Cameroun',
                'badge' => 'Livraison Express',
                'category_id' => 1,
                'sub_category_id' => 1,
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

        foreach ($productsData as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'original_price' => $productData['original_price'],
                'image' => $productData['image'],
                'rating' => $productData['rating'],
                'reviews' => $productData['reviews'],
                'seller' => $productData['seller'],
                'location' => $productData['location'],
                'badge' => $productData['badge'],
                'category_id' => $productData['category_id'],
                'sub_category_id' => $productData['sub_category_id'],
                'stock_quantity' => $productData['stock_quantity'],
                'restock_frequency' => $productData['restock_frequency'],
                'return_policy' => $productData['return_policy'],
                'payment_on_delivery' => $productData['payment_on_delivery'],
                'has_color_variants' => $productData['has_color_variants'],
                'default_color' => $productData['default_color'],
                'default_color_code' => $productData['default_color_code'],
                'sexe' => $productData['sexe'],
                'age_group' => $productData['age_group'],
            ]);

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

                    foreach ($variantData['images'] as $index => $imageUrl) {
                        VariantImage::create([
                            'color_variant_id' => $variant->id,
                            'image_url' => $imageUrl,
                            'is_main' => ($index === 0) ? 1 : 0
                        ]);
                    }

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
        }

        $this->command->info('Produits créés: ' . Product::count());
        $this->command->info('Variantes créées: ' . ColorVariant::count());
    }
}