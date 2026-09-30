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
            <div>
                <x-form.select name="category_id" label="Kategori" required emptyLabel="Pilih kategori"
                               :options="$categories->pluck('name', 'id')->all()" :value="$book->category_id"
                               data-quick-add data-quick-type="category" data-quick-url="{{ route('categories.quick') }}" />
            </div>
            <div>
                <x-form.select name="author_id" label="Penulis" required emptyLabel="Pilih penulis"
                               :options="$authors->pluck('name', 'id')->all()" :value="$book->author_id"
                               data-quick-add data-quick-type="author" data-quick-url="{{ route('authors.quick') }}" />
            </div>
            <div>
                <x-form.select name="publisher_id" label="Penerbit" required emptyLabel="Pilih penerbit"
                               :options="$publishers->pluck('name', 'id')->all()" :value="$book->publisher_id"
                               data-quick-add data-quick-type="publisher" data-quick-url="{{ route('publishers.quick') }}" />
            </div>
        </div>

        <x-form.select name="status" label="Status" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()" :value="$book->status" />

        {{-- Cover publik, PDF privat. Keduanya opsional. --}}
        <div class="grid gap-5 lg:grid-cols-2">
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

    {{--
        Modal quick-add (kategori/penulis/penerbit) harus DI LUAR form utama.

        HTML tidak boleh punya <form> di dalam <form>: browser membaca tag
        <form> kedua di dalam form pertama sebagai "sampah" dan membuangnya
        beserta seluruh atributnya (action, data-quick-form, dan lain-lain).
        Akibatnya input di dalam modal ikut dimiliki form utama, dan karena
        modalnya tertutup (dialog tanpa `open` = `display: none`) input `name`
        yang `required` itu tidak bisa difokuskan. Saat tombol "Simpan
        perubahan" diklik, validasi browser menolak mengirim dan hanya menulis
        "An invalid form control with name='name' is not focusable" di console
        — dari sisi user tombolnya terlihat tidak bisa diklik sama sekali.

        Dipisah juga berarti form quick-add benar-benar form sendiri, jadi
        validasi required-nya tidak pernah ikut-ikutan memblokir form buku.
    --}}
    <x-modal id="modal-kategori" title="Tambah Kategori Baru">
        <form method="POST" action="{{ route('categories.quick') }}" data-quick-form data-quick-target="category_id">
            @csrf
            <x-form.input id="quick-category-name" name="name" label="Nama kategori" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penulis" title="Tambah Penulis Baru">
        <form method="POST" action="{{ route('authors.quick') }}" data-quick-form data-quick-target="author_id">
            @csrf
            <x-form.input id="quick-author-name" name="name" label="Nama penulis" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penerbit" title="Tambah Penerbit Baru">
        <form method="POST" action="{{ route('publishers.quick') }}" data-quick-form data-quick-target="publisher_id">
            @csrf
            <x-form.input id="quick-publisher-name" name="name" label="Nama penerbit" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </x-modal>

    {{-- Form hapus harus di luar form utama: HTML tidak boleh punya <form> bersarang. --}}
    <form method="POST" action="{{ route('books.destroy', $book) }}" class="mt-4"
          data-confirm="Hapus buku ini beserta data terkait?"
          data-confirm-title="Hapus buku">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Hapus buku</button>
    </form>
@endsection
