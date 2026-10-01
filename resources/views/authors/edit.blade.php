@extends('layouts.app')

@section('title', __('master.edit').' '.ucfirst(__('master.author')).' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('master.edit') }} {{ ucfirst(__('master.author')) }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ $author->name }}</p>

    <form method="POST" action="{{ route('authors.update', $author) }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" :label="__('master.author_name')" required :value="$author->name" />

        <x-form.textarea name="biography" :label="__('master.biography')" rows="4" :value="$author->biography" />

        <x-form.file name="photo" :label="__('master.replace_photo')" accept="image/jpeg,image/png,image/webp"
                     :mimes="config('perpustakaan.uploads.cover_mimes')"
                     :maxKb="config('perpustakaan.uploads.cover_max_kb')"
                     :current="$author->photo"
                     :currentUrl="$author->photo ? Storage::url($author->photo) : null"
                     removeName="remove_photo" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('master.save_changes') }}</button>
            <a href="{{ route('authors.index') }}" class="btn btn-secondary">{{ __('master.cancel') }}</a>
        </div>
    </form>

    @include('partials.master-danger-delete', [
        'label' => __('master.author'),
        'name' => $author->name,
        'action' => route('authors.destroy', $author),
        'booksCount' => $author->books_count,
    ])
@endsection
