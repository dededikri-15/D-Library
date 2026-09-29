<?php

namespace App\Actions;

use App\Exceptions\LoanNotPossibleException;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat di aplikasi yang boleh membuat baris peminjaman baru.
 *
 * Dulu aturan ini hidup di dalam `LoanController::store` (pustakawan).
 * Sekarang anggota juga bisa meminjam sendiri dari halaman detail buku, dan
 * dua jalur itu WAJIB tidak boleh berbeda aturan. Kalau masing-masing controller
 * menulis ceknya sendiri, cepat atau lambat satu jalur lupa `lockForUpdate()`
 * dan buku yang sama bisa dipinjam dua orang.
 *
 * ## Kenapa harus atomik?
 *
 * Pemeriksaan "buku ini belum dipinjam" dan penulisan status buku tidak bisa
 * dipisah, karena di antara keduanya ada celah waktu. Dua orang menekan tombol
 * pada detik yang sama akan':
 *
 *     A: baca buku    -> status "available"  -> LANJUT
 *     B: baca buku    -> status "available"  -> LANJUT   (dua-duanya lolos!)
 *     A: tulis loan
 *     B: tulis loan   -> buku sekarang dipinjam oleh 2 orang
 *
 * `DB::transaction()` + `lockForUpdate()` menutup celah itu: B tidak bisa
 * membaca baris buku sampai A selesai menulis. Permintaan yang kedua lalu
 * membaca status yang SUDAH berubah, dan ditolak dengan pesan yang jelas.
 *
 * Kenapa `lockForUpdate()` pada tabel `books` dan bukan pada `loans`? Karena
 * yang perlu diserialkan adalah "pengubahan status buku ini". Tabel `loans`
 * belum tentu punya baris untuk buku tersebut, dan baris yang tidak ada tidak
 * bisa dikunci.
 */
class BorrowBook
{
    /**
     * @throws LoanNotPossibleException
     */
    public function handle(User $member, int $bookId, ?Carbon $borrowedAt = null): Loan
    {
        $borrowedAt ??= now();

        return DB::transaction(function () use ($member, $bookId, $borrowedAt) {
            // Kunci baris buku sampai transaksi selesai. Lihat komentar kelas.
            $book = Book::query()->whereKey($bookId)->lockForUpdate()->first();

            if ($book === null) {
                throw LoanNotPossibleException::bookMissing();
            }

            if (! $book->isAvailable() && $book->status !== Book::STATUS_BORROWED) {
                throw LoanNotPossibleException::bookInactive();
            }

            $activeLoan = Loan::query()
                ->where('book_id', $book->id)
                ->active()
                ->latest('id')
                ->first();

            // Buku sedang dipinjam. Bedakan "oleh saya sendiri" dari
            // "oleh orang lain" supaya pesannya tepat sasaran.
            if ($activeLoan !== null) {
                throw $activeLoan->user_id === $member->id
                    ? LoanNotPossibleException::alreadyBorrowedByRequester()
                    : LoanNotPossibleException::borrowedByOtherMember();
            }

            $loan = Loan::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'borrowed_at' => $borrowedAt,
                // Jatuh tempo dihitung sistem dari config, bukan dari form,
                // jadi anggota tidak bisa memilih tanggal sendiri.
                'due_at' => Loan::dueAt($borrowedAt),
                'returned_at' => null,
                'status' => Loan::STATUS_BORROWED,
            ]);

            $book->update(['status' => Book::STATUS_BORROWED]);

            return $loan;
        });
    }
}
