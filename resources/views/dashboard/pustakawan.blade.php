@extends('layouts.app')

@section('title', 'Dasbor Pustakawan - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Dasbor Pustakawan</h1>
    <p class="mt-1 text-sm text-secondary">
        Kelola koleksi, pengguna, dan layanan peminjaman perpustakaan.
    </p>

    <div class="mt-6">
        <x-dashboard.stats :total-books="$totalBooks" :total-users="$totalUsers" :total-loans="$totalLoans" :total-members="$totalMembers" :active-loans="$activeLoans"
            :overdue-loans="$overdueLoans" :book-status="$bookStatus">
            <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">Kelola pengguna</a>
            <a href="{{ route('loans.index') }}" class="btn btn-secondary btn-sm">Kelola peminjaman</a>
            <a href="{{ route('books.create') }}" class="btn btn-secondary btn-sm">Tambah buku</a>
            <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">Daftar buku</a>
        </x-dashboard.stats>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="card p-5">
            <h2 class="font-semibold text-primary">Peminjaman Terbaru</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentLoans as $loan)
                    <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <div class="min-w-0">
                            <a href="{{ route('books.show', $loan->book) }}"
                                class="block truncate font-medium text-primary hover:text-tertiary">
                                {{ $loan->book->title }}
                            </a>
                            <p class="text-sm text-secondary">{{ $loan->user->name }}</p>
                        </div>
                        <x-status-badge :status="$loan->status" />
                    </div>
                @empty
                    <p class="text-sm text-secondary">Belum ada peminjaman.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-primary">Buku Terbaru</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentBooks as $book)
                    <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <div class="min-w-0">
                            <a href="{{ route('books.show', $book) }}"
                                class="block truncate font-medium text-primary hover:text-tertiary">
                                {{ $book->title }}
                            </a>
                            <p class="text-sm text-secondary">{{ $book->author?->name ?? '-' }}</p>
                        </div>
                        <x-status-badge :status="$book->status" />
                    </div>
                @empty
                    <p class="text-sm text-secondary">Belum ada buku.</p>
                @endforelse
            </div>
        </div>

        <div class="card p-5">
            <h2 class="font-semibold text-primary">Aktivitas Terbaru</h2>
            <div class="mt-4 space-y-3">
                @forelse ($recentActivity as $activity)
                    <div class="flex items-center justify-between gap-3 border-b border-secondary/10 pb-3 last:border-0">
                        <div class="min-w-0">
                            <p class="truncate font-medium text-primary">{{ $activity->user->name }}</p>
                            <p class="text-sm text-secondary">membaca {{ $activity->book->title }}</p>
                        </div>
                        <span
                            class="shrink-0 text-sm text-secondary">{{ $activity->last_read_at?->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-sm text-secondary">Belum ada aktivitas.</p>
                @endforelse
            </div>
        </div>
    </div>

@endsection
