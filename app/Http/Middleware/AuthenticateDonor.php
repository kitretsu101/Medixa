<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDonor implements AuthenticatesRequests
{
    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::guard('donor')->check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('blood-connect.donor.login');
        }
        $previousGuard = Auth::getDefaultDriver();
        try {
            $response = $next($request);
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        } finally {
            Auth::shouldUse($previousGuard);
        }
    }
}
