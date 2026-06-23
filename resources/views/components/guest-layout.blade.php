<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LapakTrack') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="flex items-center justify-center gap-2 mb-6">
            <x-icon name="o-square-3-stack-3d" class="w-8 h-8 text-primary" />
            <span class="font-bold text-2xl">LapakTrack</span>
        </div>

        <x-card>
            {{ $slot }}
        </x-card>
    </div>
</body>
</html>
