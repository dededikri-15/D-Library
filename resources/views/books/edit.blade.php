@extends('layouts.app')

@section('title', __('book_form.edit_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('book_form.edit_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $book->title }}</p>

    <form method="POST" action="{{ route('books.update', $book) }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="title" :label="__('book_form.title')" required :value="$book->title" />
        <x-form.input name="isbn" :label="__('book_form.isbn')" required :value="$book->isbn" />

        <x-form.textarea name="description" :label="__('book_form.description')" rows="4" :value="$book->description" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="publication_year" :label="__('book_form.publication_year')" type="number" required
                          :value="$book->publication_year" />
            <x-form.input name="pages" :label="__('book_form.pages')" type="number" :value="$book->pages" />
        </div>

        <div class="space-y-3 rounded-md border border-hairline p-4">
            <p class="text-sm font-medium text-primary">{{ __('book_form.current_copies', ['count' => $copies->count()]) }}</p>
            <div class="max-h-40 space-y-2 overflow-y-auto">
                @foreach ($copies as $copy)
                    <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                        <span class="font-mono text-secondary">{{ $copy->inventory_code }}</span>
                        <x-status-badge :status="$copy->status" />
                    </div>
                @endforeach
            </div>
            <x-form.input name="add_copies" :label="__('book_form.add_copies')" type="number" min="0" max="500"
                          :value="old('add_copies', 0)" />
        </div>

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <x-form.select name="category_id" :label="__('book_form.category')" required :emptyLabel="__('book_form.choose_category')"
                               :options="$categories->pluck('name', 'id')->all()" :value="$book->category_id"
                               data-quick-add data-quick-type="category" data-quick-url="{{ route('categories.quick') }}" />
            </div>
            <div>
                <x-form.select name="author_id" :label="__('book_form.author')" required :emptyLabel="__('book_form.choose_author')"
                               :options="$authors->pluck('name', 'id')->all()" :value="$book->author_id"
                               data-quick-add data-quick-type="author" data-quick-url="{{ route('authors.quick') }}" />
            </div>
            <div>
                <x-form.select name="publisher_id" :label="__('book_form.publisher')" required :emptyLabel="__('book_form.choose_publisher')"
                               :options="$publishers->pluck('name', 'id')->all()" :value="$book->publisher_id"
                               data-quick-add data-quick-type="publisher" data-quick-url="{{ route('publishers.quick') }}" />
            </div>
        </div>

        <x-form.select name="status" :label="__('book_form.status')" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()" :value="$book->status" />

        {{-- Cover publik, PDF privat. Keduanya opsional. --}}
        <div class="grid gap-5 lg:grid-cols-2">
            <x-form.file name="cover" :label="__('book_form.replace_cover')" accept="image/jpeg,image/png,image/webp"
                         :mimes="config('perpustakaan.uploads.cover_mimes')"
                         :maxKb="config('perpustakaan.uploads.cover_max_kb')"
                         :current="$book->cover"
                         :currentUrl="$book->cover ? Storage::url($book->cover) : null"
                         removeName="remove_cover" />

            <x-form.file name="file" :label="__('book_form.replace_digital_file')" accept="application/pdf"
                         :mimes="config('perpustakaan.uploads.book_file_mimes')"
                         :maxKb="config('perpustakaan.uploads.book_file_max_kb')"
                         :current="$book->file" removeName="remove_file" />
        </div>

        <p class="text-xs text-secondary">
            {{ __('book_form.replace_note') }}
        </p>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('book_form.save_changes') }}</button>
            <a href="{{ route('books.show', $book) }}" class="btn btn-secondary">{{ __('book_form.cancel') }}</a>
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
    <x-modal id="modal-kategori" :title="__('book_form.quick_category')">
        <form method="POST" action="{{ route('categories.quick') }}" data-quick-form data-quick-target="category_id">
            @csrf
            <x-form.input id="quick-category-name" name="name" :label="__('book_form.category_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penulis" :title="__('book_form.quick_author')">
        <form method="POST" action="{{ route('authors.quick') }}" data-quick-form data-quick-target="author_id">
            @csrf
            <x-form.input id="quick-author-name" name="name" :label="__('book_form.author_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>

    <x-modal id="modal-penerbit" :title="__('book_form.quick_publisher')">
        <form method="POST" action="{{ route('publishers.quick') }}" data-quick-form data-quick-target="publisher_id">
            @csrf
            <x-form.input id="quick-publisher-name" name="name" :label="__('book_form.publisher_name')" required />
            <p data-quick-error class="mt-1 hidden text-label text-overdue" role="alert"></p>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('book_form.cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('book_form.quick_save') }}</button>
            </div>
        </form>
    </x-modal>

    {{-- Form hapus harus di luar form utama: HTML tidak boleh punya <form> bersarang. --}}
    <form method="POST" action="{{ route('books.destroy', $book) }}" class="mt-4"
          data-confirm="{{ __('book_form.delete_confirmation') }}"
          data-confirm-title="{{ __('book_form.delete') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">{{ __('book_form.delete') }}</button>
    </form>
@endsection
