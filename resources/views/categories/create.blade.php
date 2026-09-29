@extends('layouts.app')

@section('title', 'Tambah Kategori - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Tambah Kategori</h1>
    <p class="mt-1 text-sm text-secondary">Kategori mengelompokkan buku di katalog.</p>

    <form method="POST" action="{{ route('categories.store') }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf

        <x-form.input name="name" label="Nama kategori" required :value="$category->name" />

        <x-form.input name="slug" label="Slug" :value="$category->slug"
                      hint="Kosongkan untuk dibuat otomatis dari nama." />

        <x-form.textarea name="description" label="Deskripsi" rows="3" :value="$category->description" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>
@endsection
