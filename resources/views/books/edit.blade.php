@extends('layouts.app')

@section('title', __('book_form.edit_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('book_form.edit_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $book->title }}</p>

    <form method="POST" action="{{ route('books.update', $book) }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-3xl space-y-5 p-6">
        @csrf
        @method('PUT')

        @include('partials.books.fields-meta')


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

        @include('partials.books.fields-relations', ['statusValue' => $book->status])

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

        @include('partials.books.quick-add-modals')

    {{-- Form hapus harus di luar form utama: HTML tidak boleh punya <form> bersarang. --}}
    <form method="POST" action="{{ route('books.destroy', $book) }}" class="mt-4"
          data-confirm="{{ __('book_form.delete_confirmation') }}"
          data-confirm-title="{{ __('book_form.delete') }}">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">{{ __('book_form.delete') }}</button>
    </form>
@endsection
