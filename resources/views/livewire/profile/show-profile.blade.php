<div>
    <x-header title="Profil Pengguna" separator progress-indicator>
        <x-slot:actions>
            <x-button label="Kembali" icon="o-arrow-left" link="{{ route('dashboard') }}" class="btn-ghost" />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Sidebar: User Info --}}
        <div class="space-y-6">
            {{-- User Card --}}
            <x-card title="Informasi Akun" class="text-center">
                <div class="flex flex-col items-center">
                    <div class="w-16 h-16 rounded-full bg-primary/20 flex items-center justify-center mb-4">
                        <x-icon name="o-user" class="w-8 h-8 text-primary" />
                    </div>
                    <h3 class="font-bold text-lg">{{ Auth::user()->name }}</h3>
                    <p class="text-sm text-base-content/60 break-all">{{ Auth::user()->email }}</p>
                    <div class="mt-4 text-xs text-base-content/50">
                        <p>Bergabung: {{ Auth::user()->created_at->format('d M Y') }}</p>
                    </div>
                </div>
            </x-card>

            {{-- Quick Actions --}}
            <x-card>
                <div class="space-y-2">
                    <x-button
                        label="Edit Profil"
                        icon="o-pencil"
                        class="btn-primary w-full"
                        wire:click="$set('isEditingProfile', true)"
                    />
                    <x-button
                        label="Ubah Password"
                        icon="o-key"
                        class="btn-secondary w-full"
                        wire:click="$set('isChangingPassword', true)"
                    />
                    <x-button
                        label="Logout"
                        icon="o-arrow-right-on-rectangle"
                        class="btn-error w-full"
                        wire:click="logout"
                    />
                </div>
            </x-card>
        </div>

        {{-- Main Content: Forms --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Edit Profile Form --}}
            @if($isEditingProfile)
                <x-card title="Edit Profil" class="border-2 border-primary">
                    <form wire:submit="updateProfile" class="space-y-4">
                        <x-input
                            wire:model="name"
                            label="Nama Lengkap"
                            placeholder="Masukkan nama Anda"
                            icon="o-user"
                        />

                        <x-input
                            wire:model="email"
                            label="Email"
                            type="email"
                            placeholder="Masukkan email Anda"
                            icon="o-envelope"
                        />

                        <div class="flex gap-3 pt-4">
                            <x-button
                                label="Batal"
                                class="btn-ghost flex-1"
                                wire:click="$set('isEditingProfile', false)"
                            />
                            <x-button
                                label="Simpan"
                                class="btn-primary flex-1"
                                type="submit"
                                spinner="updateProfile"
                            />
                        </div>
                    </form>
                </x-card>
            @else
                <x-card title="Profil" class="card-bordered">
                    <div class="space-y-4 text-sm">
                        <div class="flex justify-between items-center pb-3 border-b border-base-200">
                            <span class="text-base-content/60">Nama:</span>
                            <span class="font-medium">{{ Auth::user()->name }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-base-200">
                            <span class="text-base-content/60">Email:</span>
                            <span class="font-medium">{{ Auth::user()->email }}</span>
                        </div>
                        <div class="flex justify-between items-center pb-3 border-b border-base-200">
                            <span class="text-base-content/60">Status Verifikasi:</span>
                            @if(Auth::user()->email_verified_at)
                                <x-badge value="Terverifikasi" class="badge-success badge-sm" />
                            @else
                                <x-badge value="Belum Terverifikasi" class="badge-warning badge-sm" />
                            @endif
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-base-content/60">Bergabung:</span>
                            <span class="font-medium">{{ Auth::user()->created_at->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- Change Password Form --}}
            @if($isChangingPassword)
                <x-card title="Ubah Password" class="border-2 border-warning">
                    <div role="alert" class="alert alert-info mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="stroke-current shrink-0 w-6 h-6">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Password baru harus minimal 8 karakter.</span>
                    </div>

                    <form wire:submit="changePassword" class="space-y-4">
                        <x-input
                            wire:model="password"
                            label="Password Baru"
                            type="password"
                            placeholder="Masukkan password baru"
                            icon="o-key"
                        />

                        <x-input
                            wire:model="password_confirmation"
                            label="Konfirmasi Password"
                            type="password"
                            placeholder="Konfirmasi password baru"
                            icon="o-key"
                        />

                        <div class="flex gap-3 pt-4">
                            <x-button
                                label="Batal"
                                class="btn-ghost flex-1"
                                wire:click="$set('isChangingPassword', false)"
                            />
                            <x-button
                                label="Ubah Password"
                                class="btn-warning flex-1"
                                type="submit"
                                spinner="changePassword"
                            />
                        </div>
                    </form>
                </x-card>
            @else
                <x-card title="Keamanan" class="card-bordered">
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium">Password</h4>
                                <p class="text-sm text-base-content/60">Ubah password akun Anda secara berkala untuk keamanan.</p>
                            </div>
                        </div>
                    </div>
                </x-card>
            @endif

            {{-- Additional Info --}}
            <x-card title="Tentang Aplikasi" class="card-bordered bg-base-200/50">
                <div class="text-sm space-y-2">
                    <p>
                        <span class="font-medium text-primary">LapakTrack</span>
                        adalah sistem manajemen untuk dealer dan billing di marketplace.
                    </p>
                    <p class="text-base-content/60">
                        Aplikasi ini membantu mengelola data pedagang, lapak sewa, tagihan, dan pembayaran dengan mudah dan terorganisir.
                    </p>
                    <div class="pt-2 text-xs text-base-content/50">
                        <p>Versi: 1.0.0</p>
                        <p>© 2026 LapakTrack. Semua hak dilindungi.</p>
                    </div>
                </div>
            </x-card>
        </div>
    </div>
</div>
