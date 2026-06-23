<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LapakTrack') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-base-200">
    <x-nav sticky full-width>
        <x-slot:brand>
            <div class="flex items-center gap-2">
                <x-icon name="o-square-3-stack-3d" class="w-7 h-7 text-primary" />
                <span class="font-bold text-lg">LapakTrack</span>
            </div>
        </x-slot:brand>

        <x-slot:actions>
            <x-dropdown>
                <x-slot:trigger>
                    <x-button label="{{ Auth::user()->name }}" icon-right="o-chevron-down" class="btn-ghost btn-sm" />
                </x-slot:trigger>
                <x-menu-item title="Profil" icon="o-user" link="{{ route('profile.edit') }}" />
                <x-menu-separator />
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-menu-item title="Keluar" icon="o-arrow-right-on-rectangle"
                        onclick="event.preventDefault(); this.closest('form').submit();" />
                </form>
            </x-dropdown>
        </x-slot:actions>
    </x-nav>

    <div class="flex">
        {{-- Sidebar --}}
        <aside class="w-64 min-h-screen bg-base-100 border-r border-base-300">
            @include('layouts.sidebar')
        </aside>

        {{-- Main Content --}}
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>

    <x-toast />
    @livewireScripts
</body>
</html>
