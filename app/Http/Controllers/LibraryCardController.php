<?php

namespace App\Http\Controllers;

use App\Models\LibraryCard;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LibraryCardController extends Controller
{
    /**
     * Halaman kartu perpustakaan digital milik anggota yang sedang login.
     *
     * Kartu dibuat saat pertama kali dibuka (`createFor` memakai
     * `firstOrCreate`, jadi klik ganda atau request bersamaan aman).
     * Tidak ada route untuk melihat kartu orang lain — setiap anggota
     * hanya berhak melihat kartunya sendiri.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        /*
         * Kartu hanya bisa dibuat jika tanggal lahir sudah terisi — nomor
         * kartu (DDMMYY) membutuhkan tanggal lahir. Jika belum, view akan
         * menampilkan pesan "lengkapi profil dulu" alih-alih kartu rusak.
         */
        $card = $user->hasBirthDate()
            ? LibraryCard::createFor($user)
            : null;

        return view('library-cards.show', [
            'card' => $card,
        ]);
    }

    /**
     * Perpanjang masa berlaku kartu anggota (dipanggil pustakawan).
     *
     * Route berada di grup `role:pustakawan`, jadi anggota tidak pernah
     * bisa memperpanjang kartunya sendiri. Pustakawan menekan tombol ini
     * setelah memeriksa checklist syarat di halaman edit pengguna.
     */
    public function renew(Request $request, User $user): RedirectResponse
    {
        if ($user->isStaff()) {
            return $this->backWithStatus(__('messages.card_not_member'));
        }

        // Kartu anggota dibuat saat pertama kali dibuka. Kalau belum ada
        // (anggota belum pernah buka halaman kartu) buat dulu, supaya
        // pustakawan tetap bisa memperpanjang tanpa meminta anggota
        // membuka halaman kartunya lebih dulu.
        $card = $user->hasBirthDate()
            ? LibraryCard::createFor($user)
            : null;

        if ($card === null) {
            return $this->backWithStatus(__('messages.card_no_birthdate'));
        }

        // Syarat diperiksa ULANG di server. Tombol di view memang sudah
        // disabled kalau syarat belum terpenuhi, tapi view bisa
        // dilewati (curl, tab lama, dsb.) — keputusan akhir selalu di sini.
        if (! $card->canRenew()) {
            return $this->failure('users.edit', __('messages.card_not_renewable'), ['user' => $user]);
        }

        $card->renew();

        $this->notifySelf(
            $request->user(),
            'card_renewed',
            ['subject' => $user->name],
            route('users.edit', $user),
        );

        return $this->success('users.edit', __('messages.card_renewed'), ['user' => $user]);
    }
}
