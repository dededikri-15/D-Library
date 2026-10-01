@extends('layouts.app')

@section('title', __('book_form.add_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('book_form.add_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('book_form.add_description') }}</p>

    <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf

        <x-form.input name="title" :label="__('book_form.title')" required :value="$book->title" />
        <x-form.input name="isbn" :label="__('book_form.isbn')" required :value="$book->isbn" />

        <x-form.textarea name="description" :label="__('book_form.description')" rows="4" :value="$book->description" />

        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.input name="publication_year" :label="__('book_form.publication_year')" type="number" required
                          :value="$book->publication_year" />
            <x-form.input name="pages" :label="__('book_form.pages')" type="number" :value="$book->pages" />
        </div>

        <x-form.input name="initial_copies" :label="__('book_form.copies')" type="number" required min="1" max="500"
                      :value="old('initial_copies', 1)" />

        <div class="grid gap-5 sm:grid-cols-3">
            <div>
                <x-form.select name="category_id" :label="__('book_form.category')" required :emptyLabel="__('book_form.choose_category')"
                               :options="$categories->pluck('name', 'id')->all()"
                               data-quick-add data-quick-type="category" data-quick-url="{{ route('categories.quick') }}" />
            </div>
            <div>
                <x-form.select name="author_id" :label="__('book_form.author')" required :emptyLabel="__('book_form.choose_author')"
                               :options="$authors->pluck('name', 'id')->all()"
                               data-quick-add data-quick-type="author" data-quick-url="{{ route('authors.quick') }}" />
            </div>
            <div>
                <x-form.select name="publisher_id" :label="__('book_form.publisher')" required :emptyLabel="__('book_form.choose_publisher')"
                               :options="$publishers->pluck('name', 'id')->all()"
                               data-quick-add data-quick-type="publisher" data-quick-url="{{ route('publishers.quick') }}" />
            </div>
        </div>

        <x-form.select name="status" :label="__('book_form.status')" required :allowEmpty="false"
                       :options="App\Models\Book::statusOptions()"
                       :value="App\Models\Book::STATUS_AVAILABLE" />

        {{-- Cover publik, PDF privat. Keduanya opsional. --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <x-form.file name="cover" :label="__('book_form.cover')" accept="image/jpeg,image/png,image/webp"
                         :mimes="config('perpustakaan.uploads.cover_mimes')"
                         :maxKb="config('perpustakaan.uploads.cover_max_kb')" />

            <x-form.file name="file" :label="__('book_form.digital_file')" accept="application/pdf"
                         :mimes="config('perpustakaan.uploads.book_file_mimes')"
                         :maxKb="config('perpustakaan.uploads.book_file_max_kb')" />
        </div>

        <p class="text-xs text-secondary">
            {{ __('book_form.pdf_note') }}
        </p>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('book_form.save') }}</button>
            <a href="{{ route('books.index') }}" class="btn btn-secondary">{{ __('book_form.cancel') }}</a>
        </div>
    </form>

    {{--
        Modal quick-add (kategori/penulis/penerbit) harus DI LUAR form utama.

        HTML tidak boleh punya <form> di dalam <form>: browser membuang tag
        <form> kedua beserta atributnya, lalu input `name` yang `required` di
        dalam modal ikut dimiliki form utama. Karena modalnya tertutup
        (`display: none`), validasi browser menolak mengirim form utama dengan
        "An invalid form control with name='name' is not focusable" — tombol
        "Simpan buku" jadi tidak bereaksi sama sekali.
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
@endsection
