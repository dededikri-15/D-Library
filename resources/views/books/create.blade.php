@extends('layouts.app')

@section('title', 'Tambah Buku - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Tambah Buku</h1>
    <p class="mt-1 text-sm text-secondary">Isi detail buku lalu unggah cover dan file digital bila tersedia.</p>

    <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf

        <x-form.input name="title" label="Judul" required :value="$book->title" />
        <x-form.input name="isbn" label="ISBN" required :value="$book->isbn" />

        <x-form.textarea name="description" label="Deskripsi" rows="4" :value="$book->description" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="publication_year" label="Tahun terbit" type="number" required
                          :value="$book->publication_year" />
            <x-form.input name="pages" label="Jumlah halaman" type="number" :value="$book->pages" />
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <x-form.select name="category_id" label="Kategori" required emptyLabel="Pilih kategori"
                           :options="$categories->pluck('name', 'id')->all()" />
            <x-form.select name="author_id" label="Penulis" required emptyLabel="Pilih penulis"
                           :options="$authors->pluck('name', 'id')->all()" />
            <x-form.select name="publisher_id" label="Penerbit" required emptyLabel="Pilih penerbit"
                           :options="$publishers->pluck('name', 'id')->all()" />
        </div>

        <x-form.select name="status" label="Status" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()"
                       :value="App\Models\Book::STATUS_AVAILABLE" />

        {{-- Cover publik, PDF privat. Keduanya opsional. --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.file name="cover" label="Cover buku" accept="image/jpeg,image/png,image/webp"
                         :mimes="config('perpustakaan.uploads.cover_mimes')"
                         :maxKb="config('perpustakaan.uploads.cover_max_kb')" />

            <x-form.file name="file" label="File digital (PDF)" accept="application/pdf"
                         :mimes="config('perpustakaan.uploads.book_file_mimes')"
                         :maxKb="config('perpustakaan.uploads.book_file_max_kb')" />
        </div>

        <p class="text-xs text-secondary">
            File PDF disimpan di penyimpanan privat dan hanya bisa dibaca anggota yang sedang meminjam buku ini.
        </p>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan buku</button>
            <a href="{{ route('books.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
