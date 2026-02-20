<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SubscriptionPlan;

class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Parfait pour démarrer votre activité',
                'monthly_price' => 0,
                'yearly_price' => 0,
                'yearly_discount' => 0,
                'product_limit' => 10,
                'order_limit' => 50,
                'commission_rate' => 8.00,
                'features' => [
                    '10 produits maximum',
                    '50 commandes/mois',
                    'Commission 8%',
                    'Support par email',
                    'Tableau de bord basique',
                ],
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'description' => 'Pour les commerces en croissance',
                'monthly_price' => 15000,
                'yearly_price' => 150000,
                'yearly_discount' => 17,
                'product_limit' => 100,
                'order_limit' => 500,
                'commission_rate' => 5.00,
                'features' => [
                    '100 produits maximum',
                    '500 commandes/mois',
                    'Commission 5%',
                    'Support prioritaire',
                    'Analytics avancées',
                    'Promotions illimitées',
                ],
                'is_active' => true,
                'is_popular' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Pour les professionnels exigeants',
                'monthly_price' => 35000,
                'yearly_price' => 350000,
                'yearly_discount' => 17,
                'product_limit' => 500,
                'order_limit' => 2000,
                'commission_rate' => 3.00,
                'features' => [
                    '500 produits maximum',
                    '2000 commandes/mois',
                    'Commission 3%',
                    'Support 24/7',
                    'API access',
                    'Personnalisation boutique',
                    'Multi-utilisateurs',
                ],
                'is_active' => true,
                'is_popular' => false,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Solutions sur mesure pour grandes entreprises',
                'monthly_price' => 75000,
                'yearly_price' => 750000,
                'yearly_discount' => 17,
                'product_limit' => 0, // Illimité
                'order_limit' => 0, // Illimité
                'commission_rate' => 2.00,
                'features' => [
                    'Produits illimités',
                    'Commandes illimitées',
                    'Commission 2%',
                    'Account manager dédié',
                    'Support prioritaire 24/7',
                    'Intégrations personnalisées',
                    'Formation équipe',
                    'Rapports personnalisés',
                ],
                'is_active' => true,
                'is_popular' => false,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}