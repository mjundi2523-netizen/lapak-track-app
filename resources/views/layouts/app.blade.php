<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LapakTrack') }}</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{-- Dark mode: kelas `lt-dark` (CSS custom) + `data-theme="dark"` (komponen MaryUI/DaisyUI)
     di <body>, dirender server dari config_users. Di <body> agar ikut ter-morph & persist
     saat wire:navigate. `lt-dark` menyetel halaman hex-styled; `data-theme` menyetel kartu/
     input/select/tombol MaryUI. --}}
@php $lt_dark = auth()->user()?->prefersDark() ?? false; @endphp
<body data-theme="{{ $lt_dark ? 'dark' : 'light' }}"
      @class(['min-h-screen', 'lt-dark' => $lt_dark])
      style="background:color-mix(in srgb, var(--lt-p) 5%, #f4f4f6); color:#18181b; -webkit-font-smoothing:antialiased;">
    {{-- collapsed (computed): sempit bila tidak disematkan (pinnedOpen) DAN tidak sedang di-hover.
         Hover → sidebar otomatis terbuka; klik logo/hamburger → sematkan tetap terbuka. --}}
    <div x-data="{ pinnedOpen: false, hovering: false, userMenu: false, get collapsed() { return !this.pinnedOpen && !this.hovering } }" class="flex min-h-screen">
        {{-- Sidebar: default light, override gelap murni via CSS `body.lt-dark .lt-sidebar` (lihat
             app.css) — supaya reaktif ke toggle client-side tanpa reload, sama seperti header/main. --}}
        <aside
            @mouseenter="hovering = true" @mouseleave="hovering = false"
            :class="collapsed ? 'w-[76px]' : 'w-64'"
            class="lt-sidebar shrink-0 flex flex-col sticky top-0 h-screen z-20 overflow-hidden transition-all duration-200"
            style="background:#ffffff; border-right:1px solid #eceef2;">
            @include('layouts.sidebar')
        </aside>

        {{-- Right column --}}
        <div class="flex-1 min-w-0 flex flex-col">
            {{-- Topbar --}}
            <header class="sticky top-0 z-30 bg-white flex items-center justify-between px-7"
                    style="height:68px; border-bottom:1px solid #eceef2;">
                <div class="flex items-center gap-4">
                    <button @click="pinnedOpen = !pinnedOpen"
                            class="w-[38px] h-[38px] inline-flex items-center justify-center rounded-[9px] text-[#52525b] hover:bg-base-200 transition">
                        <x-icon name="o-bars-3" class="w-[22px] h-[22px]" />
                    </button>
                    <div class="hidden md:flex items-center gap-2.5 h-10 px-3 rounded-[10px] min-w-[280px]"
                         style="border:1px solid #e6e8ee; background:#f7f8fb;">
                        <x-icon name="o-magnifying-glass" class="w-[17px] h-[17px] text-[#9aa3b2]" />
                        <input placeholder="Cari..." class="flex-1 border-none outline-none bg-transparent text-sm text-[#27272a]" />
                        <span class="text-xs text-[#9aa3b2] rounded-md px-1.5 py-px bg-white" style="border:1px solid #e0e3ea;">⌘K</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 relative">
                    <livewire:notification-bell />

                    <button @click="userMenu = !userMenu" @click.outside="userMenu = false"
                            class="flex items-center gap-2 rounded-[10px] py-1.5 pl-1.5 pr-2 hover:bg-base-200 transition">
                        <div class="w-9 h-9 rounded-full text-white flex items-center justify-center text-[13px] font-bold"
                             style="background:var(--lt-p);">
                            {{ strtoupper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 2)) }}
                        </div>
                        <x-icon name="o-chevron-down" class="w-4 h-4 text-[#71717a]" />
                    </button>

                    <div x-show="userMenu" x-cloak x-transition.opacity
                         class="absolute right-0 top-[52px] bg-white rounded-xl p-1.5 min-w-[190px] z-40"
                         style="border:1px solid #e5e7eb; box-shadow:0 12px 28px rgba(0,0,0,0.12);">
                        <div class="px-3 pt-2.5 pb-2 mb-1" style="border-bottom:1px solid #f0f0f1;">
                            <div class="text-sm font-semibold text-[#18181b]">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-[#9aa3b2]">{{ Auth::user()->email }}</div>
                        </div>
                        <a href="{{ route('profile.edit') }}" wire:navigate
                           class="flex items-center gap-2.5 px-3 py-2.5 text-sm text-[#3f3f46] rounded-lg hover:bg-base-200 transition">
                            <x-icon name="o-user" class="w-[18px] h-[18px]" /> Profil
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    class="w-full flex items-center gap-2.5 px-3 py-2.5 text-sm text-[#dc2626] rounded-lg hover:bg-red-50 transition">
                                <x-icon name="o-arrow-right-on-rectangle" class="w-[18px] h-[18px]" /> Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            {{-- Main --}}
            <main class="flex-1 min-w-0 p-7" style="background:#eef1f6;">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />
    @include('partials.premium-modal')
    @livewireScripts
</body>
</html>
