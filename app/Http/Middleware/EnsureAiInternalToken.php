<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAiInternalToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = config('ai.internal_token');

        if ($token && ! hash_equals($token, (string) $request->header('X-AI-Internal-Token'))) {
            abort(403, 'Invalid AI internal token.');
        }

        return $next($request);
    }
}
