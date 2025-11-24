<?php

namespace App\Http\Middleware;

use App\Services\StoreContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreContextMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only process for authenticated users
        if (! auth('sanctum')->check() && ! auth()->check()) {
            return $next($request);
        }

        $user = auth('sanctum')->user() ?? auth()->user();

        // Check if X-Store-ID header is provided
        $storeId = $request->header('X-Store-ID');

        if ($storeId) {
            // Validate that the user has access to this store
            if (! $user->hasAccessToStore((int) $storeId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have access to this store.',
                ], 403);
            }

            // Set the active store
            StoreContext::setActiveStore((int) $storeId);
        } else {
            // Fall back to user's default store
            if ($user->default_store_id) {
                StoreContext::setActiveStore($user->default_store_id);
            }
        }

        return $next($request);
    }
}
