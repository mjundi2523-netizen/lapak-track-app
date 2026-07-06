// Livewire 3 sudah membundel Alpine.js, jadi tidak perlu mengimpornya manual di sini.

// ── Penjaga "form belum tersimpan" ───────────────────────────────────────────
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

// Livewire menjalankan navigasi link dari mousedown→mouseup (bukan click), jadi
// mencegat event click terlambat. Satu-satunya titik yang benar-benar bisa
// membatalkan adalah event cancelable `livewire:navigate` — tapi event itu juga
// dipicu redirect programatik setelah simpan. Pembeda: catat waktu interaksi
// terakhir pada link wire:navigate; event yang muncul segera setelahnya = klik user.
let ltNavIntentAt = 0;

const ltFlagNavIntent = (e) => {
    if (e.target.closest && e.target.closest('a[href][wire\\:navigate], a[href][wire\\:navigate\\.hover]')) {
        ltNavIntentAt = Date.now();
    }
};
document.addEventListener('mouseup', ltFlagNavIntent, true);
document.addEventListener('keydown', ltFlagNavIntent, true);
document.addEventListener('click', ltFlagNavIntent, true);

document.addEventListener('livewire:navigate', (e) => {
    const fromUser = Date.now() - ltNavIntentAt < 1000;
    ltNavIntentAt = 0;

    // Redirect programatik (redirectBack setelah simpan) → biarkan lewat.
    if (!ltFormDirty || !fromUser) return;

    if (confirm('Perubahan pada form belum disimpan. Yakin ingin meninggalkan halaman ini?')) {
        ltFormDirty = false;
    } else {
        e.preventDefault();
    }
});

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
