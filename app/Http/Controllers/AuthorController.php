<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesUploads;
use App\Http\Controllers\Concerns\RespondsWithFlash;
use App\Http\Requests\AuthorRequest;
use App\Models\Author;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthorController extends Controller
{
    use HandlesUploads;
    use RespondsWithFlash;

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
        $this->deleteUpload($author->photo, static::photoDisk());

        $author->delete();

        return $this->success('authors.index', 'Penulis berhasil dihapus.');
    }
}
