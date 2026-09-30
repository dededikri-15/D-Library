<?php

namespace App\Http\Controllers;

use App\Actions\BorrowBook;
use App\Actions\MarkOverdueLoans;
use App\Exceptions\LoanNotPossibleException;
use App\Http\Requests\LoanRequest;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            ->with(['user', 'book'])
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
            $this->borrowBook->handle($member, $request->integer('book_id'), $request->date('borrowed_at'));
        } catch (LoanNotPossibleException $e) {
            // Pesannya ditempel ke field `book_id`, bukan flashed sebagai
            // error umum, supaya muncul tepat di bawah dropdown buku.
            return back()->withErrors(['book_id' => $e->getMessage()])->withInput();
        }

        return $this->success('loans.index', 'Peminjaman berhasil dicatat.');
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
            $this->borrowBook->handle($request->user(), $book->getKey());
        } catch (LoanNotPossibleException $e) {
            // Selalu kembali ke detail buku. `back()` tidak boleh dipakai
            // di sini: kalau pesan ini muncul dari sumber lain, referer
            // bisa saja tidak cocok dan user mendarat di halaman yang
            // tidak ada hubungannya dengan peminjaman.
            return redirect()
                ->route('books.show', $book)
                ->with('status', $e->getMessage());
        }

        return $this->success(
            'books.show',
            'Buku berhasil dipinjam. Jangan lupa dikembalikan sebelum tanggal jatuh tempo.',
            ['book' => $book]
        );
    }

    /**
     * Pengembalian buku oleh pustakawan (Task 10.7).
     */
    public function returnBook(Loan $loan): RedirectResponse
    {
        if (! $this->completeReturn($loan)) {
            return $this->backWithStatus('Buku ini sudah pernah dikembalikan.');
        }

        return $this->success('loans.index', 'Buku berhasil dikembalikan.');
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

        return match ($result) {
            'returned' => $this->backWithStatus('Pinjaman ini sudah tidak aktif.'),
            'pending' => $this->backWithStatus('Permintaan pengembalianmu sedang menunggu konfirmasi pustakawan.'),
            default => $this->backWithStatus('Permintaan pengembalian dikirim ke pustakawan.'),
        };
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
            ]);

            $lockedLoan->book()->update(['status' => Book::STATUS_AVAILABLE]);

            return true;
        });
    }

    /**
     * Hapus catatan peminjaman (koreksi administratif pustakawan).
     */
    public function destroy(Loan $loan): RedirectResponse
    {
        $book = $loan->book;

        DB::transaction(function () use ($loan, $book) {
            // Kalau peminjaman aktif dihapus, buku harus kembali tersedia.
            if (! $loan->isReturned() && $book !== null) {
                $book->update(['status' => Book::STATUS_AVAILABLE]);
            }

            $loan->delete();
        });

        return $this->success('loans.index', 'Data peminjaman dihapus.');
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
            ->with('book')
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
