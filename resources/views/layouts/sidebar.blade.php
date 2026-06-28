@php
    $topItems = [
        ['label' => 'Dashboard', 'icon' => 'o-home', 'route' => 'dashboard', 'active' => 'dashboard'],
    ];
    $sections = [
        'Master Data' => [
            ['label' => 'Pedagang',       'icon' => 'o-users',              'route' => 'dealers.index',        'active' => 'dealers.*'],
            ['label' => 'Aturan Bayar',   'icon' => 'o-banknotes',          'route' => 'payment-terms.index',  'active' => 'payment-terms.*'],
            ['label' => 'Biaya Lain-lain','icon' => 'o-plus-circle',        'route' => 'add-ons.index',        'active' => 'add-ons.*'],
            ['label' => 'Lapak',          'icon' => 'o-building-storefront','route' => 'stalls.index',         'active' => 'stalls.*'],
            ['label' => 'Kategori Pengeluaran','icon' => 'o-tag',          'route' => 'expense-categories.index', 'active' => 'expense-categories.*'],
        ],
        'Transaksi' => [
            ['label' => 'Tagihan',        'icon' => 'o-document-text',      'route' => 'bills.index',          'active' => 'bills.*'],
            ['label' => 'Pembayaran',     'icon' => 'o-credit-card',        'route' => 'payments.index',       'active' => 'payments.*'],
            ['label' => 'Pengeluaran',    'icon' => 'o-arrow-trending-down','route' => 'expenses.index',       'active' => 'expenses.*'],
        ],
        'Laporan' => [
            ['label' => 'Arus Kas',        'icon' => 'o-chart-bar',   'route' => 'reports.cash-flow',     'active' => 'reports.cash-flow'],
            ['label' => 'Rekap Pedagang',  'icon' => 'o-table-cells', 'route' => 'reports.dealer-summary','active' => 'reports.dealer-summary*'],
        ],
    ];
@endphp

{{-- Logo --}}
<div class="flex items-center gap-3 px-[22px] pt-[22px] pb-[18px] transition-all duration-200"
     :class="collapsed && 'justify-center !px-0'">
    <div class="w-[38px] h-[38px] rounded-[11px] flex items-center justify-center shrink-0"
         style="background:var(--lt-p); box-shadow:0 6px 18px color-mix(in srgb, var(--lt-p) 45%, transparent);">
        <x-icon name="o-square-3-stack-3d" class="w-[22px] h-[22px] text-white" />
    </div>
    <span x-show="!collapsed" x-cloak class="font-bold text-xl text-white tracking-tight whitespace-nowrap">LapakTrack</span>
</div>

{{-- Nav --}}
<div class="flex-1 overflow-y-auto pt-1 pb-3">
    {{-- Dashboard (di luar section, selalu paling atas) --}}
    @foreach($topItems as $item)
        @php $isActive = request()->routeIs($item['active']); @endphp
        <a href="{{ route($item['route']) }}" wire:navigate
           :class="collapsed ? 'justify-center !px-0' : ''"
           class="flex items-center gap-[13px] w-full text-sm font-medium py-[11px] px-6 cursor-pointer transition-colors
                  {{ $isActive
                      ? 'text-white'
                      : 'text-[#aeb7c5] hover:bg-white/[0.04] hover:text-white' }}"
           @style([
               'background:rgba(255,255,255,0.06); box-shadow:inset 3px 0 0 var(--lt-p)' => $isActive,
           ])>
            <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0" />
            <span x-show="!collapsed" x-cloak class="whitespace-nowrap">{{ $item['label'] }}</span>
        </a>
    @endforeach

    @foreach($sections as $section => $items)
        <div x-show="!collapsed" x-cloak
             class="text-[11px] font-bold uppercase tracking-[0.08em] text-[#5b6678] px-6 pt-3.5 pb-2">{{ $section }}</div>
        <div x-show="collapsed" x-cloak class="mx-auto my-2.5 h-px w-8" style="background:rgba(255,255,255,0.07);"></div>

        @foreach($items as $item)
            @php $isActive = request()->routeIs($item['active']); @endphp
            <a href="{{ route($item['route']) }}" wire:navigate
               :class="collapsed ? 'justify-center !px-0' : ''"
               class="flex items-center gap-[13px] w-full text-sm font-medium py-[11px] px-6 cursor-pointer transition-colors
                      {{ $isActive
                          ? 'text-white'
                          : 'text-[#aeb7c5] hover:bg-white/[0.04] hover:text-white' }}"
               @style([
                   'background:rgba(255,255,255,0.06); box-shadow:inset 3px 0 0 var(--lt-p)' => $isActive,
               ])>
                <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0" />
                <span x-show="!collapsed" x-cloak class="whitespace-nowrap">{{ $item['label'] }}</span>
            </a>
        @endforeach
    @endforeach
</div>

{{-- Profile footer --}}
<div class="flex items-center gap-3" style="border-top:1px solid rgba(255,255,255,0.08);"
     :class="collapsed ? 'justify-center py-3.5' : 'py-3.5 px-4'">
    <div class="w-[38px] h-[38px] rounded-full text-white flex items-center justify-center text-[13px] font-bold shrink-0"
         style="background:var(--lt-p);">
        {{ strtoupper(\Illuminate\Support\Str::substr(Auth::user()->name, 0, 2)) }}
    </div>
    <div x-show="!collapsed" x-cloak class="flex-1 min-w-0 overflow-hidden whitespace-nowrap">
        <div class="text-sm font-semibold text-white leading-tight truncate">{{ Auth::user()->name }}</div>
        <div class="text-xs text-[#7b8597]">Admin</div>
    </div>
    <form method="POST" action="{{ route('logout') }}" x-show="!collapsed" x-cloak>
        @csrf
        <button type="submit" title="Keluar"
                class="w-[34px] h-[34px] inline-flex items-center justify-center rounded-lg text-[#8a94a6] hover:bg-white/[0.08] transition shrink-0">
            <x-icon name="o-arrow-right-on-rectangle" class="w-[19px] h-[19px]" />
        </button>
    </form>
</div>
