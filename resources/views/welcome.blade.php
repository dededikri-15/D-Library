@extends('layouts.app')

@section('title', config('app.name'))

@section('content')
    {{--
        Hero. Latar bergradasi diambil dari utility `.bg-hero` yang memakai token
        `--hero-*`, jadi warnanya otomatis menyesuaikan tema: indigo pucat di
        terang, indigo pekat di gelap.
    --}}
    <section class="bg-hero relative overflow-hidden rounded-xl border border-hairline">
        {{-- Cahaya bergradasi di belakang teks. --}}
        <span class="pointer-events-none absolute -top-24 -right-16 h-72 w-72 rounded-full blur-3xl"
              style="background-color: var(--hero-glow)" aria-hidden="true"></span>
        <span class="pointer-events-none absolute -bottom-32 -left-20 h-72 w-72 rounded-full blur-3xl"
              style="background-color: var(--hero-glow)" aria-hidden="true"></span>

        <div class="relative px-6 py-16 sm:px-10 sm:py-20 lg:px-16 lg:py-24">
            <p class="section-eyebrow animate-fade-in">D-Library</p>

            <h1 class="mt-5 max-w-3xl text-display font-extrabold tracking-tight text-balance text-primary animate-fade-up">
    Akses ribuan buku, <span class="text-tertiary">dalam satu klik.</span>
</h1>

<p class="mt-5 max-w-xl text-lg leading-relaxed text-secondary animate-fade-up [animation-delay:80ms]">
    Nikmati pengalaman membaca buku digital yang cepat, rapi, dan responsif langsung melalui perangkat Anda kapan saja.
</p>

            <div class="mt-9 flex flex-wrap items-center gap-3 animate-fade-up [animation-delay:160ms]">
                @auth
                    <a href="{{ route(auth()->user()->isStaff() ? 'dashboard' : 'anggota.dashboard') }}"
                       class="btn btn-primary btn-lg">
                        Buka dasbor
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('books.index') }}" class="btn btn-secondary btn-lg">Jelajahi katalog</a>
                @else
                    <a href="{{ route('books.index') }}" class="btn btn-blue-primary btn-lg">
                        Jelajahi katalog
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                    <a href="{{ route('login') }}" class="btn btn-blue-outline btn-lg">Masuk</a>
                    @if (config('perpustakaan.registration.enabled', true))
                        <a href="{{ route('register') }}" class="btn btn-blue-outline btn-lg">Daftar anggota</a>
                    @endif
                @endauth
            </div>
        </div>
    </section>

    {{-- Angka koleksi --}}
    <section class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" aria-label="Ringkasan koleksi">
        @foreach ([
            ['label' => 'Buku tersedia', 'value' => $totalBooks, 'tone' => 'available', 'text' => 'text-available'],
            ['label' => 'Sedang dipinjam', 'value' => $currentlyBorrowed, 'tone' => 'borrowed', 'text' => 'text-borrowed'],
            ['label' => 'Kategori', 'value' => $totalCategories, 'tone' => 'tertiary', 'text' => 'text-tertiary'],
            ['label' => 'Anggota terdaftar', 'value' => $totalMembers, 'tone' => 'primary', 'text' => 'text-primary'],
        ] as $stat)
            @php
                // Ikon ditulis sebagai SVG, bukan emoji: emoji tampil berbeda
                // tiap sistem operasi dan tidak bisa diberi warna status.
                $iconPath = match ($stat['tone']) {
                    'available' => 'M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25',
                    'borrowed' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                    'tertiary' => 'M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z',
                    default => 'M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z',
                };
            @endphp

            <div class="stat-card">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold tabular-nums {{ $stat['text'] }}">
                            {{ number_format($stat['value'], 0, ',', '.') }}
                        </p>
                    </div>

                    <span @class([
                        'stat-icon shrink-0',
                        'bg-available/10 text-available dark:bg-available/15' => $stat['tone'] === 'available',
                        'bg-borrowed/10 text-borrowed dark:bg-borrowed/15' => $stat['tone'] === 'borrowed',
                        'bg-tertiary/10 text-tertiary dark:bg-tertiary/15' => $stat['tone'] === 'tertiary',
                        'bg-secondary/10 text-secondary dark:bg-secondary/15' => $stat['tone'] === 'primary',
                    ])>
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
                        </svg>
                    </span>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Buku terbaru --}}
    <section class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="section-eyebrow">Koleksi</p>
                <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">Buku Terbaru</h2>
                <p class="mt-1.5 text-sm text-secondary">Koleksi yang baru ditambahkan ke katalog.</p>
            </div>
            <a href="{{ route('books.index') }}" class="link-accent text-sm">
                Lihat semua &rarr;
            </a>
        </div>

        @if ($latestBooks->isEmpty())
            <x-empty-state class="mt-6"
                           title="Katalog masih kosong"
                           description="Buku yang ditambahkan pustakawan akan muncul di sini." />
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Buku populer --}}
    @if ($popularBooks->isNotEmpty() && $popularBooks->first()->loans_count > 0)
        <section class="mt-14">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-eyebrow">Favorit Pembaca</p>
                    <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">Buku Populer</h2>
                    <p class="mt-1.5 text-sm text-secondary">Paling sering dipinjam anggota.</p>
                </div>
            </div>

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($popularBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Kategori --}}
    @if ($categories->isNotEmpty())
        <section class="mt-14">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <p class="section-eyebrow">Topik</p>
                    <h2 class="mt-2 text-h1 font-bold tracking-tight text-primary">Jelajahi Kategori</h2>
                    <p class="mt-1.5 text-sm text-secondary">Telusuri koleksi berdasarkan topik.</p>
                </div>
                <a href="{{ route('categories.public') }}" class="link-accent text-sm">
                    Semua kategori &rarr;
                </a>
            </div>

            <div class="mt-6 flex flex-wrap gap-2.5">
                @foreach ($categories as $category)
                    <a href="{{ route('books.index', ['category' => $category->slug]) }}" class="pill">
                        <svg class="h-4 w-4 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z"/>
                        </svg>
                        {{ $category->name }}
                        <span class="rounded-full bg-secondary/10 px-2 py-0.5 text-label tabular-nums text-secondary dark:bg-secondary/20">
                            {{ $category->books_count }}
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Ajakan bertindak --}}
    @guest
        @if (config('perpustakaan.registration.enabled', true))
            <section class="mt-14 overflow-hidden rounded-xl border border-tertiary/25 bg-tertiary/5 px-6 py-14 text-center">
                <h2 class="text-h1 font-bold tracking-tight text-primary">Mulai membaca hari ini</h2>
                <p class="mx-auto mt-3 max-w-lg text-secondary">
                    Daftar sebagai anggota untuk menyimpan buku ke favorit, memantau riwayat baca,
                    dan meminjam koleksi digital kami.
                </p>
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg mt-8">Daftar sekarang</a>
            </section>
        @endif
    @endguest
@endsection
