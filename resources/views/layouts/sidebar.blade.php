@php
    $isPremium = Auth::user()?->isPremium() ?? false;

    $topItems = [
        ['label' => 'Dashboard', 'icon' => 'o-home', 'route' => 'dashboard', 'active' => 'dashboard'],
    ];
    $sections = [
        'Master Data' => [
            ['label' => 'Pedagang',       'icon' => 'o-users',              'route' => 'dealers.index',        'active' => 'dealers.*'],
            ['label' => 'Aturan Bayar',   'icon' => 'o-banknotes',          'route' => 'payment-terms.index',  'active' => 'payment-terms.*'],
            ['label' => 'Biaya Lain-lain','icon' => 'o-plus-circle',        'route' => 'add-ons.index',        'active' => 'add-ons.*'],
            ['label' => 'Lapak',          'icon' => 'o-building-storefront','route' => 'stalls.index',         'active' => ['stalls.index', 'stalls.create', 'stalls.edit', 'stalls.show']],
            ['label' => 'Denah Lapak',    'icon' => 'o-map',                'route' => 'stalls.map',           'active' => 'stalls.map', 'premium' => true],
            ['label' => 'Kategori Pengeluaran','icon' => 'o-tag',          'route' => 'expense-categories.index', 'active' => 'expense-categories.*', 'premium' => true],
        ],
        'Transaksi' => [
            ['label' => 'Tagihan',        'icon' => 'o-document-text',      'route' => 'bills.index',          'active' => 'bills.*'],
            ['label' => 'Pembayaran',     'icon' => 'o-credit-card',        'route' => 'payments.index',       'active' => 'payments.*'],
            ['label' => 'Pengeluaran',    'icon' => 'o-arrow-trending-down','route' => 'expenses.index',       'active' => 'expenses.*', 'premium' => true],
        ],
        'Laporan' => [
            ['label' => 'Arus Kas',        'icon' => 'o-chart-bar',     'route' => 'reports.cash-flow',     'active' => 'reports.cash-flow', 'premium' => true],
            ['label' => 'Rekap Penerimaan','icon' => 'o-banknotes',     'route' => 'reports.collection',    'active' => 'reports.collection', 'premium' => true],
            ['label' => 'Rekap Pedagang',  'icon' => 'o-table-cells',   'route' => 'reports.dealer-summary','active' => 'reports.dealer-summary*', 'premium' => true],
        ],
    ];
@endphp

{{-- Logo (klik untuk buka/tutup sidebar) --}}
<button type="button" @click="collapsed = !collapsed"
        class="flex items-center gap-3 w-full px-[22px] pt-[22px] pb-[18px] transition-all duration-200 cursor-pointer"
        :class="collapsed && 'justify-center !px-0'"
        :title="collapsed ? 'Buka sidebar' : 'Tutup sidebar'">
    <div class="w-[38px] h-[38px] rounded-[11px] flex items-center justify-center shrink-0"
         style="background:var(--lt-p); box-shadow:0 6px 18px color-mix(in srgb, var(--lt-p) 45%, transparent);">
        <x-icon name="o-square-3-stack-3d" class="w-[22px] h-[22px] text-white" />
    </div>
    <span x-show="!collapsed" x-cloak class="font-bold text-xl text-white tracking-tight whitespace-nowrap">LapakTrack</span>
</button>

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
            @php
                $isActive = request()->routeIs($item['active']);
                $locked = ($item['premium'] ?? false) && ! $isPremium;
            @endphp
            @if($locked)
                {{-- Item premium terkunci: buka modal, bukan navigasi. --}}
                <button type="button" @click="$dispatch('premium-required')"
                   :class="collapsed ? 'justify-center !px-0' : ''"
                   class="flex items-center gap-[13px] w-full text-sm font-medium py-[11px] px-6 cursor-pointer transition-colors text-[#7e8799] hover:bg-white/[0.04] hover:text-[#aeb7c5]">
                    <x-icon name="{{ $item['icon'] }}" class="w-5 h-5 shrink-0" />
                    <span x-show="!collapsed" x-cloak class="whitespace-nowrap flex-1 text-left">{{ $item['label'] }}</span>
                    <x-icon name="s-lock-closed" x-show="!collapsed" x-cloak class="w-3.5 h-3.5 shrink-0 text-[#5b6678]" />
                </button>
            @else
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
            @endif
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
