<?php

namespace App\Http\Controllers;

use App\Actions\MarkOverdueLoans;
use App\Models\Book;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnggotaDashboardController extends Controller
{
    public function __construct(protected MarkOverdueLoans $markOverdueLoans)
    {
        //
    }

    /**
     * Dasbor untuk anggota: ringkasan aktivitas miliknya sendiri.
     */
    public function index(Request $request): View
    {
        $this->markOverdueLoans->handle();

        $user = $request->user();

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

        return view('anggota.dashboard', [
            'activeLoans' => $user->activeLoans()->with('book')->get(),
            'activeLoanCount' => $user->activeLoans()->count(),
            'overdueCount' => $user->loans()->overdue()->count(),
            'recentLoans' => $user->loans()->with('book')->latest('borrowed_at')->limit(5)->get(),
            'favoriteCount' => $user->favorites()->count(),
            'readingHistory' => $user->readingHistories()->with('book')->latest('last_read_at')->take(5)->get(),
            'recommendations' => $recommendations,
        ]);
    }
}
