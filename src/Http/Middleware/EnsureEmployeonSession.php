<?php

declare(strict_types=1);

namespace Employeon\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

final readonly class EnsureEmployeonSession
{
    /**
     * @param  Closure(Request): mixed  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if (! is_array($request->session()->get('employeon.user'))) {
            return redirect()->route('login');
        }

        return $next($request);
    }
}
