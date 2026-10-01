<?php

namespace App\Actions;

use App\Exceptions\LoanNotPossibleException;
use App\Models\Book;
use App\Models\BookCopy;
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
 * dan satu eksemplar fisik bisa dipinjam dua orang.
 *
 * ## Kenapa harus atomik?
 *
 * Pemeriksaan stok dan penulisan status eksemplar tidak bisa dipisah, karena
 * di antara keduanya ada celah waktu. Dua orang menekan tombol untuk salinan
 * terakhir pada detik yang sama akan:
 *
 *     A: baca stok    -> salinan tersedia    -> LANJUT
 *     B: baca stok    -> salinan tersedia    -> LANJUT   (dua-duanya lolos!)
 *     A: kunci salinan, buat loan
 *     B: menunggu     -> stok dibaca ulang setelah A commit
 *
 * `DB::transaction()` + `lockForUpdate()` menutup celah itu: B tidak bisa
 * membaca baris buku sampai A selesai menulis. Permintaan kedua lalu memilih
 * salinan berikutnya, atau ditolak bila semua salinan sudah dipinjam.
 *
 * Kenapa `lockForUpdate()` pada tabel `books` dan bukan pada `loans`? Karena
 * yang perlu diserialkan adalah pemilihan stok judul ini. Baris buku menjadi
 * kunci bersama untuk pinjam, pengembalian, dan perubahan inventaris.
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

            if ($book->status === Book::STATUS_INACTIVE) {
                throw LoanNotPossibleException::bookInactive();
            }

            $activeLoan = Loan::query()
                ->where('book_id', $book->id)
                ->where('user_id', $member->id)
                ->active()
                ->latest('id')
                ->first();

            if ($activeLoan !== null) {
                throw LoanNotPossibleException::alreadyBorrowedByRequester();
            }

            $copy = BookCopy::query()
                ->where('book_id', $book->id)
                ->where('status', BookCopy::STATUS_AVAILABLE)
                ->lockForUpdate()
                ->first();

            if ($copy === null) {
                throw LoanNotPossibleException::borrowedByOtherMember();
            }

            $loan = Loan::create([
                'user_id' => $member->id,
                'book_id' => $book->id,
                'book_copy_id' => $copy->id,
                'borrowed_at' => $borrowedAt,
                // Jatuh tempo dihitung sistem dari config, bukan dari form,
                // jadi anggota tidak bisa memilih tanggal sendiri.
                'due_at' => Loan::dueAt($borrowedAt),
                'returned_at' => null,
                'status' => Loan::STATUS_BORROWED,
            ]);

            $copy->update(['status' => BookCopy::STATUS_BORROWED]);
            $book->update([
                'status' => $book->availableCopies()->exists()
                    ? Book::STATUS_AVAILABLE
                    : Book::STATUS_BORROWED,
            ]);

            return $loan;
        });
    }
}
