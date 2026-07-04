<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Url;

/**
 * Sort tabel dengan klik judul kolom (header pakai partial `partials.sort-th`).
 *
 * Komponen pemakai meng-override sortColumns(): ['field' => ekspresi SQL].
 * `field` = nilai di sort-th; ekspresi = nama kolom biasa atau raw SQL
 * (subquery utk kolom relasi/terhitung). Panggil applySort($query, $default)
 * di render() — $default dipakai saat user belum memilih kolom.
 */
trait Sortable
{
    #[Url(except: '')]
    public string $sortBy = '';

    #[Url(except: 'asc')]
    public string $sortDir = 'asc';

    /** Klik header: kolom sama → balik arah; kolom baru → mulai asc. */
    public function sort(string $field): void
    {
        if (! array_key_exists($field, $this->sortColumns())) {
            return;
        }

        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'asc';
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    /** Peta kolom yang boleh di-sort — override di komponen. */
    protected function sortColumns(): array
    {
        return [];
    }

    protected function applySort($query, callable $default)
    {
        $columns = $this->sortColumns();

        if ($this->sortBy !== '' && isset($columns[$this->sortBy])) {
            $dir = $this->sortDir === 'desc' ? 'desc' : 'asc';
            $query->orderByRaw('(' . $columns[$this->sortBy] . ') ' . $dir);
        } else {
            $default($query);
        }

        return $query;
    }
}
