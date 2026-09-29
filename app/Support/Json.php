<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;

/**
 * Flag `json_encode` yang mengubah `<`, `>`, `&`, `'` dan `"` jadi escape
 * `\uXXXX`, dipakai bersama untuk semua respons JSON di proyek ini.
 *
 * Kenapa ini konstanta global, bukan konstanta trait? Karena PHP 8.3+ MELARANG
 * akses langsung ke konstanta trait: `RespondsToAjax::JSON_SAFE_FLAGS` melempar
 * `Error: Cannot access trait constant ... directly`. Konsekuensinya nilai yang
 * sama harus ditulis dua kali — persis buatkan yang seharusnya dihindari.
 */
final class Json
{
    /**
     * Flag encoding untuk respons JSON yang isinya berasal dari data user.
     *
     * Ada karena `json_encode()` sendiri tidak escape `<`, `>`, `&`, `'`, dan
     * `"`. Isi JSON tidak dieksekusi browser, jadi ini bukan satu-satunya
     * pengaman; pengaman utama panel pratinjau pencarian (Task 14.6) adalah
     * `textContent` di JS. Flag ini lapisan kedua: begitu ada penyusun kode
     * yang menyalin judul buku ke `innerHTML` — entah untuk kebutuhan lain —
     * respons ini masih tidak menjadi celah XSS.
     */
    public const SAFE_FLAGS = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

    /**
     * Respons JSON 200 dengan flag aman di atas.
     *
     * Perhatikan posisinya: argumen kedua `response()->json()` adalah STATUS
     * CODE, flag encoding ada di argumen keempat. `json($data, $flags)` akan
     * membaca flag sebagai nomor status dan melempar
     * `The HTTP status code "15" is not valid` -> HTTP 500. Karena itu status
     * 200 ditulis eksplisit di sini, bukan mengandalkan nilai default yang
     * mudah tertukar posisinya.
     *
     * @param  array<string, mixed>  $data
     */
    public static function response(array $data): JsonResponse
    {
        return response()->json($data, 200, [], self::SAFE_FLAGS);
    }
}
