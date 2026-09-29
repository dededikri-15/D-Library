<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Halaman depan untuk pengunjung. Menampilkan sekilas jumlah koleksi,
     * kategori yang tersedia, dan buku yang baru masuk supaya pengunjung
     * tidak langsung mendarat di daftar kosong.
     */
    public function index(): View
    {
        return view('welcome', [
            'totalBooks' => Book::where('status', '!=', Book::STATUS_INACTIVE)->count(),
            'totalCategories' => Category::count(),
            'totalMembers' => User::where('role', User::ROLE_ANGGOTA)->count(),
            'categories' => Category::query()
                ->withCount('books')
                ->orderBy('name')
                ->limit(8)
                ->get(),
            'latestBooks' => Book::query()
                ->with(['category', 'author'])
                ->where('status', '!=', Book::STATUS_INACTIVE)
                ->latest('created_at')
                ->limit(4)
                ->get(),
            // "Populer" dihitung dari jumlah peminjaman yang sudah selesai,
            // bukan dari urutan acak.
            'popularBooks' => Book::query()
                ->with(['category', 'author'])
                ->where('status', '!=', Book::STATUS_INACTIVE)
                ->withCount(['loans as loans_count' => fn ($query) => $query->whereNotNull('returned_at')])
                ->orderByDesc('loans_count')
                ->orderByDesc('created_at')
                ->limit(4)
                ->get(),
            'currentlyBorrowed' => Book::where('status', Book::STATUS_BORROWED)->count(),
        ]);
    }
}
