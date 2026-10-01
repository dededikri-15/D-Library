<?php

namespace App\Exceptions;

use Exception;

/**
 * Peminjaman tidak bisa diteruskan karena keadaan pustaka berubah.
 *
 * Ini kondisi NORMAL, bukan bug. Dua orang bisa menekan tombol "Pinjam Buku"
 * untuk buku yang sama pada detik yang sama, atau buku baru saja dipinjam
 * orang lain setelah halaman detail dibuka di browser. Karena itu kesalahan
 * ini harus dibalas dengan pesan yang bisa dibaca user, bukan halaman 500.
 *
 * Isi pesan dikirim apa adanya ke flash message, jadi jangan pernah menulis
 * detail internal (kode error, nama tabel, isi query) ke sini.
 */
class LoanNotPossibleException extends Exception
{
    public static function bookMissing(): self
    {
        return new self(__('messages.book_missing'));
    }

    /**
     * Buku berstatus `inactive`: sengaja disingkirkan dari katalog, jadi
     * memang tidak boleh dipinjam meski tidak ada tanda "__dipinjam__".
     */
    public static function bookInactive(): self
    {
        return new self(__('messages.book_inactive'));
    }

    /**
     * Pemohon adalah pemilik peminjaman aktif itu sendiri.
     *
     * Pesannya sengaja dibedakan dari "dipinjam anggota lain". Tanpa
     * pemisahan ini orang akan mengira bukunya hilang, padahal yang salah
     * adalah dia menekan tombol untuk buku yang memang sedang di tangannya.
     */
    public static function alreadyBorrowedByRequester(): self
    {
        return new self(__('messages.already_borrowed_by_requester'));
    }

    public static function borrowedByOtherMember(): self
    {
        return new self(__('messages.borrowed_by_other_member'));
    }
}
