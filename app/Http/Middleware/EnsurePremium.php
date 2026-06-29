<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePremium
{
    /**
     * Gerbang fitur premium. Akun non-premium yang menembus URL langsung
     * dialihkan ke dashboard sambil menandai sesi agar modal premium otomatis terbuka.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isPremium()) {
            return redirect()
                ->route('dashboard')
                ->with('premium_required', true);
        }

        return $next($request);
    }
}
