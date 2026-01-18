<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shop;
use App\Services\EtsyApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    /**
     * Display a listing of orders.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $shops = $user->shops()->get();

        if ($shops->isEmpty()) {
            return redirect()->route('dashboard')
                ->with('error', 'Veuillez d\'abord connecter une boutique.');
        }

        // Get selected shop or default to first/session
        $shopId = $request->get('shop_id', session('active_shop_id', $shops->first()->id));
        $shop = $shops->firstWhere('id', $shopId) ?? $shops->first();

        session(['active_shop_id' => $shop->id]);

        Gate::authorize('view', $shop);

        // Build query with filters
        $query = $shop->orders()->with(['items']);

        // Status filter
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Date filter
        if ($date = $request->get('date')) {
            switch ($date) {
                case 'today':
                    $query->whereDate('created_at', today());
                    break;
                case 'week':
                    $query->where('created_at', '>=', now()->subWeek());
                    break;
                case 'month':
                    $query->where('created_at', '>=', now()->subMonth());
                    break;
            }
        }

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_email', 'like', "%{$search}%")
                  ->orWhere('etsy_receipt_id', 'like', "%{$search}%");
            });
        }

        // Stats
        $stats = [
            'total' => $shop->orders()->count(),
            'new' => $shop->orders()->where('status', Order::STATUS_NEW)->count(),
            'in_progress' => $shop->orders()->whereIn('status', [
                Order::STATUS_ORDERED,
                Order::STATUS_SHIPPED,
            ])->count(),
            'completed' => $shop->orders()->whereIn('status', [
                Order::STATUS_DELIVERED,
                Order::STATUS_COMPLETED,
            ])->count(),
            'today_revenue' => $shop->orders()->today()->sum('total_price'),
            'today_profit' => $shop->orders()->today()->sum('total_profit'),
        ];

        $orders = $query->latest()->paginate(20)->withQueryString();

        return view('orders.index', compact('orders', 'shop', 'shops', 'stats'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order->shop);

        $order->load(['shop', 'items.product']);

        return view('orders.show', compact('order'));
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order->shop);

        $request->validate([
            'status' => 'required|in:' . implode(',', Order::statuses()),
        ]);

        $newStatus = $request->status;

        // Update status and corresponding timestamp
        $updateData = ['status' => $newStatus];

        switch ($newStatus) {
            case Order::STATUS_ORDERED:
                $updateData['ordered_at'] = now();
                break;
            case Order::STATUS_SHIPPED:
                $updateData['shipped_at'] = now();
                break;
            case Order::STATUS_DELIVERED:
                $updateData['delivered_at'] = now();
                break;
            case Order::STATUS_COMPLETED:
                $updateData['completed_at'] = now();
                break;
        }

        $order->update($updateData);

        return redirect()->back()
            ->with('success', 'Statut mis a jour avec succes !');
    }

    /**
     * Import orders from Etsy.
     */
    public function importFromEtsy(Request $request)
    {
        $shop = Shop::findOrFail($request->get('shop_id', session('active_shop_id')));
        Gate::authorize('update', $shop);

        if (!$shop->etsy_shop_id) {
            return redirect()->back()
                ->with('error', 'La boutique n\'est pas connectee a Etsy.');
        }

        try {
            $etsyClient = new EtsyApiClient($shop);
            $receipts = $etsyClient->getShopReceipts($shop->etsy_shop_id);

            $imported = 0;
            $updated = 0;

            foreach ($receipts['results'] ?? [] as $receipt) {
                $existingOrder = Order::where('etsy_receipt_id', $receipt['receipt_id'])->first();

                $orderData = [
                    'shop_id' => $shop->id,
                    'customer_name' => $receipt['name'] ?? 'Client Etsy',
                    'customer_email' => $receipt['buyer_email'] ?? null,
                    'total_price' => ($receipt['grandtotal']['amount'] ?? 0) / ($receipt['grandtotal']['divisor'] ?? 100),
                    'currency' => $receipt['grandtotal']['currency_code'] ?? 'EUR',
                    'shipping_address' => [
                        'name' => $receipt['name'] ?? '',
                        'first_line' => $receipt['first_line'] ?? '',
                        'second_line' => $receipt['second_line'] ?? '',
                        'city' => $receipt['city'] ?? '',
                        'state' => $receipt['state'] ?? '',
                        'zip' => $receipt['zip'] ?? '',
                        'country_name' => $receipt['country_name'] ?? '',
                    ],
                    'etsy_data' => $receipt,
                ];

                if ($existingOrder) {
                    $existingOrder->update($orderData);
                    $updated++;
                } else {
                    $order = Order::create(array_merge($orderData, [
                        'etsy_receipt_id' => $receipt['receipt_id'],
                        'status' => Order::STATUS_NEW,
                    ]));

                    // Import order items (transactions)
                    foreach ($receipt['transactions'] ?? [] as $transaction) {
                        $this->createOrderItem($order, $transaction, $shop);
                    }

                    // Recalculate totals
                    $order->recalculateTotals();
                    $imported++;
                }
            }

            // Update shop stats
            $shop->updateCachedStats();

            $message = "{$imported} commande(s) importee(s)";
            if ($updated > 0) {
                $message .= ", {$updated} mise(s) a jour";
            }

            return redirect()->route('orders.index', ['shop_id' => $shop->id])
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de l\'importation: ' . $e->getMessage());
        }
    }

    /**
     * Create order item from Etsy transaction.
     */
    private function createOrderItem(Order $order, array $transaction, Shop $shop): OrderItem
    {
        // Try to find matching product
        $product = Product::where('shop_id', $shop->id)
            ->where('etsy_listing_id', $transaction['listing_id'] ?? null)
            ->first();

        $price = ($transaction['price']['amount'] ?? 0) / ($transaction['price']['divisor'] ?? 100);
        $cost = $product?->cost_price ?? 0;

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product?->id,
            'etsy_listing_id' => $transaction['listing_id'] ?? null,
            'etsy_transaction_id' => $transaction['transaction_id'] ?? null,
            'title' => $transaction['title'] ?? 'Produit Etsy',
            'quantity' => $transaction['quantity'] ?? 1,
            'price' => $price,
            'cost' => $cost,
            'profit' => $price - $cost,
            'source_type' => $product?->source_type,
            'source_url' => $product?->source_url,
            'is_digital' => $product?->is_digital ?? false,
        ]);
    }

    /**
     * Add note to order.
     */
    public function addNote(Request $request, Order $order)
    {
        Gate::authorize('update', $order->shop);

        $request->validate([
            'notes' => 'nullable|string|max:1000',
        ]);

        $order->update(['notes' => $request->notes]);

        return redirect()->back()
            ->with('success', 'Note enregistree !');
    }
}
