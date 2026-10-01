@extends('layouts.app')

@section('title', __('master.add_entity', ['entity' => ucfirst(__('master.author'))]).' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('master.add_entity', ['entity' => ucfirst(__('master.author'))]) }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('master.author_description') }}</p>

    <form method="POST" action="{{ route('authors.store') }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" :label="__('master.author_name')" required :value="$author->name" />

        <x-form.textarea name="biography" :label="__('master.biography')" rows="4" :value="$author->biography" />

        <x-form.file name="photo" :label="__('master.photo')" accept="image/jpeg,image/png,image/webp"
                     :mimes="config('perpustakaan.uploads.cover_mimes')"
                     :maxKb="config('perpustakaan.uploads.cover_max_kb')" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">{{ __('master.save') }}</button>
            <a href="{{ route('authors.index') }}" class="btn btn-secondary">{{ __('master.cancel') }}</a>
        </div>
    </form>
@endsection
