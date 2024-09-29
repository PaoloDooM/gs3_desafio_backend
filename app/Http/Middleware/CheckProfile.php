<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProfile
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$profiles): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(401, 'Unauthorized');
        } else if (in_array($user->profile->id, $profiles)) {
            return $next($request);
        }
        abort(403, 'Forbidden');
    }
}
