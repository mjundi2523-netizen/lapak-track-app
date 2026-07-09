<?php

namespace App\Models\Concerns;

use Sqids\Sqids;

/**
 * Menyamarkan PK numerik di URL memakai Sqids: /pedagang/2 → /pedagang/Uk60ax3TqM.
 *
 * Cukup `use HasObfuscatedId;` di model — semua route({name}, $model) otomatis
 * menghasilkan key ter-encode, dan implicit route model binding men-decode balik.
 * Decode gagal / sqid milik model lain → 404 (seed per-model diverifikasi).
 *
 * Catatan: ini pelengkap estetika+obscurity; otorisasi tetap di global scope
 * BelongsToMarket (resolveRouteBinding di bawah tetap melewati scope itu).
 */
trait HasObfuscatedId
{
    protected static ?Sqids $sqidsEncoder = null;

    protected static function sqids(): Sqids
    {
        return static::$sqidsEncoder ??= new Sqids(
            alphabet: (string) config('sqids.alphabet'),
            minLength: (int) config('sqids.min_length', 10),
        );
    }

    /** Pembeda antar model: id sama di tabel berbeda menghasilkan sqid berbeda. */
    protected static function sqidSeed(): int
    {
        return crc32(static::class) % 997;
    }

    public static function encodeKey(int|string $id): string
    {
        return static::sqids()->encode([(int) $id, static::sqidSeed()]);
    }

    /** Kembalikan PK asli, atau null bila sqid tidak valid untuk model ini. */
    public static function decodeKey(?string $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $numbers = static::sqids()->decode($value);

        if (count($numbers) !== 2 || $numbers[1] !== static::sqidSeed()) {
            return null;
        }

        // Tolak bentuk non-kanonik (satu id bisa punya banyak alias di Sqids).
        if (static::sqids()->encode($numbers) !== $value) {
            return null;
        }

        return $numbers[0];
    }

    public function getRouteKey(): string
    {
        return static::encodeKey($this->getKey());
    }

    /** Akses `$model->obfuscated_id` di blade (mis. option-value x-choices). */
    public function getObfuscatedIdAttribute(): string
    {
        return static::encodeKey($this->getKey());
    }

    public function resolveRouteBinding($value, $field = null)
    {
        // Binding eksplisit ke kolom lain (mis. {dealer:nik}) tetap perilaku bawaan.
        if ($field !== null && $field !== $this->getRouteKeyName()) {
            return parent::resolveRouteBinding($value, $field);
        }

        $id = static::decodeKey((string) $value);

        // null → Laravel lempar 404. Query lewat model = global scope tetap aktif.
        return $id === null ? null : $this->where($this->getKeyName(), $id)->first();
    }
}
