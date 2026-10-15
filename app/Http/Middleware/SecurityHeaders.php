<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Allow Vite-built self assets + inline Livewire styles; tighten CSP
        // further once a nonce pipeline is in place. When the Vite dev server is
        // running (local HMR), its origin must be whitelisted or assets are blocked.
        $viteDev = Vite::isRunningHot()
            ? ' http://[::1]:5173 http://127.0.0.1:5173 http://localhost:5173 ws://[::1]:5173 ws://127.0.0.1:5173 ws://localhost:5173'
            : '';

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'{$viteDev}; script-src 'self' 'unsafe-inline'{$viteDev}; style-src 'self' 'unsafe-inline'{$viteDev}; img-src 'self' data: https:; font-src 'self' data:{$viteDev}; connect-src 'self'{$viteDev}; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        return $response;
    }
}
