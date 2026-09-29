@extends('layouts.app')

@section('title', 'Dasbor Anggota - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Halo, {{ auth()->user()->name }}</h1>
    <p class="mt-1 text-sm text-secondary">Ringkasan aktivitas Anda di D-Library.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-3">
        @foreach ([
            'Peminjaman Aktif' => [$activeLoanCount, 'text-primary'],
            'Terlambat' => [$overdueCount, 'text-overdue'],
            'Favorit' => [$favoriteCount, 'text-primary'],
        ] as $label => [$value, $tone])
            <div class="card p-5">
                <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $label }}</p>
                <p class="mt-2 text-3xl font-semibold {{ $tone }}">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="card mt-8 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-primary">Buku yang sedang dipinjam</h2>
            <a href="{{ route('books.index') }}" class="text-sm font-medium text-tertiary hover:underline">
                Cari buku &rarr;
            </a>
        </div>

        @forelse ($activeLoans as $loan)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                <a href="{{ route('books.show', $loan->book) }}"
                   class="font-medium text-primary transition-colors hover:text-tertiary">
                    {{ $loan->book->title }}
                </a>
                <div class="flex items-center gap-3">
                    @if ($loan->isOverdue())
                        <x-status-badge :status="'overdue'" />
                    @else
                        <x-status-badge :status="$loan->status" />
                    @endif
                    <span class="text-sm text-secondary">Jatuh tempo {{ $loan->displayDate($loan->due_at)?->format('d M Y') }}</span>
                </div>
            </div>
        @empty
            <x-empty-state class="mt-4 border-0"
                           title="Belum ada buku yang dipinjam"
                           description="Telusuri katalog untuk menemukan buku yang ingin Anda baca.">
                <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">Jelajahi katalog</a>
            </x-empty-state>
        @endforelse
    </div>

    <div class="card mt-4 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-primary">Riwayat peminjaman</h2>
            <a href="{{ route('loans.mine') }}" class="text-sm font-medium text-tertiary hover:underline">
                Lihat semua &rarr;
            </a>
        </div>

        @forelse ($recentLoans as $loan)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                <div class="min-w-0">
                    <a href="{{ route('books.show', $loan->book) }}"
                       class="font-medium text-primary transition-colors hover:text-tertiary">
                        {{ $loan->book->title }}
                    </a>
                    <p class="text-sm text-secondary">Dipinjam {{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</p>
                </div>
                <x-status-badge :status="$loan->status" />
            </div>
        @empty
            <p class="mt-4 text-sm text-secondary">Belum ada riwayat peminjaman.</p>
        @endforelse
    </div>

    <div class="card mt-4 p-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-primary">Riwayat membaca terakhir</h2>
            <a href="{{ route('reading-histories.index') }}" class="text-sm font-medium text-tertiary hover:underline">
                Lihat semua &rarr;
            </a>
        </div>

        @forelse ($readingHistory as $history)
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                <div class="min-w-0">
                    <a href="{{ route('books.show', $history->book) }}"
                       class="font-medium text-primary transition-colors hover:text-tertiary">
                        {{ $history->book->title }}
                    </a>
                    <p class="text-sm text-secondary">Halaman {{ $history->last_page }}</p>
                </div>

                {{-- Task 11.7: langsung buka di halaman yang tersimpan. --}}
                @if ($history->book->hasFile() && $history->last_page > 0)
                    <a href="{{ route('books.read', ['book' => $history->book, 'page' => $history->last_page]) }}"
                       class="btn btn-secondary btn-sm">Lanjut baca</a>
                @endif
            </div>
        @empty
            <x-empty-state class="mt-4 border-0"
                           title="Belum ada riwayat membaca"
                           description="Buka buku digital dan riwayat halaman akan tercatat di sini." />
        @endforelse
    </div>

    @if ($recommendations->isNotEmpty())
        <div class="mt-8">
            <h2 class="font-semibold text-primary">Rekomendasi untuk Anda</h2>
            <p class="mt-1 text-sm text-secondary">Berdasarkan kategori yang sering Anda baca.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($recommendations as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </div>
    @endif
@endsection
