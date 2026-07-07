<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Market;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        // Market disediakan developer via DB (tak bisa dibuat dari aplikasi).
        return view('auth.register', [
            'markets' => Market::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'market_id' => ['required', 'integer', 'exists:markets,mid'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // Akun dibuat non-aktif; developer mengaktifkan manual (flip is_approved di DB).
        // email_verified_at diisi langsung karena gerbang akses = approval developer,
        // bukan verifikasi email (menghindari dead-end verify-email bila SMTP belum diset).
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'market_id' => $request->market_id,
            'is_approved' => false,
            'email_verified_at' => now(),
            'password' => Hash::make($request->password),
        ]);

        // Tidak auto-login: user harus menunggu verifikasi developer dulu.
        return redirect()->route('login')->with('status',
            'Pendaftaran berhasil. Akun Anda akan aktif setelah diverifikasi oleh admin.');
    }
}
