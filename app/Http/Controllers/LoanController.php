<?php

namespace App\Http\Controllers;

use App\Actions\BorrowBook;
use App\Actions\MarkOverdueLoans;
use App\Exceptions\LoanNotPossibleException;
use App\Http\Requests\LoanRequest;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanApproved;
use App\Notifications\LoanBorrowed;
use App\Notifications\LoanCreated;
use App\Notifications\LoanRejected;
use App\Notifications\LoanReturned;
use App\Notifications\LoanReturnRequested;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function __construct(
        protected BorrowBook $borrowBook,
        protected MarkOverdueLoans $markOverdue,
    ) {
        //
    }

    /**
     * Daftar semua peminjaman untuk pustakawan (PRD §9, Task 10.12).
     */
    public function index(Request $request): View
    {
        /*
         * Filter query divalidasi, bukan diambil mentah. `user_id` menuju
         * kolom bigint: tanpa validasi, `?user_id=abc` akan membuat
         * PostgreSQL melempar QueryException -> HTTP 500.
         */
        $filters = $this->validFilters($request, [
            'status' => ['nullable', Rule::in(Loan::statuses())],
            'user_id' => ['nullable', 'integer', 'min:1'],
            /*
             * Dipakai oleh kartu "Peminjaman Aktif" di dasbor. Filter `status`
             * hanya menerima satu status, sedangkan "aktif" berarti borrowed +
             * overdue sekaligus, jadi butuh parameter sendiri.
             *
             * Hanya `1` yang diterima: `?active=0` artinya "jangan filter",
             * bukan "tampilkan yang tidak aktif". Nilai lain diabaikan oleh
             * `validFilters()` — bukan halaman kosong, tapi angka di dasbor dan
             * isi halaman jadi berbeda, dan itu lebih membingungkan.
             */
            'active' => ['nullable', Rule::in(['1'])],
        ]);

        /*
         * Status `overdue` dihitung dari kolom `due_at`, jadi harus
         * disegarkan sebelum tabel ini ditampilkan. Tanpa itu buku yang
         * sudah lewat tempo masih terlihat "Dipinjam" selama scheduler
         * belum sempat jalan. Lihat MarkOverdueLoans.
         */
        $this->markOverdue->handle();

        $loans = Loan::query()
            ->with(['user', 'book', 'bookCopy'])
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            // `scopeActive()` definisi yang sama dengan angka "Peminjaman
            // Aktif" di dasbor, jadi kartu dan daftar ini tidak bisa
            // menghitung dua hal berbeda.
            ->when(($filters['active'] ?? null) === '1', fn ($query) => $query->active())
            ->when($filters['user_id'] ?? null, fn ($query, $userId) => $query->where('user_id', $userId))
            ->latest('borrowed_at')
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('loans.index', [
            'loans' => $loans,
            'statuses' => Loan::statuses(),
            'onlyActive' => ($filters['active'] ?? null) === '1',
        ]);
    }

    /**
     * Catat peminjaman baru oleh pustakawan.
     *
     * Aturan "buku belum dipinjam" sengaja TIDAK ditulis ulang di sini.
     * Pemeriksaan, pembuatan baris loan, dan pengubahan status buku semuanya
     * diserahkan ke BorrowBook, supaya jalur staff dan jalur anggota tidak
     * bisa berbeda aturan. Kalau masing-masing controller menulis ceknya
     * sendiri, cepat atau lambat salah satu jalur lupa mengunci baris buku
     * dan satu eksemplar bisa dipinjam dua orang.
     */
    public function store(LoanRequest $request): RedirectResponse
    {
        $member = User::findOrFail($request->integer('user_id'));

        try {
            $loan = $this->borrowBook->handle($member, $request->integer('book_id'), $request->date('borrowed_at'));
        } catch (LoanNotPossibleException $e) {
            // Pesannya ditempel ke field `book_id`, bukan flashed sebagai
            // error umum, supaya muncul tepat di bawah dropdown buku.
            return back()->withErrors(['book_id' => $e->getMessage()])->withInput();
        }

        $this->announceNewLoan($loan, $request->user(), recordedByStaff: true);

        // Jejak pelaku: anggota sudah dapat "disetujui", staf lain sudah
        // dikabari "peminjaman baru" — pencatatnya sendiri belum.
        $this->notifySelf(
            $request->user(),
            'loan_recorded',
            [
                'subject' => $loan->book?->title ?? __('loans.book_deleted'),
                'user_name' => $member->name,
            ],
            route('loans.index'),
        );

        return $this->success('loans.index', __('messages.loan_created'));
    }

    /**
     * Anggota meminjam buku sendiri dari halaman detail buku (PRD §5 & §9).
     *
     * Tanggal pinjam dan jatuh tempo tidak pernah diterima dari form. Kalau
     * anggota boleh memilih sendiri, dia bisa mengetik tanggal jatuh tempo
     * yang jauh di masa depan dan peminjamannya tidak akan pernah terlambat.
     */
    public function borrow(Request $request, Book $book): RedirectResponse
    {
        Gate::authorize('borrow', $book);

        try {
            $loan = $this->borrowBook->handle($request->user(), $book->getKey());
        } catch (LoanNotPossibleException $e) {
            // Selalu kembali ke detail buku. `back()` tidak boleh dipakai
            // di sini: kalau pesan ini muncul dari sumber lain, referer
            // bisa saja tidak cocok dan user mendarat di halaman yang
            // tidak ada hubungannya dengan peminjaman.
            return redirect()
                ->route('books.show', $book)
                ->with('status', $e->getMessage());
        }

        $this->announceNewLoan($loan, $request->user(), recordedByStaff: false);

        return $this->success(
            'books.show',
            __('messages.book_borrowed'),
            ['book' => $book]
        );
    }

    /**
     * Pengembalian buku oleh pustakawan (Task 10.7).
     */
    public function returnBook(Request $request, Loan $loan): RedirectResponse
    {
        if (! $this->completeReturn($loan)) {
            return $this->backWithStatus(__('messages.book_already_returned'));
        }

        /*
         * `completeReturn()` menulis barisnya lewat instance KUNCI yang
         * berbeda di dalam transaksi, jadi `$loan` yang ini masih membawa
         * nilai lama — `returned_at` dan denda snapshot-nya masih null.
         * Refresh dulu, kalau tidak notifikasi anggota dikirim dengan
         * tanggal pengembalian kosong dan denda Rp 0.
         */
        $loan->refresh();
        $loan->user?->notify(new LoanReturned($loan));

        // Jejak pelaku (pustakawan): route ini hanya untuk staf, jadi pelaku
        // selalu user login — bukan anggota peminjam.
        $this->notifySelf(
            $request->user(),
            'return_recorded',
            [
                'subject' => $loan->book?->title ?? __('loans.book_deleted'),
                'returned_at' => $loan->returned_at?->toIso8601String(),
            ],
            route('loans.index'),
        );

        return $this->success('loans.index', __('messages.book_returned'));
    }

    public function requestReturn(Request $request, Loan $loan): RedirectResponse
    {
        abort_unless($loan->user_id === $request->user()->id, 403);

        $result = DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->isActive()) {
                return 'returned';
            }

            if ($lockedLoan->hasReturnRequest()) {
                return 'pending';
            }

            $lockedLoan->update(['return_requested_at' => now()]);

            return 'requested';
        });

        // Hanya pengajuan BARU yang memberi tahu pustakawan. Pengajuan
        // kedua ('pending') sudah pernah dikabari pada kali pertama.
        if ($result === 'requested') {
            $this->notifyLibrarians(new LoanReturnRequested($loan));

            // Jejak pelaku (anggota): tadi hanya pustakawan yang dikabari —
            // pengirimnya sendiri cuma melihat pesan flash.
            $this->notifySelf(
                $request->user(),
                'return_requested_self',
                [
                    'subject' => $loan->book?->title ?? __('loans.book_deleted'),
                    'due_at' => $loan->due_at?->toIso8601String(),
                ],
                route('loans.mine'),
            );
        }

        return match ($result) {
            'returned' => $this->backWithStatus(__('messages.loan_inactive')),
            'pending' => $this->backWithStatus(__('messages.return_pending')),
            default => $this->backWithStatus(__('messages.return_requested')),
        };
    }

    /**
     * Perpanjangan peminjaman — oleh anggota (pinjamannya sendiri) atau
     * pustakawan (pinjaman siapa pun).
     *
     * Aturan kelayakan ada di `Loan::canRenew()`: tombol di view memakai
     * method yang sama, jadi kasus tombol terlihat tapi server menolak
     * hampir mustahil terjadi — kecuali saat race (dua orang menekan
     * perpanjangan bersamaan), yang ditangani `lockForUpdate` di bawah.
     */
    public function renew(Request $request, Loan $loan): RedirectResponse
    {
        $user = $request->user();

        // Anggota hanya boleh memperpanjang pinjamannya sendiri. URL route
        // ini bisa ditebak orang, jadi otorisasinya dicek di server —
        // bukan disembunyikan di view.
        if (! $user->isPustakawan() && $loan->user_id !== $user->getKey()) {
            abort(403);
        }

        $result = DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->canRenew()) {
                return 'ineligible';
            }

            $lockedLoan->update([
                'due_at' => $lockedLoan->renewalDueAt(),
                'renew_count' => $lockedLoan->renew_count + 1,
            ]);

            return 'renewed';
        });

        if ($result === 'ineligible') {
            return back()->with('error', __('messages.loan_not_renewable'));
        }

        if ($result === 'renewed') {
            // `due_at` yang baru ada di instance transaksi ($lockedLoan),
            // bukan di $loan ini — refresh dulu sebelum ikut di payload.
            $loan->refresh();

            // Jejak pelaku: perpanjangan tidak punya notifikasi sama sekali
            // sebelumnya — hanya pesan flash yang hilang saat refresh.
            $this->notifySelf(
                $user,
                'loan_renewed',
                [
                    'subject' => $loan->book?->title ?? __('loans.book_deleted'),
                    'due_at' => $loan->due_at?->toIso8601String(),
                ],
                $user->isPustakawan() ? route('loans.index') : route('loans.mine'),
            );
        }

        // Kembali ke riwayat milik anggota, tapi pustakawan yang memperpanjang
        // dari halaman manajemen tetap mendarat di sana.
        return $this->backWithStatus(__('messages.loan_renewed', [
            'days' => (int) config('perpustakaan.loan.duration_days'),
        ]));
    }

    /**
     * Tandai denda sudah dibayar (pencatatan kas meja sirkulasi).
     *
     * Tidak ada alur pembayaran online di PRD — denda dibayar tunai/transfer,
     * lalu pustakawan menekan tombol ini supaya status "belum lunas" hilang
     * dari daftar.
     */
    public function payFine(Request $request, Loan $loan): RedirectResponse
    {
        if (! $loan->hasUnpaidFine()) {
            return back()->with('error', __('messages.fine_not_payable'));
        }

        $loan->update(['fine_paid_at' => now()]);

        $this->notifySelf(
            $request->user(),
            'fine_paid',
            [
                'subject' => $loan->book?->title ?? __('loans.book_deleted'),
                'fine' => $loan->fine,
            ],
            route('loans.index'),
        );

        return $this->backWithStatus(__('messages.fine_paid'));
    }

    protected function completeReturn(Loan $loan): bool
    {
        return DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();

            if (! $lockedLoan->isActive()) {
                return false;
            }

            $lockedLoan->update([
                'returned_at' => now(),
                'status' => Loan::STATUS_RETURNED,
                /*
                 * Denda di-snapshot di sini, saat momen pengembalian.
                 * `liveFine()` pada saat status masih aktif menghitung dari
                 * `due_at` sampai `now()` — yang kebetulan adalah detik
                 * pengembalian ini. Setelah baris ini, tampilan memakai
                 * kolom `fine`, jadi tarif yang berubah nanti tidak
                 * mengubah denda yang sudah pernah ditagih.
                 */
                'fine' => $lockedLoan->liveFine(),
            ]);

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

    /**
     * Hapus catatan peminjaman (koreksi administratif pustakawan).
     */
    public function destroy(Request $request, Loan $loan): RedirectResponse
    {
        // Penerima notifikasi ditentukan SEBELUM barisnya dihapus, supaya
        // alurnya tidak bergantung pada relasi yang barisnya sudah tidak ada.
        $borrower = $loan->user;
        $bookTitle = $loan->book?->title ?? __('loans.book_deleted');

        DB::transaction(function () use ($loan) {
            $lockedLoan = Loan::query()->whereKey($loan->getKey())->lockForUpdate()->firstOrFail();
            $book = Book::query()->whereKey($lockedLoan->book_id)->lockForUpdate()->first();

            // Kalau peminjaman aktif dihapus, buku harus kembali tersedia.
            if (! $lockedLoan->isReturned() && $book !== null) {
                $copy = $lockedLoan->book_copy_id
                    ? BookCopy::query()->whereKey($lockedLoan->book_copy_id)->lockForUpdate()->first()
                    : $book->copies()->where('status', BookCopy::STATUS_BORROWED)
                        ->lockForUpdate()->first();
                $copy?->update(['status' => BookCopy::STATUS_AVAILABLE]);

                if ($book->status !== Book::STATUS_INACTIVE) {
                    $book->update([
                        'status' => $book->availableCopies()->exists()
                            ? Book::STATUS_AVAILABLE
                            : Book::STATUS_BORROWED,
                    ]);
                }
            }

            $lockedLoan->delete();
        });

        /*
         * Peminjaman yang dihapus bukan lagi urusan staf — dia yang
         * menekan tombolnya — tapi bagi anggota itu riwayatnya hilang.
         * Tanpa kabar ini, dia baru mengetahuinya pada kunjungan berikutnya
         * dan akan mengira datanya rusak.
         */
        $borrower?->notify(new LoanRejected($loan, LoanRejected::REASON_ADMIN_DELETED));

        // Jejak pelaku (pustakawan): anggota sudah dikabari "dibatalkan",
        // pencatat penghapusannya sendiri baru melihat pesan flash.
        $this->notifySelf(
            $request->user(),
            'loan_record_deleted',
            ['subject' => $bookTitle],
            route('loans.index'),
        );

        return $this->success('loans.index', __('messages.loan_deleted'));
    }

    /**
     * Kabari anggota bahwa peminjaman barunya tercatat, lalu seluruh
     * pustakawan tentang peminjaman yang keluar dari rak.
     *
     * Bentuk pemberitahuannya berbeda tergantung SIAPA yang mencatat:
     * staf menginput atas nama anggota -> "disetujui"; anggota meminjam
     * sendiri -> "berhasil dipinjam".
     *
     * @param  bool  $recordedByStaff  true saat dicatat lewat form staf
     */
    private function announceNewLoan(Loan $loan, ?User $actor, bool $recordedByStaff): void
    {
        $loan->user?->notify($recordedByStaff
            ? new LoanApproved($loan)
            : new LoanBorrowed($loan));

        $this->notifyLibrarians(new LoanCreated($loan), except: $actor);
    }

    /**
     * Pemberitahuan untuk meja sirkulasi.
     *
     * @param  ?User  $except  pustakawan yang sedang menjalankan aksi ini —
     *                         notifikasi adalah daftar pekerjaan, dan baris
     *                         yang baru saja dia tulis sendiri bukan pekerjaan
     *                         baru
     */
    private function notifyLibrarians(Notification $notification, ?User $except = null): void
    {
        User::query()
            ->pustakawan()
            ->when($except?->isPustakawan(), fn ($query) => $query->whereKeyNot($except->getKey()))
            ->get()
            ->each(fn (User $librarian) => $librarian->notify($notification));
    }

    /**
     * Riwayat peminjaman milik anggota yang sedang login (Task 10.11, PRD §9).
     *
     * Data diambil dari `$request->user()`, tidak pernah dari parameter URL.
     * Kalau id anggota diterima dari request, orang bisa mengetik id orang
     * lain di address bar dan melihat riwayat pinjamannya.
     */
    public function mine(Request $request): View
    {
        $user = $request->user();

        $this->markOverdue->handle();

        $loans = $user->loans()
            ->with(['book', 'bookCopy'])
            ->latest('borrowed_at')
            ->paginate(config('perpustakaan.pagination.per_page'))
            ->withQueryString();

        return view('loans.mine', [
            'loans' => $loans,
            'activeCount' => $user->activeLoans()->count(),
            'overdueCount' => $user->loans()->overdue()->count(),
            'returnedCount' => $user->loans()->where('status', Loan::STATUS_RETURNED)->count(),
        ]);
    }
}
