@extends('layouts.app')

@section('title', __('book_form.add_title').' - '.config('app.name'))

@section('content')

    <h1 class="text-h1 font-semibold text-primary">{{ __('book_form.add_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('book_form.add_description') }}</p>

    <form method="POST" action="{{ route('books.store') }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf

        @include('partials.books.fields-meta')

        <x-form.input name="initial_copies" :label="__('book_form.copies')" type="number" required min="1" max="500"
                      :value="old('initial_copies', 1)" />

        @include('partials.books.fields-relations', ['statusValue' => App\Models\Book::STATUS_AVAILABLE])

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

        @include('partials.books.quick-add-modals')
@endsection
