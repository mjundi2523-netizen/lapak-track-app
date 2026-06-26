{{-- Pagination LapakTrack — mengikuti referensi claude.ai/design.
     Override global untuk semua ->links() di komponen Livewire. --}}
@php($pageName = method_exists($paginator, 'getPageName') ? $paginator->getPageName() : 'page')

@if ($paginator->total() > 0)
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between gap-4 flex-wrap">
        <span class="text-[13px] text-[#71717a]">
            Menampilkan {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }} dari {{ $paginator->total() }}
        </span>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1.5">
                {{-- Sebelumnya --}}
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" class="lt-page-arrow lt-page-disabled">‹</span>
                @else
                    <button type="button" wire:key="pg-prev" wire:click="previousPage('{{ $pageName }}')" wire:loading.attr="disabled" rel="prev" class="lt-page-arrow" aria-label="Sebelumnya">‹</button>
                @endif

                {{-- Nomor halaman (windowed via $elements) --}}
                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span class="lt-page-ellipsis">{{ $element }}</span>
                    @endif
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" class="lt-page lt-page-active" wire:key="pg-{{ $page }}">{{ $page }}</span>
                            @else
                                <button type="button" wire:key="pg-{{ $page }}" wire:click="gotoPage({{ $page }}, '{{ $pageName }}')" class="lt-page" aria-label="Halaman {{ $page }}">{{ $page }}</button>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Berikutnya --}}
                @if ($paginator->hasMorePages())
                    <button type="button" wire:key="pg-next" wire:click="nextPage('{{ $pageName }}')" wire:loading.attr="disabled" rel="next" class="lt-page-arrow" aria-label="Berikutnya">›</button>
                @else
                    <span aria-disabled="true" class="lt-page-arrow lt-page-disabled">›</span>
                @endif
            </div>
        @endif
    </nav>
@endif
