<?php

namespace App\Http\Controllers\Concerns;

use App\Support\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Menjawab satu aksi dengan dua bentuk, tergantung pemanggilnya.
 *
 * Aksi yang sama harus melayani dua pemanggil yang sangat berbeda:
 *
 * - Form biasa (JS mati, atau JS-nya gagal): user menekan tombol, halaman
 *   dirender ulang, flash message muncul sebagai toast. Jalur ini WAJIB tetap
 *   utuh — kalau hilang begitu saja, satu error JS berarti user tidak bisa
 *   menyelesaikan aksi sama sekali.
 * - `fetch()` dari JS (Task 14.7): halaman tidak di-reload, jadi harus dapat
 *   JSON yang bisa dipakai JS untuk memperbarui tombol.
 *
 * Menyusun dua jalur ini manual (`if ($request->ajax()) ... else ...`) di
 * setiap method itu repetitif dan mudah salah: biasanya author menulis respons
 * JSON lalu lupa memastikan jalur redirect masih ada, dan test yang baru akan
 * membuka jalur yang diperbaiki.
 */
trait RespondsToAjax
{
    /**
     * Apakah pemanggil ini meminta JSON dan tidak butuh halaman penuh?
     *
     * `expectsJson()` sudah cukup: ia mengembalikan true kalau header `Accept`
     * menyebut JSON, atau kalau request ditandai AJAX
     * (`X-Requested-With: XMLHttpRequest`) dan pemanggil mau menerima apa saja.
     */
    protected function wantsJson(Request $request): bool
    {
        return $request->expectsJson();
    }

    /**
     * Respons JSON untuk pemanggil AJAX.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function jsonStatus(string $message, array $payload = []): JsonResponse
    {
        return Json::response(['message' => $message] + $payload);
    }

    /**
     * Satu aksi, dua bentuk respons. Pemanggil yang menentukan.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function respond(Request $request, string $message, array $payload = []): JsonResponse|RedirectResponse
    {
        if ($this->wantsJson($request)) {
            return $this->jsonStatus($message, $payload);
        }

        return back()->with('status', $message);
    }
}
