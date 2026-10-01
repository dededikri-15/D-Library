<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman profil: melihat data diri sendiri sekaligus mengubahnya.
 *
 * Route-nya hanya butuh middleware `auth` — sengaja TIDAK memakai
 * `role:pustakawan` atau `role:anggota`. Mengatur profil adalah hak setiap
 * user yang sudah masuk, bukan privilege staff. Kalau route-nya didaftarkan di
 * dua group role terpisah, salah satunya akan mudah lupa saat role baru
 * ditambahkan.
 *
 * Foto profil ditangani dengan aturan yang sama seperti cover buku: nama file
 * di-generate ulang (hash), di disk publik, dan berkas lama SELALU dihapus
 * saat diganti atau saat avatar dilepas — termasuk lewat `remove_avatar`.
 */
class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        // Angka ringkas untuk kartu statistik. `loadCount` membuat satu query
        // terpisah, bukan N+1: empat angka ini tidak layak diambil di template
        // dengan `->count()` di dalam loop.
        $user->loadCount(['loans', 'activeLoans', 'favorites', 'readingHistories']);

        return view('profile.show', ['user' => $user]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        // `avatar` dan `remove_avatar` bukan kolom yang bisa diisi mentah: yang
        // pertama adalah objek UploadedFile (yang kalau dibiarkan ikut masuk
        // akan ditulis ke kolom string dan meledak jadi error), yang kedua
        // cuma flag.
        $data = $request->safe()->except(['avatar', 'remove_avatar']);

        // Kolom kata sandi kosong = "biarkan yang lama". Tanpa unset ini, nilai
        // kosong akan menimpa password lama dengan string kosong.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $avatar = [];
        $this->applyUploadField(
            $avatar,
            'avatar',
            $request,
            $user->avatar,
            User::avatarDisk(),
            'avatars',
        );

        $user->update([...$data, ...$avatar]);

        return redirect()
            ->route('profile.show')
            ->with('status', __('messages.profile_updated'));
    }
}
