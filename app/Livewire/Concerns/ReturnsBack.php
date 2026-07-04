<?php

namespace App\Livewire\Concerns;

/**
 * "Kembali ke halaman asal" untuk form create/edit/void.
 *
 * Saat form dibuka, URL halaman sebelumnya (referer, lengkap dengan query
 * string filter/halaman) direkam ke $backUrl. Setelah simpan/batal, panggil
 * redirectBack('fallback.route') — user kembali persis ke halaman asalnya
 * (mis. daftar Tagihan dengan filter yang masih aktif), seperti tombol back.
 */
trait ReturnsBack
{
    public string $backUrl = '';

    /** Dipanggil otomatis oleh Livewire saat mount (konvensi mount{NamaTrait}). */
    public function mountReturnsBack(): void
    {
        $ref = (string) request()->headers->get('referer', '');

        // Hanya terima URL milik app sendiri, dan bukan halaman form ini sendiri
        // (mis. refresh / validasi gagal) supaya tidak redirect berputar.
        if ($ref !== '' && $ref !== url()->full() && str_starts_with($ref, url('/'))) {
            $this->backUrl = $ref;
        }
    }

    /** Redirect ke halaman asal; fallback ke route bernama bila referer tak ada. */
    protected function redirectBack(string $fallbackRoute, mixed $params = []): void
    {
        $this->redirect($this->backUrl ?: route($fallbackRoute, $params), navigate: true);
    }

    /** URL tujuan tombol "Batal" di view (fallback ke route bernama). */
    public function backHref(string $fallbackRoute, mixed $params = []): string
    {
        return $this->backUrl ?: route($fallbackRoute, $params);
    }
}
