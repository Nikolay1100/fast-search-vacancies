<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Response\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPremium()) {
            return ApiResponse::error('Active subscription required to access this resource.', 403);
        }

        return $next($request);
    }
}
