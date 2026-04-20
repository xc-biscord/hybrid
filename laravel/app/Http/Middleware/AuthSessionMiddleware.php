<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthSessionMiddleware
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (!isset($_SESSION['user_id'])) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Non authentifié',
            ], 401);
        }

        return $next($request);
    }
}
