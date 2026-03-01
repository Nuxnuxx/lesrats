<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or get demo user
        $user = User::firstOrCreate(
            ['email' => 'demo@lesrats.fr'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Create multiple shops
        $shops = $this->createShops($user);

        // Create products for each shop
        foreach ($shops as $shop) {
            $this->createProducts($shop);
        }

        $this->command->info('Demo data created successfully!');
        $this->command->info('Login: demo@lesrats.fr / password');
    }

    private function createShops(User $user): array
    {
        $shopsData = [
            [
                'name' => 'LesRats3D',
                'currency' => 'EUR',
                'is_active' => true,
            ],
            [
                'name' => 'PrintablesParadise',
                'currency' => 'EUR',
                'is_active' => true,
            ],
            [
                'name' => 'DropshipKing',
                'currency' => 'EUR',
                'is_active' => true,
            ],
        ];

        $shops = [];
        foreach ($shopsData as $data) {
            $shop = Shop::firstOrCreate(
                ['name' => $data['name']],
                $data
            );

            // Attach user if not already
            if (! $shop->users()->where('user_id', $user->id)->exists()) {
                $shop->users()->attach($user->id, ['role' => 'owner']);
            }

            $shops[] = $shop;
        }

        return $shops;
    }

    private function createProducts(Shop $shop): void
    {
        $printablesProducts = [
            ['title' => 'Dragon Articule - Fichier STL', 'price' => 4.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Support Casque Gaming RGB', 'price' => 3.49, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Pot de Fleur Geometrique', 'price' => 2.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Organisateur Bureau Modulaire', 'price' => 5.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Figurine Chat Mignon', 'price' => 1.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Boite a Cles Murale', 'price' => 3.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Lampe Lune Lithophane', 'price' => 6.99, 'source_type' => 'printables', 'is_digital' => true],
            ['title' => 'Porte Savon Design', 'price' => 2.49, 'source_type' => 'printables', 'is_digital' => true],
        ];

        // Dropship products - we don't track stock (supplier handles it)
        $aliexpressProducts = [
            ['title' => 'LED Strip RGB 5m', 'price' => 24.99, 'cost_price' => 8.50, 'source_type' => 'aliexpress', 'is_digital' => false],
            ['title' => 'Webcam HD 1080p', 'price' => 39.99, 'cost_price' => 15.00, 'source_type' => 'aliexpress', 'is_digital' => false],
            ['title' => 'Support Telephone Voiture', 'price' => 14.99, 'cost_price' => 3.20, 'source_type' => 'aliexpress', 'is_digital' => false],
            ['title' => 'Chargeur Sans Fil 15W', 'price' => 19.99, 'cost_price' => 6.80, 'source_type' => 'aliexpress', 'is_digital' => false],
            ['title' => 'Ecouteurs Bluetooth TWS', 'price' => 29.99, 'cost_price' => 9.50, 'source_type' => 'aliexpress', 'is_digital' => false],
            ['title' => 'Hub USB-C 7 en 1', 'price' => 34.99, 'cost_price' => 12.00, 'source_type' => 'aliexpress', 'is_digital' => false],
        ];

        $manualProducts = [
            ['title' => 'Creation Personnalisee', 'price' => 49.99, 'source_type' => 'manual', 'is_digital' => false, 'quantity' => 999],
            ['title' => 'Service Impression 3D', 'price' => 15.00, 'source_type' => 'manual', 'is_digital' => false, 'quantity' => 999],
        ];

        $allProducts = array_merge($printablesProducts, $aliexpressProducts, $manualProducts);

        // Randomize which products go to which shop
        shuffle($allProducts);
        $productsForShop = array_slice($allProducts, 0, rand(8, 14));

        foreach ($productsForShop as $index => $productData) {
            Product::firstOrCreate(
                [
                    'shop_id' => $shop->id,
                    'title' => $productData['title'],
                ],
                [
                    'description' => 'Description pour '.$productData['title'].'. Produit de haute qualite.',
                    'price' => $productData['price'],
                    'cost_price' => $productData['cost_price'] ?? 0,
                    'quantity' => $productData['quantity'] ?? 999,
                    'source_type' => $productData['source_type'],
                    'source_url' => $productData['source_type'] === 'aliexpress'
                        ? 'https://www.aliexpress.com/item/'.rand(1000000000, 9999999999).'.html'
                        : ($productData['source_type'] === 'printables'
                            ? 'https://www.printables.com/model/'.rand(100000, 999999)
                            : null),
                    'is_digital' => $productData['is_digital'],
                    'is_active' => true,
                ]
            );
        }
    }
}
