<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Upload
{
    /**
     * Simpan upload. Bila gambar yang bisa dibaca GD → konversi WebP (opsional di-downscale ke
     * sisi terpanjang $maxDim) supaya ringan; selain itu (mis. PDF) simpan apa adanya.
     * Return path relatif di disk.
     */
    public static function storeImageAsWebp(
        UploadedFile $file,
        string $dir,
        string $disk = 'public',
        int $quality = 80,
        int $maxDim = 1600,
    ): string {
        $img = @imagecreatefromstring(@file_get_contents($file->getRealPath()));

        if ($img === false) {
            return $file->store($dir, $disk); // bukan gambar (PDF dll) → apa adanya
        }

        $w = imagesx($img);
        $h = imagesy($img);
        $scale = min(1, $maxDim / max($w, $h));

        if ($scale < 1) {
            $nw = (int) round($w * $scale);
            $nh = (int) round($h * $scale);
            $dst = imagecreatetruecolor($nw, $nh);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($img);
            $img = $dst;
        } else {
            imagepalettetotruecolor($img);
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        $tmp = tempnam(sys_get_temp_dir(), 'webp');
        imagewebp($img, $tmp, $quality);
        imagedestroy($img);

        $path = $dir . '/' . Str::random(40) . '.webp';
        Storage::disk($disk)->put($path, file_get_contents($tmp));
        @unlink($tmp);

        return $path;
    }
}
