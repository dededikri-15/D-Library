@extends('layouts.app')

@section('title', 'Edit Buku - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Edit Buku</h1>
    <p class="mt-1 text-sm text-secondary">{{ $book->title }}</p>

    <form method="POST" action="{{ route('books.update', $book) }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf
        @method('PUT')

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
                           :options="$categories->pluck('name', 'id')->all()" :value="$book->category_id" />
            <x-form.select name="author_id" label="Penulis" required emptyLabel="Pilih penulis"
                           :options="$authors->pluck('name', 'id')->all()" :value="$book->author_id" />
            <x-form.select name="publisher_id" label="Penerbit" required emptyLabel="Pilih penerbit"
                           :options="$publishers->pluck('name', 'id')->all()" :value="$book->publisher_id" />
        </div>

        <x-form.select name="status" label="Status" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()" :value="$book->status" />

        {{-- Cover publik, PDF privat. Keduanya opsional. --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.file name="cover" label="Ganti cover" accept="image/jpeg,image/png,image/webp"
                         :mimes="config('perpustakaan.uploads.cover_mimes')"
                         :maxKb="config('perpustakaan.uploads.cover_max_kb')"
                         :current="$book->cover"
                         :currentUrl="$book->cover ? Storage::url($book->cover) : null"
                         removeName="remove_cover" />

            <x-form.file name="file" label="Ganti file digital (PDF)" accept="application/pdf"
                         :mimes="config('perpustakaan.uploads.book_file_mimes')"
                         :maxKb="config('perpustakaan.uploads.book_file_max_kb')"
                         :current="$book->file" removeName="remove_file" />
        </div>

        <p class="text-xs text-secondary">
            Mengunggah berkas baru akan otomatis menggantikan berkas lama. Centang "Hapus" untuk mengosongkan tanpa mengunggah pengganti.
        </p>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>

    {{-- Form hapus harus di luar form utama: HTML tidak boleh punya <form> bersarang. --}}
    <form method="POST" action="{{ route('books.destroy', $book) }}" class="mt-4"
          data-confirm="Hapus buku ini beserta data terkait?"
          data-confirm-title="Hapus buku">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Hapus buku</button>
    </form>
@endsection
