<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use App\Support\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    /** Foto penulis sama perlakuannya dengan cover buku: gambar publik. */
    protected static function photoDisk(): string
    {
        return (string) config('perpustakaan.uploads.cover_disk');
    }

    public function index(Request $request): View
    {
        return $this->masterIndex($request, Author::class, 'authors.index', 'authors');
    }

    public function create(): View
    {
        return view('authors.create', ['author' => new Author]);
    }

    public function store(AuthorRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        $upload = $request->file('photo');
        $data['photo'] = $upload
            ? $this->storeUpload($upload, static::photoDisk(), 'authors')
            : null;

        Author::create($data);

        return $this->success('authors.index', 'Penulis berhasil ditambahkan.');
    }

    public function edit(Author $author): View
    {
        // `books_count` dipakai view untuk menonaktifkan tombol hapus, supaya
        // user tahu sebelum menekan, bukan setelah ditolak.
        $author->loadCount('books');

        return view('authors.edit', ['author' => $author]);
    }

    public function update(AuthorRequest $request, Author $author): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'remove_photo']);

        $upload = $request->file('photo');

        if ($upload) {
            // Upload baru menggantikan foto lama, lalu berkas lama dihapus.
            $data['photo'] = $this->storeUpload($upload, static::photoDisk(), 'authors', $author->photo);
        } elseif ($request->boolean('remove_photo')) {
            $this->deleteUpload($author->photo, static::photoDisk());
            $data['photo'] = null;
        }

        $author->update($data);

        return $this->success('authors.index', 'Penulis berhasil diperbarui.');
    }

    public function destroy(Author $author): RedirectResponse
    {
        // Foto ikut terhapus, tapi hanya kalau penulisnya benar-benar dihapus.
        return $this->destroyMasterData(
            $author,
            'penulis',
            'authors.index',
            fn () => $this->deleteUpload($author->photo, static::photoDisk()),
        );
    }

    public function storeQuick(Request $request): JsonResponse
    {
        // Validasi inline tetap perlu `attributes` sendiri: tanpa itu pesan
        // errornya menyebut field mentah "name", bukan "nama penulis".
        $validated = $request->validate(
            [
                'name' => ['required', 'string', 'max:150'],
            ],
            attributes: ['name' => 'nama penulis'],
        );

        $author = Author::create(['name' => $validated['name']]);

        return Json::response([
            'id' => $author->id,
            'name' => $author->name,
        ]);
    }
}
