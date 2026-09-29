<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Http\Requests\CategoryRequest;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    use RespondsWithFlash;

    public function index(Request $request): View
    {
        return $this->masterIndex($request, Category::class, 'categories.index', 'categories');
    }

    /**
     * Halaman kategori untuk pengunjung (Task 8.7). CRUD-nya tetap milik
     * staff di method-method di atas; yang di sini hanya daftar beserta
     * jumlah buku, yang dipakai sebagai bahan filter katalog.
     */
    public function publicIndex(): View
    {
        // Filter jumlah buku dilakukan di PHP, bukan `having('books_count')`.
        // withCount menghasilkan subquery, dan HAVING di atasnya tidak
        // portabel: SQLite menolaknya dengan "HAVING clause on a non-aggregate
        // query", sementara kuerinya sendiri tetap sama persis di kedua DB.
        $categories = Category::query()
            ->withCount(['books' => fn ($query) => $query->where('status', '!=', Book::STATUS_INACTIVE)])
            ->orderBy('name')
            ->get()
            ->filter(fn ($category) => $category->books_count > 0)
            ->values();

        return view('categories.public', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('categories.create', ['category' => new Category]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return $this->success('categories.index', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return $this->success('categories.index', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return $this->success('categories.index', 'Kategori berhasil dihapus.');
    }
}
