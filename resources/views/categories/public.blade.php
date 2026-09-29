@extends('layouts.app')

@section('title', 'Kategori Buku - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Kategori Buku</h1>
    <p class="mt-1 text-sm text-secondary">
        Pilih kategori untuk menyaring katalog. Jumlah buku tidak menghitung buku yang tidak aktif.
    </p>

    @forelse ($categories as $category)
        <a href="{{ route('books.index', ['category' => $category->slug]) }}"
           class="card mt-4 flex items-center justify-between gap-4 p-5 transition-colors hover:border-tertiary/40">
            <div class="min-w-0">
                <h2 class="font-semibold text-primary">{{ $category->name }}</h2>
                @if ($category->description)
                    <p class="mt-1 line-clamp-2 text-sm text-secondary">{{ $category->description }}</p>
                @endif
            </div>

            <span class="badge shrink-0 bg-tertiary/10 text-tertiary">
                {{ $category->books_count }} buku
            </span>
        </a>
    @empty
        <x-empty-state class="mt-6"
                       title="Belum ada kategori"
                       description="Kategori akan tampil di sini setelah pustakawan menambahkannya." />
    @endforelse
@endsection
