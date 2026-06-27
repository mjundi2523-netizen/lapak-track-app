<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'LapakTrack') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen flex items-center justify-center p-4"
      style="background:linear-gradient(160deg, color-mix(in srgb, var(--lt-p) 10%, #f4f4f6), #f4f4f6 60%);">
    <div class="w-full max-w-[420px]">
        <div class="flex items-center justify-center gap-2.5 mb-6">
            <x-icon name="o-square-3-stack-3d" class="w-8 h-8 text-[var(--lt-p)]" />
            <span class="font-bold text-2xl tracking-tight text-[#18181b]">LapakTrack</span>
        </div>

        <div class="bg-white border border-[#e5e7eb] rounded-2xl shadow-[0_1px_3px_rgba(0,0,0,0.06)] p-7">
            {{ $slot }}
        </div>
    </div>
</body>
</html>
