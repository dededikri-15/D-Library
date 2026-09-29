@extends('layouts.app')

@section('title', 'Riwayat Peminjaman - ' . config('app.name'))

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h1 font-semibold text-primary">Riwayat Peminjaman</h1>
            <p class="mt-1 text-sm text-secondary">Semua buku yang pernah kamu pinjam, beserta tanggal-pinjam dan statusnya.
            </p>
        </div>
        <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">Cari buku lagi</a>
    </div>

    {{-- Ringkasan singkat. Tiga angka ini jadi kartu, bukan teks, karena
         dari sanalah orang cepat tahu "berapa yang masih harus saya
         kembalikan". --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([['label' => 'Sedang dipinjam', 'value' => $activeCount, 'tone' => ''], ['label' => 'Terlambat', 'value' => $overdueCount, 'tone' => $overdueCount > 0 ? 'text-overdue' : ''], ['label' => 'Sudah dikembalikan', 'value' => $returnedCount, 'tone' => '']] as $stat)
            <div class="stat-card">
                <p class="text-label font-medium tracking-wide text-secondary uppercase">{{ $stat['label'] }}</p>
                <p class="mt-2 text-3xl font-bold tabular-nums text-primary {{ $stat['tone'] }}">
                    {{ number_format($stat['value'], 0, ',', '.') }}
                </p>
            </div>
        @endforeach
    </div>

    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>Buku</th>
                    <th>Dipinjam</th>
                    <th>Jatuh tempo</th>
                    <th>Dikembalikan</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-medium text-primary">
                            @if ($loan->book)
                                <a href="{{ route('books.show', $loan->book) }}"
                                    class="link-accent">{{ $loan->book->title }}</a>
                            @else
                                <span class="text-secondary">Buku sudah dihapus</span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</td>
                        <td class="text-secondary">
                            {{ $loan->displayDate($loan->due_at)?->format('d M Y') }}
                            @if ($loan->isOverdue())
                                <span class="block text-label text-overdue">
                                    lewat {{ $loan->displayDate($loan->due_at)?->diffForHumans() }}
                                </span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->returned_at)?->format('d M Y') ?? '-' }}
                        </td>
                        <td><x-status-badge :status="$loan->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($loan->isReturned())
                                    <span class="text-label text-secondary">Selesai</span>
                                @elseif ($loan->hasReturnRequest())
                                    <span class="text-label font-medium text-borrowed">Menunggu konfirmasi pustakawan</span>
                                @elseif ($loan->isActive() && $loan->book)
                                    <form method="POST" action="{{ route('loans.mine.request-return', $loan) }}"
                                        data-confirm="Ajukan pengembalian {{ $loan->book->title }} kepada pustakawan?"
                                        data-confirm-title="Ajukan pengembalian">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">Ajukan pengembalian</button>
                                    </form>
                                @elseif ($loan->book)
                                    <a href="{{ route('books.show', $loan->book) }}" class="btn btn-ghost btn-sm">
                                        Detail buku
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <x-empty-state class="border-0" title="Belum ada riwayat peminjaman"
                                description="Cari buku di katalog lalu tekan tombol Pinjam Buku.">
                                <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">Buka katalog</a>
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
