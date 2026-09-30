@extends('layouts.app')

@section('title', 'Edit Penulis - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Edit Penulis</h1>
    <p class="mt-1 text-sm text-secondary">{{ $author->name }}</p>

    <form method="POST" action="{{ route('authors.update', $author) }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" label="Nama penulis" required :value="$author->name" />

        <x-form.textarea name="biography" label="Biografi" rows="4" :value="$author->biography" />

        <x-form.file name="photo" label="Ganti foto" accept="image/jpeg,image/png,image/webp"
                     :mimes="config('perpustakaan.uploads.cover_mimes')"
                     :maxKb="config('perpustakaan.uploads.cover_max_kb')"
                     :current="$author->photo"
                     :currentUrl="$author->photo ? Storage::url($author->photo) : null"
                     removeName="remove_photo" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <a href="{{ route('authors.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>

    @include('partials.master-danger-delete', [
        'label' => 'penulis',
        'name' => $author->name,
        'action' => route('authors.destroy', $author),
        'booksCount' => $author->books_count,
    ])
@endsection
