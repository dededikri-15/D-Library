<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\ManagesMasterData;
use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Models\User;
use App\Notifications\ActionLogged;
use App\Support\CatalogSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

abstract class Controller
{
    // Dipakai di sini, bukan di tiap controller anak, karena helper bersama
    // (`masterIndex`, `destroyMasterData`) memanggil `success()`/`failure()`.
    // Kalau trait-nya hanya menempel di kelas anak, pemanggilan dari kelas
    // induk terlihat seperti method yang tidak ada.
    //
    // `HandlesUploads` ikut di sini karena `destroyMasterData()` menghapus
    // berkas buku yang ikut terhapus oleh cascade. Controller anak yang
    // memakai trait yang sama (Book, Author) tidak perlu `use` lagi.
    use HandlesUploads;
    use ManagesMasterData;
    use RespondsWithFlash;

    /**
     * Ambil nilai filter query yang valid, abaikan yang tidak valid.
     *
     * Filter di URL sering diketik atau diutak-atik orang. Kalau nilai buruk
     * langsung dibalas 422/redirect, orang yang salah ketik akan
     * mendapat halaman kosong tanpa penjelasan. Jadi di sini nilai yang
     * tidak lolos validasi simply diabaikan, dan halaman tetap tampil.
     *
     * Yang penting: nilainya sudah jadi tipe yang benar (mis. `user_id`
     * menjadi integer), sehingga tidak pernah sampai ke SQL sebagai teks.
     */
    protected function validFilters(Request $request, array $rules): array
    {
        $validator = Validator::make($request->query(), $rules);

        // PENTING: pakai `valid()`, bukan `validated()`. Method `validated()`
        // melempar ValidationException kalau ada yang gagal, yang akan membuat
        // halaman daftar memantul dengan 302. `valid()` diam-diam mengembalikan
        // hanya data yang lolos.
        $failed = $validator->errors()->keys();
        $valid = $validator->valid();

        $filters = [];

        foreach (array_keys($rules) as $key) {
            if (in_array($key, $failed, true)) {
                continue;
            }

            $value = $valid[$key] ?? null;

            if ($value !== null && $value !== '') {
                $filters[$key] = $value;
            }
        }

        return $filters;
    }

    /**
     * Catat aksi yang baru saja dijalankan sebagai notifikasi untuk PELAKUNYA.
     *
     * Notifikasi in-app dipakai sebagai jejak tindakan: user melihat di
     * lonceng bahwa dia pernah menambah/mengubah/menghapus sesuatu, lengkap
     * dengan tautan ke halaman yang relevan. Penerimanya SELALU user yang
     * sedang login — bukan orang lain — sehingga ini bukan kanal pesan
     * antar-user.
     *
     * `$url` diisi route() tujuan supaya klik notifikasi langsung membuka
     * halaman yang berkaitan. Kalau null, klik akan mendarat di halaman
     * notifikasi (lihat NotificationController::destination).
     *
     * @param  array<string, mixed>  $data  parameter tambahan untuk placeholder badan pesan
     */
    protected function notifySelf(User $user, string $type, array $data = [], ?string $url = null): void
    {
        $user->notify(new ActionLogged($type, $data, $url));
    }

    /**
     * Terapkan pencarian sederhana pada satu kolom teks.
     *
     * Harus dipakai di controller yang sudah divalidasi lebih dulu.
     */
    protected function applySearch(Builder $query, string $column, ?string $term): Builder
    {
        $term = trim((string) $term);

        if ($term === '') {
            return $query;
        }

        return $query->whereRaw(
            "LOWER({$column}) LIKE ? ESCAPE '\\'",
            ['%'.CatalogSearch::escapeLike(mb_strtolower($term)).'%']
        );
    }
}
