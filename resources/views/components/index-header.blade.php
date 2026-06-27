@props(['title'])

<div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3 mb-5 pb-4" style="border-bottom:1px solid #e5e7eb;">
    <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0">{{ $title }}</h1>
    <div class="flex items-center gap-2.5">
        {{ $slot }}
    </div>
</div>
