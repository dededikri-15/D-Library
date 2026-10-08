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

        // Jejak aksi ke pelakunya sendiri (lonceng). Tiga peristiwa bisa
        // terjadi dalam satu simpan, jadi masing-masing dinotifikasi terpisah
        // supaya judulnya jujur terhadap apa yang benar-benar berubah.
        $changes = collect($user->getChanges())->except(['updated_at', 'remember_token']);

        if ($changes->has('password')) {
            $this->notifySelf($user, 'password_changed', [], route('profile.show'));
        }

        if ($changes->has('avatar') && filled($user->avatar)) {
            $this->notifySelf($user, 'avatar_replaced', [], route('profile.show'));
        }

        if ($changes->has('avatar') && blank($user->avatar)) {
            $this->notifySelf($user, 'avatar_removed', [], route('profile.show'));
        }

        if ($changes->except(['password', 'avatar'])->isNotEmpty()) {
            $this->notifySelf($user, 'profile_updated', [], route('profile.show'));
        }

        return redirect()
            ->route('profile.show')
            ->with('status', __('messages.profile_updated'));
    }

    /**
     * Hapus foto profil lewat tombol khusus (tanpa lewat form update).
     *
     * Kenapa route terpisah, bukan cuma checkbox `remove_avatar` di form:
     * menghapus foto adalah tindakan yang berdiri sendiri dan tidak perlu
     * ikut mengirim seluruh field profil. Satu klik + dialog konfirmasi
     * lebih jelas daripada centang checkbox lalu menekan "Simpan Perubahan".
     *
     * Berkas dihapus dari disk lebih dulu, baru kolom dikosongkan — kalau
     * penghapusan file gagal (ditangkap `deleteUpload`), kolom tetap diisi
     * dan tampilan tidak pernah menunjukkan foto yang sudah tidak ada.
     * Setelah ini tampilan kembali ke avatar jenis kelamin.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Sudah tidak ada foto: jangan menampilkan "berhasil" untuk aksi
        // yang tidak melakukan apa-apa (mis. tab kedua yang sudah basi).
        if (blank($user->avatar)) {
            return redirect()->route('profile.show');
        }

        $this->deleteUpload($user->avatar, User::avatarDisk());

        $user->update(['avatar' => null]);

        $this->notifySelf($user, 'avatar_removed', [], route('profile.show'));

        return redirect()
            ->route('profile.show')
            ->with('status', __('messages.photo_removed'));
    }
}
