<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ajoute les headers HTTP de sécurité standards.
 *
 * Notes :
 * - HSTS n'est envoyé qu'en production sur connexion HTTPS — éviter de le pousser
 *   en dev local sinon le navigateur force HTTPS sur localhost.
 * - CSP volontairement souple pour Alpine.js inline + storage : ne pas bloquer
 *   ce dont l'app a vraiment besoin. À durcir si on supprime les inline handlers.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=(), payment=()');

        // HSTS uniquement en prod et sur HTTPS — sinon casse le dev local
        if (app()->environment('production') && $request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP — Alpine + Tailwind nécessitent 'unsafe-inline' pour les directives @click etc.
        // img-src élargi pour autoriser les CDN AliExpress / Etsy / cloud storage publics.
        if (! $response->headers->has('Content-Security-Policy')) {
            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net data:",
                "img-src 'self' data: blob: https:",
                "connect-src 'self' https:",
                "frame-ancestors 'self'",
                "base-uri 'self'",
                "form-action 'self'",
            ]));
        }

        return $response;
    }
}
