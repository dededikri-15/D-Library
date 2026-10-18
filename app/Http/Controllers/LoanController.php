<?php

namespace App\Http\Controllers;

use App\Actions\BorrowBook;
use App\Actions\CompleteReturn;
use App\Actions\MarkOverdueLoans;
use App\Actions\RenewLoan;
use App\Actions\RequestReturn;
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
        protected CompleteReturn $completeReturn,
        protected RequestReturn $requestReturn,
        protected RenewLoan $renewLoan,
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

        /*
         * Ringkasan untuk banner di atas tabel. Semua dihitung SETELAH
         * markOverdue, supaya angka "terlambat" langsung mencerminkan
         * kenyataan, bukan status basi dari scheduler yang belum jalan.
         *
         * `due_at` dibatasi `whereBetween` ke jendela beberapa hari ke
         * depan (config). Peminjaman yang sudah lewat tempo sengaja tidak
         * ikut dihitung di sini — yang telat sudah punya baris merahnya
         * sendiri, menggabungkannya hanya menyembunyikan urgensi.
         */
        $summary = [
            'active' => Loan::query()->active()->count(),
            'return_pending' => Loan::query()->active()->whereNotNull('return_requested_at')->count(),
            'overdue' => Loan::query()->overdue()->count(),
            'due_soon' => Loan::query()
                ->where('status', Loan::STATUS_BORROWED)
                ->whereBetween('due_at', [
                    now()->startOfDay(),
                    now()->addDays((int) config('perpustakaan.loan.due_soon_days'))->endOfDay(),
                ])
                ->count(),
            'due_soon_days' => (int) config('perpustakaan.loan.due_soon_days'),
        ];

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
            'summary' => $summary,
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
        Gate::authorize('returnLoan', $loan);

        if (! $this->completeReturn->handle($loan)) {
            return $this->backWithStatus(__('messages.book_already_returned'));
        }

        /*
         * `CompleteReturn` menulis barisnya lewat instance KUNCI yang
         * berbeda di dalam transaksi, jadi `$loan` yang ini masih membawa
         * nilai lama — `returned_at`-nya masih null. Refresh dulu, kalau
         * tidak notifikasi anggota dikirim dengan tanggal pengembalian
         * kosong.
         */
        $loan->refresh();
        $loan->user?->refresh();
        $loan->user?->notify(new LoanReturned($loan, $request->user()?->name));

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

        /*
         * Pengembalian terlambat memberi kabar berbeda dari yang tepat waktu:
         * anggota perlu tahu batas akhir sanksinya, bukan sekadar "berhasil".
         * Angka 72 di pesan diambil dari config, jadi kalau durasi sanksi
         * berubah nanti teksnya tidak akan berbohong.
         */
        if ($loan->isLateReturn() && $loan->user?->isSuspended()) {
            return $this->success('loans.index', __('messages.book_returned_suspended', [
                'hours' => (int) config('perpustakaan.loan.suspension_hours'),
                'until' => $loan->user->suspensionEndsAt()
                    ?->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
                    ->translatedFormat('d M Y H:i'),
            ]));
        }

        return $this->success('loans.index', __('messages.book_returned'));
    }

    public function requestReturn(Request $request, Loan $loan): RedirectResponse
    {
        // Kepemilikan dicek lewat policy: pengajuan hanya boleh atas
        // pinjaman sendiri, tanpa tergantung middleware tertentu.
        Gate::authorize('requestReturn', $loan);

        // Logika atomiknya di RequestReturn; controller hanya mengurus
        // pemberitahuan dan pesan sesuai hasilnya.
        $result = $this->requestReturn->handle($loan);

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

        // Anggota hanya boleh memperpanjang pinjamannya sendiri; pustakawan
        // boleh memperpanjang siapa pun. Aturannya di LoanPolicy — URL route
        // bisa ditebak orang, jadi otorisasi dicek di server, bukan
        // disembunyikan di view.
        Gate::authorize('renew', $loan);

        // Logika atomiknya di RenewLoan — aturan kelayakan tetap satu
        // (`Loan::canRenew()`), otorisasi siapa yang boleh tetap di sini.
        $result = $this->renewLoan->handle($loan);

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
     * Hapus catatan peminjaman (koreksi administratif pustakawan).
     */
    public function destroy(Request $request, Loan $loan): RedirectResponse
    {
        Gate::authorize('delete', $loan);

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
        $borrower?->notify(new LoanRejected($loan, LoanRejected::REASON_ADMIN_DELETED, $request->user()?->name));

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
            ? new LoanApproved($loan, $actor?->name)
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
