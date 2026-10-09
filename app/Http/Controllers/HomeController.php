<?php

namespace App\Http\Controllers;

use App\Actions\MarkOverdueLoans;
use App\Models\Book;
use App\Models\Category;
use App\Models\Loan;
use App\Models\ReadingHistory;
use App\Models\User;
use App\Support\LoanReport;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct(
        protected MarkOverdueLoans $markOverdueLoans,
        protected LoanReport $loanReport,
    ) {
        //
    }

    /**
     * Halaman depan (Beranda). Gabungan beranda pengunjung + dasbor anggota + dasbor pustakawan.
     */
    public function index(): View
    {
        $this->markOverdueLoans->handle();

        $user = Auth::user();

        // Data umum (untuk semua role, termasuk tamu)
        $totalBooksActive = Book::where('status', '!=', Book::STATUS_INACTIVE)->count();
        $totalCategories = Category::count();
        $totalMembers = User::where('role', User::ROLE_ANGGOTA)->count();
        $currentlyBorrowed = Book::where('status', Book::STATUS_BORROWED)->count();

        $categories = Category::query()
            ->withCount('books')
            ->orderBy('name')
            ->limit(8)
            ->get();

        $latestBooks = Book::query()
            ->with(['category', 'author'])
            ->where('status', '!=', Book::STATUS_INACTIVE)
            ->latest('created_at')
            ->limit(4)
            ->get();

        $popularBooks = Book::query()
            ->with(['category', 'author'])
            ->where('status', '!=', Book::STATUS_INACTIVE)
            ->withCount(['loans as loans_count' => fn ($query) => $query->whereNotNull('returned_at')])
            ->orderByDesc('loans_count')
            ->orderByDesc('created_at')
            ->limit(4)
            ->get();

        $data = [
            'user' => $user,
            'totalBooks' => $totalBooksActive,
            'totalCategories' => $totalCategories,
            'totalMembers' => $totalMembers,
            'categories' => $categories,
            'latestBooks' => $latestBooks,
            'popularBooks' => $popularBooks,
            'currentlyBorrowed' => $currentlyBorrowed,
            'isStaff' => $user?->isStaff() ?? false,
            'isMember' => $user?->isMember() ?? false,
        ];

        // Data khusus Pustakawan
        if ($user && $user->isStaff()) {
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

            $data = array_merge($data, [
                'totalBooksStaff' => (int) $bookStats->total_books,
                'totalBooks' => (int) $bookStats->total_books,
                'totalUsers' => (int) $userStats->total_users,
                'totalMembersStaff' => (int) $userStats->total_members,
                'totalMembers' => (int) $userStats->total_members,
                'totalLoans' => (int) $loanStats->total_loans,
                'activeLoansStaff' => (int) $loanStats->active_loans,
                'activeLoans' => (int) $loanStats->active_loans,
                'overdueLoansStaff' => (int) $loanStats->overdue_loans,
                'overdueLoans' => (int) $loanStats->overdue_loans,
                'bookStatus' => [
                    Book::STATUS_AVAILABLE => (int) $bookStats->available_books,
                    Book::STATUS_BORROWED => (int) $bookStats->borrowed_books,
                    Book::STATUS_INACTIVE => (int) $bookStats->inactive_books,
                ],
                'recentLoans' => Loan::with(['user', 'book'])->latest('borrowed_at')->limit(5)->get(),
                'recentBooks' => Book::with(['category', 'author'])->latest()->limit(5)->get(),
                'recentActivity' => ReadingHistory::with(['user', 'book'])->latest('last_read_at')->limit(5)->get(),

                // Data grafik laporan. Sengaja di cabang staf: anggota dan
                // tamu tidak pernah melihat grafik ini, jadi tiga query
                // tambahan tidak boleh dibayar oleh mereka.
                'loansPerMonth' => $this->loanReport->perMonth(),
                'topBorrowedBooks' => $this->loanReport->topBooks(),
                'topBorrowers' => $this->loanReport->topBorrowers(),
            ]);
        }

        // Data khusus Anggota
        if ($user && $user->isMember()) {
            $favoriteCategories = $user->readingHistories()
                ->join('books', 'reading_histories.book_id', '=', 'books.id')
                ->selectRaw('books.category_id, COUNT(*) as count')
                ->whereNotNull('books.category_id')
                ->groupBy('books.category_id')
                ->orderByDesc('count')
                ->limit(3)
                ->pluck('category_id');

            $recommendations = Book::query()
                ->with(['category', 'author'])
                ->where('status', '!=', Book::STATUS_INACTIVE)
                ->whereIn('category_id', $favoriteCategories)
                ->whereNotIn('id', $user->readingHistories()->pluck('book_id'))
                ->latest()
                ->limit(4)
                ->get();

            $data = array_merge($data, [
                'activeLoans' => $user->activeLoans()->with('book')->get(),
                'activeLoanCount' => $user->activeLoans()->count(),
                'overdueCount' => $user->loans()->overdue()->count(),
                'recentLoansMember' => $user->loans()->with('book')->latest('borrowed_at')->limit(5)->get(),
                'favoriteCount' => $user->favorites()->count(),
                'readingHistory' => $user->readingHistories()->with('book')->latest('last_read_at')->take(5)->get(),
                'recommendations' => $recommendations,
            ]);
        }

        return view('home', $data);
    }
}
