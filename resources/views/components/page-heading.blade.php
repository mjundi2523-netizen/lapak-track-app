@props(['title', 'subtitle' => null])

<div class="mb-6 pb-4" style="border-bottom:1px solid #e5e7eb;">
    <h1 class="text-[26px] font-bold tracking-tight text-[#1b2433] m-0">{{ $title }}</h1>
    @if($subtitle)
        <div class="text-[13px] text-[#9aa3b2] mt-1.5">{{ $subtitle }}</div>
    @endif
</div>
