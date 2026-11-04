<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyMessageIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredToken = config('services.message_integration.token');

        if (! is_string($configuredToken) || $configuredToken === '') {
            return response()->json(['message' => 'Message integration is not configured.'], 503);
        }

        $providedToken = $request->header('X-Integration-Token', '');

        if (! is_string($providedToken) || ! hash_equals($configuredToken, $providedToken)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
