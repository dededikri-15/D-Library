<?php

namespace App\Actions;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Catat pengembalian buku oleh pustakawan — SATU tempat untuk seluruh
 * pengembalian yang menyelesaikan pinjaman (Task 10.7).
 *
 * Dipisah dari LoanController mengikuti pola BorrowBook: controller hanya
 * menangani HTTP (otorisasi, flash, notifikasi), sedangkan transaksi yang
 * menyentuh baris peminjaman, eksemplar, buku, dan sanksi anggota hidup di
 * sini — supaya aturannya tidak bisa berbeda antar jalur panggilan.
 *
 * Dua hal kritis yang dilakukan DI DALAM transaksi yang sama:
 *
 * 1. Sanksi 72 jam dijatuhkan bersamaan dengan pencatatan pengembalian —
 *    keterlambatan dan larangan meminjam adalah satu peristiwa. Kalau
 *    dipisah, proses yang mati di tengah meninggalkan pengembalian "bersih"
 *    tanpa sanksi.
 * 2. Kunci baris anggota SEBELUM menulis: dua pustakawan yang memproses
 *    pengembalian telat anggota yang sama pada detik yang sama akan
 *    diserialkan, bukan saling menimpa.
 */
class CompleteReturn
{
    /**
     * @return bool false = pinjaman sudah tidak aktif (sudah pernah dikembalikan)
     */
    public function handle(Loan $loan): bool
    {
        return DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->isActive()) {
                return false;
            }

            $lockedLoan->update([
                'returned_at' => now(),
                'status' => Loan::STATUS_RETURNED,
            ]);

            if ($lockedLoan->isLateReturn()) {
                $member = User::query()->whereKey($lockedLoan->user_id)->lockForUpdate()->first();
                $member?->applyLateReturnSuspension();
            }

            $book = Book::query()->whereKey($lockedLoan->book_id)->lockForUpdate()->first();
            $copy = $lockedLoan->book_copy_id
                ? BookCopy::query()->whereKey($lockedLoan->book_copy_id)->lockForUpdate()->first()
                : null;

            // Loans lama belum tentu terhubung ke ID eksemplar.
            $copy ??= $book?->copies()
                ->where('status', BookCopy::STATUS_BORROWED)
                ->lockForUpdate()
                ->first();
            $copy?->update(['status' => BookCopy::STATUS_AVAILABLE]);

            if ($book && $book->status !== Book::STATUS_INACTIVE) {
                $book->update([
                    'status' => $book->availableCopies()->exists()
                        ? Book::STATUS_AVAILABLE
                        : Book::STATUS_BORROWED,
                ]);
            }

            return true;
        });
    }
}
