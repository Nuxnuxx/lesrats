<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Shop;
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
                ->with('error', 'Veuillez d\'abord creer une boutique.');
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
                    ->orWhere('order_number', 'like', "%{$search}%");
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
            'status' => 'required|in:'.implode(',', Order::statuses()),
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
