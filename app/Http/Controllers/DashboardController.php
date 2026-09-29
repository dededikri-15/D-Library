<?php

namespace App\Http\Controllers;

use App\Actions\MarkOverdueLoans;
use App\Models\Book;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected MarkOverdueLoans $markOverdueLoans)
    {
        //
    }

    /** Dashboard staff menampilkan statistik koleksi dan layanan perpustakaan. */
    public function index(): View
    {
        $this->markOverdueLoans->handle();

        $bookStats = Book::query()
            ->selectRaw('COUNT(*) AS total_books')
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS available_books', [Book::STATUS_AVAILABLE])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS borrowed_books', [Book::STATUS_BORROWED])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS inactive_books', [Book::STATUS_INACTIVE])
            ->first();

        $userStats = User::query()
            ->selectRaw('COUNT(*) AS total_users')
            ->selectRaw('COUNT(CASE WHEN role = ? THEN 1 END) AS total_members', [User::ROLE_ANGGOTA])
            ->first();

        $loanStats = Loan::query()
            ->selectRaw('COUNT(*) AS total_loans')
            ->selectRaw('COUNT(CASE WHEN status IN (?, ?) THEN 1 END) AS active_loans', [
                Loan::STATUS_BORROWED,
                Loan::STATUS_OVERDUE,
            ])
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) AS overdue_loans', [Loan::STATUS_OVERDUE])
            ->first();

        return view('dashboard.pustakawan', [
            'totalBooks' => (int) $bookStats->total_books,
            'totalUsers' => (int) $userStats->total_users,
            'totalMembers' => (int) $userStats->total_members,
            'totalLoans' => (int) $loanStats->total_loans,
            'activeLoans' => (int) $loanStats->active_loans,
            'overdueLoans' => (int) $loanStats->overdue_loans,
            'bookStatus' => [
                Book::STATUS_AVAILABLE => (int) $bookStats->available_books,
                Book::STATUS_BORROWED => (int) $bookStats->borrowed_books,
                Book::STATUS_INACTIVE => (int) $bookStats->inactive_books,
            ],
            'recentLoans' => Loan::with(['user', 'book'])->latest('borrowed_at')->limit(5)->get(),
            'recentBooks' => Book::with(['category', 'author'])->latest()->limit(5)->get(),
            'recentActivity' => ReadingHistory::with(['user', 'book'])->latest('last_read_at')->limit(5)->get(),
        ]);
    }
}
