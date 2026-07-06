// Livewire 3 sudah membundel Alpine.js, jadi tidak perlu mengimpornya manual di sini.

// ── Penjaga "form belum disimpan" ────────────────────────────────────────────
// Form create/edit/void (semua <form wire:submit>) ditandai dirty saat diisi.
// Meninggalkan halaman selagi dirty → minta konfirmasi dulu, baik lewat link
// wire:navigate (sidebar, tombol Batal, dsb.) maupun reload/tutup tab.
let ltFormDirty = false;

const ltMarkDirty = (e) => {
    if (e.target.closest && e.target.closest('form[wire\\:submit]')) {
        ltFormDirty = true;
    }
};
document.addEventListener('input', ltMarkDirty, true);
document.addEventListener('change', ltMarkDirty, true);

// Submit (tombol Simpan / Enter) = perubahan sedang diproses → tak perlu peringatan.
// Sengaja hanya form wire:submit — submit form lain (mis. logout) tetap memicu
// peringatan beforeunload bila ada form yang belum disimpan.
document.addEventListener('submit', (e) => {
    if (e.target.matches && e.target.matches('form[wire\\:submit]')) {
        ltFormDirty = false;
    }
}, true);

// Link wire:navigate: cegat di fase capture SEBELUM handler navigate Livewire.
// Redirect programatik setelah simpan (redirectBack) tidak lewat sini → tak terganggu.
document.addEventListener('click', (e) => {
    if (!ltFormDirty) return;

    const link = e.target.closest && e.target.closest('a[href][wire\\:navigate], a[href][wire\\:navigate\\.hover]');
    if (!link) return;

    if (confirm('Perubahan pada form belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
        ltFormDirty = false;
    } else {
        e.preventDefault();
        e.stopPropagation();
    }
}, true);

// Reload / tutup tab / navigasi full-page (browser menampilkan dialognya sendiri).
window.addEventListener('beforeunload', (e) => {
    if (ltFormDirty) {
        e.preventDefault();
        e.returnValue = '';
    }
});

// Selesai pindah halaman → mulai bersih lagi.
document.addEventListener('livewire:navigated', () => {
    ltFormDirty = false;
});
