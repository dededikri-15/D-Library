@extends('layouts.app')

@section('title', 'Tambah Penulis - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Tambah Penulis</h1>
    <p class="mt-1 text-sm text-secondary">Penulis ditautkan ke buku lewat form buku.</p>

    <form method="POST" action="{{ route('authors.store') }}" enctype="multipart/form-data" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" label="Nama penulis" required :value="$author->name" />

        <x-form.textarea name="biography" label="Biografi" rows="4" :value="$author->biography" />

        <x-form.file name="photo" label="Foto" accept="image/jpeg,image/png,image/webp"
                     :mimes="config('perpustakaan.uploads.cover_mimes')"
                     :maxKb="config('perpustakaan.uploads.cover_max_kb')" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('authors.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
