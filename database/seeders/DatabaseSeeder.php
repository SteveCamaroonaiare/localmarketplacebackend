<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\ColorVariant;
use App\Models\Size;
use App\Models\VariantImage;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        // 1. Catégories
        $categories = [
            ['name' => 'Électronique', 'icon' => '📱'],
            ['name' => 'Mode & Beauté', 'icon' => '👗'],
            ['name' => 'Maison & Jardin', 'icon' => '🏠'],
            ['name' => 'Sports & Loisirs', 'icon' => '⚽'],
            ['name' => 'Alimentation', 'icon' => '🍎'],
            ['name' => 'Santé & Bien-être', 'icon' => '💊'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        // 2. Produits avec différentes configurations
        $products = [
            // Smartphone avec variantes de couleur et stockage (tailles)
            [
                'name' => 'Smartphone Samsung Galaxy A54',
                'description' => 'Très bon téléphone pratique et résistant aux surcharges, batterie max',
                'price' => '185000.00',
                'original_price' => '220000.00',
                'image' => '/assets/samsung.jpg',
                'rating' => 4.5,
                'reviews' => 128,
                'seller' => 'TechStore Douala',
                'location' => 'Douala, Cameroun',
                'badge' => 'Livraison gratuite',
                'category_id' => 1,
                'stock_quantity' => 58,
                'restock_frequency' => '3 jours',
                'return_policy' => 1,
                'payment_on_delivery' => 0,
                'has_color_variants' => 1,
                'default_color' => 'Noir',
                'default_color_code' => '#000000',
                'variants' => [
                    [
                        'color_name' => 'Noir',
                        'color_code' => '#000000',
                        'price' => '185000.00',
                        'original_price' => '220000.00',
                        'images' => [
                            '/assets/samsung-noir-1.jpg',
                            '/assets/samsung-noir-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '128GB', 'price' => '185000.00','original_price' => '220000.00'],
                            ['name' => '256GB', 'price' => '210000.00','original_price' => '235000.00'],
                        ]
                    ],
                    [
                        'color_name' => 'Bleu',
                        'color_code' => '#0000FF',
                        'price' => '190000.00',
                        'original_price' => '225000.00',
                        'images' => [
                            '/assets/samsung-bleu-1.jpg',
                            '/assets/samsung-bleu-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '128GB', 'price' => '190000.00', 'original_price' => '225000.00',],
                            ['name' => '256GB', 'price' => '215000.00', 'original_price' => '255000.00',],
                        ]
                    ]
                ]
            ],
            
            // Chaussures de sport avec tailles
            [
                'name' => 'Chaussures de sport Nike Air Max',
                'description' => 'Chaussures confortables pour le sport et le quotidien',
                'price' => '75000.00',
                'original_price' => '90000.00',
                'image' => '/assets/nike.jpg',
                'rating' => 4.7,
                'reviews' => 89,
                'seller' => 'SportPlus',
                'location' => 'Yaoundé, Cameroun',
                'badge' => 'Nouvelle collection',
                'category_id' => 4,
                'stock_quantity' => 42,
                'restock_frequency' => '2 semaines',
                'return_policy' => 1,
                'payment_on_delivery' => 1,
                'has_color_variants' => 1,
                'default_color' => 'Blanc',
                'default_color_code' => '#FFFFFF',
                'variants' => [
                    [
                        'color_name' => 'Blanc',
                        'color_code' => '#FFFFFF',
                        'price' => '75000.00',
                        'original_price' => '90000.00',
                        'images' => [
                            '/assets/nike-blanc-1.jpg',
                            '/assets/nike-blanc-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '40', 'price' => '75000.00', 'original_price' => '90000.00',
],
                            ['name' => '41', 'price' => '75000.00','original_price' => '90000.00',],
                            ['name' => '42', 'price' => '76000.00','original_price' => '92000.00',],
                        ]
                    ],
                    [
                        'color_name' => 'Noir',
                        'color_code' => '#000000',
                        'price' => '78000.00',
                        'original_price' => '95000.00',
                        'images' => [
                            '/assets/nike-noir-1.jpg',
                            '/assets/nike-noir-2.jpg'
                        ],
                        'sizes' => [
                            ['name' => '40', 'price' => '78000.00','original_price' => '95000.00',],
                            ['name' => '41', 'price' => '78000.00','original_price' => '95500.00',],
                            ['name' => '42', 'price' => '78000.00','original_price' => '96000.00',],
                        ]
                    ]
                ]
            ],
            
            // Réfrigérateur (sans variantes)
            [
                'name' => 'Réfrigérateur Samsung 500L',
                'description' => 'Réfrigérateur intelligent avec technologie Twin Cooling',
                'price' => '850000.00',
                'original_price' => '950000.00',
                'image' => '/assets/fridge.jpg',
                'rating' => 4.3,
                'reviews' => 67,
                'seller' => 'ElectroMax',
                'location' => 'Douala, Cameroun',
                'badge' => 'Économie d\'énergie',
                'category_id' => 1,
                'stock_quantity' => 8,
                'restock_frequency' => 'Chaque 2 mois',
                'return_policy' => 1,
                'payment_on_delivery' => 0,
                'has_color_variants' => 0,
                'default_color' => 'Inox',
                'default_color_code' => '#C0C0C0',
                'images' => [
                    '/assets/fridge-1.jpg',
                    '/assets/fridge-2.jpg',
                    '/assets/fridge-3.jpg'
                ]
            ],
            
            // Robe africaine avec tailles
            [
                'name' => 'Robe Africaine Traditionnelle',
                'description' => 'Robe traditionnelle en tissu wax, fait main',
                'price' => '35000.00',
                'original_price' => '45000.00',
                'image' => '/assets/robe.jpg',
                'rating' => 4.8,
                'reviews' => 56,
                'seller' => 'Artisanat Africain',
                'location' => 'Yaoundé, Cameroun',
                'badge' => 'Fait main',
                'category_id' => 2,
                'stock_quantity' => 15,
                'restock_frequency' => '1 mois',
                'return_policy' => 1,
                'payment_on_delivery' => 1,
                'has_color_variants' => 0,
                'default_color' => 'Multicolore',
                'default_color_code' => null,
                'sizes' => [
                    ['name' => 'S', 'price' => '35000.00'],
                    ['name' => 'M', 'price' => '35000.00'],
                    ['name' => 'L', 'price' => '36000.00'],
                    ['name' => 'XL', 'price' => '37000.00'],
                ]
            ]
        ];

        foreach ($products as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'original_price' => $productData['original_price'] ?? null,
                'image' => $productData['image'],
                'rating' => $productData['rating'],
                'reviews' => $productData['reviews'],
                'seller' => $productData['seller'],
                'location' => $productData['location'],
                'badge' => $productData['badge'],
                'category_id' => $productData['category_id'],
                'stock_quantity' => $productData['stock_quantity'],
                'restock_frequency' => $productData['restock_frequency'] ?? null,
                'return_policy' => $productData['return_policy'],
                'payment_on_delivery' => $productData['payment_on_delivery'],
                'has_color_variants' => $productData['has_color_variants'],
                'default_color' => $productData['default_color'] ?? null,
                'default_color_code' => $productData['default_color_code'] ?? null,
            ]);

            // Gestion des variantes de couleur
            if ($productData['has_color_variants'] && isset($productData['variants'])) {
                foreach ($productData['variants'] as $variantData) {
                    $variant = ColorVariant::create([
                        'product_id' => $product->id,
                        'color_name' => $variantData['color_name'],
                        'color_code' => $variantData['color_code'],
                        'price' => $variantData['price'] ?? $productData['price'],
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
                            'price' => $sizeData['price'] ?? $variantData['price'] ?? $productData['price'],
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

            // Tailles globales (pour produits sans variantes de couleur mais avec tailles)
            if (!$productData['has_color_variants'] && isset($productData['sizes'])) {
                foreach ($productData['sizes'] as $sizeData) {
                    Size::create([
                        'product_id' => $product->id,
                        'name' => $sizeData['name'],
                        'price' => $sizeData['price'] ?? $productData['price'],
                        'available' => true
                    ]);
                }
            }
        }

        // Ajout de produits supplémentaires
        $additionalProducts = [
            [
                'name' => 'Sac à Main Cuir Premium',
                'description' => 'Sac en cuir véritable de haute qualité',
                'price' => '45000.00',
                'original_price' => '60000.00',
                'image' => '/assets/sac.jpg',
                'rating' => 4.7,
                'reviews' => 156,
                'seller' => 'Luxury Bags',
                'location' => 'Douala, Cameroun',
                'badge' => 'Cuir véritable',
                'category_id' => 2,
                'stock_quantity' => 22,
                'restock_frequency' => '3 semaines',
                'return_policy' => 1,
                'payment_on_delivery' => 1,
                'has_color_variants' => 1,
                'default_color' => 'Noir',
                'default_color_code' => '#000000',
                'variants' => [
                    [
                        'color_name' => 'Noir',
                        'color_code' => '#000000',
                        'price' => '45000.00',
                        'original_price' => '60000.00',
                        'images' => ['/assets/sac-noir.jpg'],
                        'sizes' => [] // Pas de tailles
                    ],
                    [
                        'color_name' => 'Marron',
                        'color_code' => '#8B4513',
                        'price' => '47000.00',
                        'original_price' => '63000.00',
                        'images' => ['/assets/sac-marron.jpg'],
                        'sizes' => [] // Pas de tailles
                    ]
                ]
            ],
            [
                'name' => 'Casque Audio Bluetooth',
                'description' => 'Casque sans fil avec réduction de bruit',
                'price' => '35000.00',
                'original_price' => '50000.00',
                'image' => '/assets/casque.jpg',
                'rating' => 4.4,
                'reviews' => 92,
                'seller' => 'AudioTech',
                'location' => 'Douala, Cameroun',
                'badge' => 'Réduction de bruit',
                'category_id' => 1,
                'stock_quantity' => 30,
                'restock_frequency' => '1 semaine',
                'return_policy' => 1,
                'payment_on_delivery' => 1,
                'has_color_variants' => 1,
                'default_color' => 'Noir',
                'default_color_code' => '#000000',
                'variants' => [
                    [
                        'color_name' => 'Noir',
                        'color_code' => '#000000',
                        'price' => '35000.00',
                        'original_price' =>'50000.00',
                        'images' => ['/assets/casque-noir.jpg'],
                        'sizes' => [] // Pas de tailles
                    ],
                    [
                        'color_name' => 'Blanc',
                        'color_code' => '#FFFFFF',
                        'price' => '36000.00',
                        'original_price' => '52000.00',
                        'images' => ['/assets/casque-blanc.jpg'],
                        'sizes' => [] // Pas de tailles
                    ],
                    [
                        'color_name' => 'Bleu',
                        'color_code' => '#0000FF',
                        'price' => '36500.00',
                        'original_price' => '53000.00',
                        'images' => ['/assets/casque-bleu.jpg'],
                        'sizes' => [] // Pas de tailles
                    ]
                ]
            ]
        ];

        foreach ($additionalProducts as $productData) {
            $product = Product::create([
                'name' => $productData['name'],
                'description' => $productData['description'],
                'price' => $productData['price'],
                'original_price' => $productData['original_price'] ?? null,
                'image' => $productData['image'],
                'rating' => $productData['rating'],
                'reviews' => $productData['reviews'],
                'seller' => $productData['seller'],
                'location' => $productData['location'],
                'badge' => $productData['badge'],
                'category_id' => $productData['category_id'],
                'stock_quantity' => $productData['stock_quantity'],
                'restock_frequency' => $productData['restock_frequency'] ?? null,
                'return_policy' => $productData['return_policy'],
                'payment_on_delivery' => $productData['payment_on_delivery'],
                'has_color_variants' => $productData['has_color_variants'],
                'default_color' => $productData['default_color'] ?? null,
                'default_color_code' => $productData['default_color_code'] ?? null,
            ]);

            if ($productData['has_color_variants'] && isset($productData['variants'])) {
                foreach ($productData['variants'] as $variantData) {
                    $variant = ColorVariant::create([
                        'product_id' => $product->id,
                        'color_name' => $variantData['color_name'],
                        'color_code' => $variantData['color_code'],
                        'price' => $variantData['price'] ?? $productData['price'],
                        'available' => true
                    ]);

                    if (!$productData['has_color_variants'] && isset($productData['images'])) {
                        foreach ($productData['images'] as $index => $imageUrl) {
                            VariantImage::create([
                                'product_id' => $product->id, // Ajoutez ceci
                                'image_url' => $imageUrl,
                                'is_main' => ($index === 0) ? 1 : 0
                            ]);
                        }
                    }

                    if (isset($variantData['sizes'])) {
                        foreach ($variantData['sizes'] as $sizeData) {
                            Size::create([
                                'product_id' => $product->id,
                                'color_variant_id' => $variant->id,
                                'name' => $sizeData['name'],
                                'price' => $sizeData['price'] ?? $variantData['price'] ?? $productData['price'],
                                'available' => true
                            ]);
                        }
                    }
                }
            }
        }
    }
}