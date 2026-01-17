<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingCompleted
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If user is authenticated and hasn't completed onboarding
        if ($user && !$user->onboarding_completed) {
            // Allow access to onboarding routes, logout, and profile
            $allowedRoutes = [
                'onboarding.index',
                'onboarding.store-shop',
                'onboarding.connect-etsy',
                'onboarding.skip',
                'onboarding.complete',
                'etsy.callback',
                'logout',
            ];

            if (!in_array($request->route()->getName(), $allowedRoutes)) {
                return redirect()->route('onboarding.index');
            }
        }

        return $next($request);
    }
}
