<?php

namespace App\Http\Controllers;

use App\Models\LibraryCard;
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
}
