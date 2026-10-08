<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Book;
use App\Models\Category;
use App\Support\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CategoryController extends Controller
{
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
        $category = Category::create($request->validated());

        $this->notifySelf(
            $request->user(),
            'category_created',
            ['subject' => $category->name],
            route('categories.index'),
        );

        return $this->success('categories.index', __('messages.category_created'));
    }

    public function edit(Category $category): View
    {
        // `books_count` dipakai view untuk menonaktifkan tombol hapus, supaya
        // user tahu sebelum menekan, bukan setelah ditolak.
        $category->loadCount('books');

        return view('categories.edit', ['category' => $category]);
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        if (collect($category->getChanges())->except('updated_at')->isNotEmpty()) {
            $this->notifySelf(
                $request->user(),
                'category_updated',
                ['subject' => $category->name],
                route('categories.index'),
            );
        }

        return $this->success('categories.index', __('messages.category_updated'));
    }

    public function destroy(Request $request, Category $category): RedirectResponse
    {
        $response = $this->destroyMasterData($category, 'kategori', 'categories.index');

        $this->notifySelf(
            $request->user(),
            'category_deleted',
            ['subject' => $category->name],
            route('categories.index'),
        );

        return $response;
    }

    public function storeQuick(Request $request): JsonResponse
    {
        // Validasi inline tetap perlu `attributes` sendiri: tanpa itu pesan
        // errornya menyebut field mentah "name", bukan "nama kategori".
        $validated = $request->validate(
            [
                'name' => [
                    'required', 'string', 'max:100',
                    Rule::unique('categories', 'name'),
                ],
            ],
            attributes: ['name' => 'nama kategori'],
        );

        $category = Category::create([
            'name' => $validated['name'],
            'slug' => Category::slugFor($validated['name']),
        ]);

        $this->notifySelf(
            $request->user(),
            'category_created',
            ['subject' => $category->name],
            route('categories.index'),
        );

        return Json::response([
            'id' => $category->id,
            'name' => $category->name,
        ]);
    }
}
