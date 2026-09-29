@extends('layouts.app')

@section('title', 'Riwayat Baca - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Riwayat Membaca</h1>
    <p class="mt-1 text-sm text-secondary">{{ number_format($histories->total(), 0, ',', '.') }} buku pernah dibaca.</p>

    @if ($histories->isEmpty())
        <x-empty-state class="mt-6"
                       title="Belum ada riwayat membaca"
                       description="Riwayat akan tersimpan otomatis saat Anda membuka buku digital.">
            <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">Jelajahi katalog</a>
        </x-empty-state>
    @else
        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>Buku</th>
                        <th class="w-32">Halaman terakhir</th>
                        <th class="w-44">Terakhir dibaca</th>
                        <th class="w-56 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($histories as $history)
                        <tr>
                            <td>
                                <a href="{{ route('books.show', $history->book) }}"
                                   class="font-medium text-primary transition-colors hover:text-tertiary">
                                    {{ $history->book->title }}
                                </a>
                                <p class="mt-0.5 text-secondary">{{ $history->book->author?->name ?? '-' }}</p>
                            </td>
                            <td class="tabular-nums text-secondary">{{ $history->last_page }}</td>
                            <td class="text-secondary">{{ $history->last_read_at?->diffForHumans() }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-2">
                                    {{-- "Lanjut membaca" (Task 11.7). Hanya kalau
                                         buku punya file digital dan peminjamannya
                                         masih aktif; kalau tidak, tautan ini
                                         akan berakhir di 403. --}}
                                    @if ($history->book->hasFile() && $history->last_page > 0)
                                        <a href="{{ route('books.read', ['book' => $history->book, 'page' => $history->last_page]) }}"
                                           class="btn btn-secondary btn-sm">
                                            Lanjut baca
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('reading-histories.destroy', $history) }}"
                                          data-confirm="Hapus riwayat baca {{ $history->book->title }}?"
                                          data-confirm-title="Hapus riwayat baca">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $histories->links() }}</div>
    @endif
@endsection
