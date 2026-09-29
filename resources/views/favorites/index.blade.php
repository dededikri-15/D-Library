@extends('layouts.app')

@section('title', 'Favorit - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Buku Favorit</h1>
    <p class="mt-1 text-sm text-secondary">{{ number_format($favorites->total(), 0, ',', '.') }} buku tersimpan.</p>

    @if ($favorites->isEmpty())
        <x-empty-state class="mt-6"
                       title="Belum ada buku favorit"
                       description="Buka halaman buku lalu tekan Tambah ke favorit untuk menyimpannya di sini.">
            <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">Jelajahi katalog</a>
        </x-empty-state>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($favorites as $favorite)
                @php $book = $favorite->book; @endphp

                <article class="card flex h-full flex-col overflow-hidden">
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($book->category)
                                <span class="inline-flex w-fit rounded bg-tertiary/10 px-2 py-0.5 text-label font-medium text-tertiary">
                                    {{ $book->category->name }}
                                </span>
                            @endif
                            <x-status-badge :status="$book->status" />
                        </div>

                        <h2 class="mt-2 line-clamp-2 font-semibold text-primary">
                            <a href="{{ route('books.show', $book) }}" class="transition-colors hover:text-tertiary">
                                {{ $book->title }}
                            </a>
                        </h2>

                        <p class="mt-1 text-sm text-secondary">{{ $book->author?->name ?? 'Penulis tidak diketahui' }}</p>
                        <p class="text-sm text-secondary">Disimpan {{ $favorite->created_at->diffForHumans() }}</p>

                        <div class="mt-auto flex flex-wrap gap-2 pt-4">
                            <a href="{{ route('books.show', $book) }}" class="btn btn-secondary btn-sm">Lihat detail</a>

                            <form method="POST" action="{{ route('favorites.destroy', $book) }}"
                                  data-confirm="Hapus {{ $book->title }} dari favorit?"
                                  data-confirm-title="Hapus dari favorit">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $favorites->links() }}</div>
    @endif
@endsection
