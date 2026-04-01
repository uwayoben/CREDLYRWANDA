<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Use query builder directly to avoid touching updated_at
            // and avoid firing model events unnecessarily
            \App\Models\User::withoutTimestamps(function () {
                Auth::user()->update(['last_seen_at' => now()]);
            });
        }

        return $next($request);
    }
}
