@extends('layouts.app')

@section('title', 'Edit Kategori - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Edit Kategori</h1>
    <p class="mt-1 text-sm text-secondary">{{ $category->name }}</p>

    <form method="POST" action="{{ route('categories.update', $category) }}" data-submit-once
          class="card mt-6 max-w-xl space-y-5 p-6">
        @csrf
        @method('PUT')

        <x-form.input name="name" label="Nama kategori" required :value="$category->name" />
        <x-form.input name="slug" label="Slug" :value="$category->slug" />
        <x-form.textarea name="description" label="Deskripsi" rows="3" :value="$category->description" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="btn btn-primary">Simpan perubahan</button>
            <a href="{{ route('categories.index') }}" class="btn btn-secondary">Batal</a>
        </div>
    </form>

    <form method="POST" action="{{ route('categories.destroy', $category) }}" class="mt-4"
          data-confirm="Hapus kategori ini? Buku yang memakainya harus dipindah dulu."
          data-confirm-title="Hapus kategori">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-danger">Hapus kategori</button>
    </form>
@endsection
