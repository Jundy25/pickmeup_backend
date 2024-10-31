<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check() || auth()->id() !== 1) {
            return response()->json(['message' => 'Unauthorized. Only SuperAdmin can perform this action.'], 403);
        }

        return $next($request);
    }
}