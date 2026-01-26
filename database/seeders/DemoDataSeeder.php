<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
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
            $this->createOrders($shop);
            $shop->updateCachedStats();
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

    private function createOrders(Shop $shop): void
    {
        $firstNames = ['Jean', 'Marie', 'Pierre', 'Sophie', 'Lucas', 'Emma', 'Thomas', 'Lea', 'Nicolas', 'Camille', 'Antoine', 'Julie', 'Maxime', 'Sarah', 'Alexandre'];
        $lastNames = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia'];

        $products = $shop->products;

        if ($products->isEmpty()) {
            return;
        }

        // Create orders over the last 30 days
        $numOrders = rand(15, 40);

        for ($i = 0; $i < $numOrders; $i++) {
            $daysAgo = rand(0, 30);
            $createdAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $customerName = $firstName.' '.$lastName;

            $statuses = [
                Order::STATUS_NEW,
                Order::STATUS_NEW,
                Order::STATUS_ORDERED,
                Order::STATUS_SHIPPED,
                Order::STATUS_DELIVERED,
                Order::STATUS_COMPLETED,
                Order::STATUS_COMPLETED,
            ];

            // Today's orders are more likely to be NEW
            if ($daysAgo === 0) {
                $status = rand(0, 10) > 3 ? Order::STATUS_NEW : Order::STATUS_ORDERED;
            } else {
                $status = $statuses[array_rand($statuses)];
            }

            $order = Order::create([
                'shop_id' => $shop->id,
                'order_number' => 'ORD-'.strtoupper(substr(md5(uniqid()), 0, 8)),
                'customer_name' => $customerName,
                'customer_email' => strtolower($firstName).'.'.strtolower($lastName).'@example.com',
                'total_price' => 0,
                'total_cost' => 0,
                'total_profit' => 0,
                'currency' => 'EUR',
                'status' => $status,
                'ordered_at' => in_array($status, [Order::STATUS_ORDERED, Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                    ? $createdAt->copy()->addHours(rand(1, 24)) : null,
                'shipped_at' => in_array($status, [Order::STATUS_SHIPPED, Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                    ? $createdAt->copy()->addDays(rand(1, 3)) : null,
                'delivered_at' => in_array($status, [Order::STATUS_DELIVERED, Order::STATUS_COMPLETED])
                    ? $createdAt->copy()->addDays(rand(5, 14)) : null,
                'completed_at' => $status === Order::STATUS_COMPLETED
                    ? $createdAt->copy()->addDays(rand(7, 20)) : null,
                'shipping_address' => [
                    'name' => $customerName,
                    'first_line' => rand(1, 150).' Rue '.$lastNames[array_rand($lastNames)],
                    'city' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux', 'Nice', 'Nantes', 'Lille'][array_rand(['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Bordeaux', 'Nice', 'Nantes', 'Lille'])],
                    'zip' => str_pad(rand(10000, 99999), 5, '0', STR_PAD_LEFT),
                    'country_name' => 'France',
                ],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add 1-3 items per order
            $numItems = rand(1, 3);
            $orderProducts = $products->random(min($numItems, $products->count()));

            $totalPrice = 0;
            $totalCost = 0;

            foreach ($orderProducts as $product) {
                $quantity = rand(1, 2);
                $price = $product->price;
                $cost = $product->cost_price ?? 0;
                $profit = $price - $cost;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'title' => $product->title,
                    'quantity' => $quantity,
                    'price' => $price,
                    'cost' => $cost,
                    'profit' => $profit,
                    'source_type' => $product->source_type,
                    'source_url' => $product->source_url,
                    'is_digital' => $product->is_digital,
                ]);

                $totalPrice += $price * $quantity;
                $totalCost += $cost * $quantity;
            }

            $order->update([
                'total_price' => $totalPrice,
                'total_cost' => $totalCost,
                'total_profit' => $totalPrice - $totalCost,
            ]);
        }
    }
}
