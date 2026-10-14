<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (($request->user()?->role ?? null) !== 'admin') {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
            }
            return redirect()->route('home');
        }

        return $next($request);
    }
}
