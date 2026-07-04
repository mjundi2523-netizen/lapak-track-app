{{-- Header kolom yang bisa diklik untuk sort.
     Pakai @include agar $sortBy/$sortDir warisan dari view Livewire pemanggil:
     @include('partials.sort-th', ['field' => 'due_date', 'label' => 'Jatuh Tempo', 'align' => 'right']) --}}
@php
    $align = $align ?? 'left';
    $active = ($sortBy ?? '') === $field;
@endphp
<th class="lt-th {{ $align === 'right' ? 'text-right' : ($align === 'center' ? 'text-center' : '') }}">
    <button type="button" wire:click="sort('{{ $field }}')" title="Urutkan"
            class="inline-flex items-center gap-1 bg-transparent border-0 p-0 m-0 cursor-pointer select-none transition-colors"
            style="font:inherit; text-transform:inherit; letter-spacing:inherit; {{ $active ? 'color:var(--lt-p);' : 'color:inherit;' }}">
        <span>{{ $label }}</span>
        @if($active)
            <x-icon name="{{ ($sortDir ?? 'asc') === 'asc' ? 's-chevron-up' : 's-chevron-down' }}" class="w-3 h-3 shrink-0" />
        @else
            <x-icon name="s-chevron-up-down" class="w-3 h-3 shrink-0 opacity-40" />
        @endif
    </button>
</th>
