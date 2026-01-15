<?php

namespace App\Http\Middleware;

use App\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetActiveShop
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Get shop_id from session or request
            $shopId = session('active_shop_id') ?? $request->get('shop_id');

            // If no shop is set, use the first shop the user has access to
            if (!$shopId) {
                $firstShop = $user->shops()->first();
                if ($firstShop) {
                    $shopId = $firstShop->id;
                    session(['active_shop_id' => $shopId]);
                }
            }

            // Load the active shop and verify user has access
            if ($shopId) {
                $shop = Shop::find($shopId);

                if ($shop && $user->hasAccessToShop($shop)) {
                    // Share the active shop with all views
                    view()->share('activeShop', $shop);
                    $request->attributes->set('activeShop', $shop);
                } else {
                    // User doesn't have access, clear the session
                    session()->forget('active_shop_id');
                }
            }
        }

        return $next($request);
    }
}
