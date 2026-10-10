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
        $card = LibraryCard::createFor($request->user());

        return view('library-cards.show', [
            'card' => $card,
        ]);
    }
}
