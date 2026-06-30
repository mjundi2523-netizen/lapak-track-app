<?php

namespace App\Livewire\Profile;

use App\Models\Dealer;
use App\Models\Stall;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

#[Layout('layouts.app')]
class ShowProfile extends Component
{
    use Toast;

    public ?string $name = null;
    public ?string $email = null;
    public ?string $password = null;
    public ?string $password_confirmation = null;

    public bool $isEditingProfile = false;
    public bool $isChangingPassword = false;

    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
    }

    public function updateProfile(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->isEditingProfile = false;
        $this->success('Profil berhasil diperbarui.');
    }

    public function changePassword(): void
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->password),
        ]);

        $this->password = null;
        $this->password_confirmation = null;
        $this->isChangingPassword = false;
        $this->success('Password berhasil diubah.');
    }

    /** Simpan preferensi mode gelap ke config_users (per-user). */
    public function setDark(bool $on): void
    {
        $user = Auth::user();
        $user->config()->updateOrCreate(
            ['user_id' => $user->id],
            ['dark_mode' => $on],
        );
    }

    public function logout(): void
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.profile.show-profile', [
            'stallCount' => Stall::count(),
            'dealerCount' => Dealer::count(),
        ]);
    }
}
