<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gerbang approval developer. User yang sudah login tapi is_approved=false
 * (mis. di-nonaktifkan setelah sesi berjalan) di-logout & diarahkan ke login.
 * Login awal sudah diblok di AuthenticatedSessionController; ini lapis kedua.
 */
class EnsureApproved
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->isApproved()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda belum diverifikasi oleh admin.',
            ]);
        }

        return $next($request);
    }
}
