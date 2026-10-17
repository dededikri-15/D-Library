<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Author;
use App\Models\Category;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Collection;

/**
 * Opsi dropdown untuk form buku, dipakai `BookController::create()`,
 * `BookController::edit()`, dan `BookCatalogController::index()`.
 *
 * Ketiganya butuh daftar yang persis sama dan urutan yang persis sama,
 * supaya pilihan di form edit sama dengan yang tampil di form create. Kalau
 * salah satu ditulis ulang sendiri, cepat atau lambat urutannya berbeda
 * dan pilihan di dropdown terlihat tidak konsisten antar halaman.
 */
trait LoadsBookOptions
{
    /**
     * @return array{categories: Collection<int, Category>, authors: Collection<int, Author>, publishers: Collection<int, Publisher>}
     */
    protected function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('name')->get(),
            'authors' => Author::orderBy('name')->get(),
            'publishers' => Publisher::orderBy('name')->get(),
        ];
    }
}
