@extends('layouts.app')

@section('title', 'Katalog Buku - ' . config('app.name'))

@section('content')
    {{-- Kepala halaman --}}
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="section-eyebrow">{{ $isManagement ? 'Manajemen koleksi' : 'Koleksi' }}</p>
            <h1 class="mt-2 text-h1 font-bold tracking-tight text-primary">
                {{ $isManagement ? 'Kelola Buku' : 'Katalog Buku' }}
            </h1>
            <p class="mt-1.5 text-secondary">
                {{ $isManagement ? 'Pilih buku untuk mengubah data atau menambahkan cover.' : 'Telusuri koleksi D-Library.' }}
            </p>
        </div>

        @if ($isManagement)
            <a href="{{ route('books.create') }}" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah buku
            </a>
        @endif
    </header>

    {{-- Form pencarian & filter --}}
    <form method="GET" action="{{ route('books.index') }}"
          class="card mt-7 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="q" class="field-label">Kata kunci</label>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-secondary"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
                {{--
                    Atribut `data-search-*` diaktifkan di sini saja, bukan
                    otomatis: endpoint pratinjau hanya ada untuk katalog buku.
                    Kotak pencarian di master data tetap form biasa, karena
                    endpoint JSON-nya belum dibuat dan lebih baik form biasa
                    daripada panel yang tidak pernah berisi apa-apa.
                --}}
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Cari judul, ISBN, penulis, kategori, atau penerbit"
                       class="field-input mt-0 pl-9"
                       data-search-input
                       data-search-url="{{ route('books.search') }}"
                       data-search-min="2"
                       role="combobox"
                       aria-expanded="false"
                       aria-haspopup="listbox"
                       aria-autocomplete="list"
                       aria-controls="search-results-list">

                {{-- Panel pratinjau. Kosong di HTML: isinya dirakit JS dari
                     respons JSON, jadi tidak perlu template HTML di server.

                     Tapi satu pengecualian: bentuk loading state TIDAK boleh
                     ditulis ulang sebagai string di app.js. `data-search-skeleton`
                     di bawah dirender Blade, lalu diklon JS saat request berjalan
                     (Task 14.8) — sumber markup-nya sama dengan komponen
                     loading state di halaman lain. --}}
                <div data-search-results class="search-panel" hidden></div>

                <template data-search-skeleton>
                    <x-loading-skeleton :rows="3" label="Memuat hasil pencarian..." />
                </template>
            </div>
            <p class="mt-1.5 text-label text-secondary">
                Satu kolom untuk semua: judul, ISBN, penulis, kategori, dan penerbit.
            </p>
        </div>

        <div>
            <label for="category" class="field-label">Kategori</label>
            <select id="category" name="category" class="field-input">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(($filters['category'] ?? null) === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="author" class="field-label">Penulis</label>
            <select id="author" name="author" class="field-input">
                <option value="">Semua penulis</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->name }}" @selected(($filters['author'] ?? null) === $author->name)>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="publisher" class="field-label">Penerbit</label>
            <select id="publisher" name="publisher" class="field-input">
                <option value="">Semua penerbit</option>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->name }}" @selected(($filters['publisher'] ?? null) === $publisher->name)>
                        {{ $publisher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status" class="field-label">Status</label>
            <select id="status" name="status" class="field-input">
                <option value="">Semua status</option>
                <option value="available" @selected(($filters['status'] ?? null) === 'available')>Tersedia</option>
                <option value="borrowed" @selected(($filters['status'] ?? null) === 'borrowed')>Dipinjam</option>
            </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="sort" class="field-label">Urutkan</label>
            <select id="sort" name="sort" class="field-input mt-1.5 lg:max-w-xs">
                <option value="latest" @selected($sort === 'latest')>Terbaru</option>
                <option value="title" @selected($sort === 'title')>Judul A-Z</option>
                <option value="oldest" @selected($sort === 'oldest')>Tahun terbit (terlama dulu)</option>
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
                Terapkan filter
            </button>
            <a href="{{ route('books.index') }}" class="btn btn-ghost">Reset</a>
        </div>
    </form>

    {{-- Ringkasan hasil --}}
    <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-secondary">
            Menampilkan <span class="font-semibold text-primary tabular-nums">{{ number_format($books->total(), 0, ',', '.') }}</span>
            buku ditemukan.
        </p>

        @if ($sort !== 'latest')
            <span class="badge badge-muted">
                Diurutkan: {{ ['latest' => 'Terbaru', 'title' => 'Judul A-Z', 'oldest' => 'Tahun terbit'][$sort] }}
            </span>
        @endif
    </div>

    @if ($books->isEmpty())
        <x-empty-state class="mt-6"
                       title="Buku tidak ditemukan"
                       description="Coba kata kunci lain, atau reset filter yang sedang aktif.">
            @if ($isManagement)
                <a href="{{ route('books.create') }}" class="btn btn-primary btn-sm">Tambah buku</a>
            @else
                <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">Reset filter</a>
            @endif
        </x-empty-state>
    @else
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($books as $book)
                <div class="flex min-w-0 flex-col gap-3">
                    <x-book-card :book="$book" />
                    <x-book-quick-view :book="$book" />
                    @if ($isManagement)
                        <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary btn-sm w-full">
                            Edit buku
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $books->links() }}
        </div>
    @endif
@endsection
