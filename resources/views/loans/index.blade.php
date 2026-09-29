@extends('layouts.app')

@section('title', 'Peminjaman - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Data Peminjaman</h1>
    <p class="mt-1 text-sm text-secondary">Catat peminjaman baru dan lacak pengembalian buku.</p>

    <form method="POST" action="{{ route('loans.store') }}" class="card mt-6 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4"
          data-submit-once>
        @csrf

        <x-form.select name="user_id" label="Anggota" required emptyLabel="Pilih anggota"
                       :options="$members->pluck('name', 'id')->all()" />

        <x-form.select name="book_id" label="Buku" required emptyLabel="Pilih buku tersedia"
                       :options="$availableBooks->pluck('title', 'id')->all()" />

        <x-form.input name="borrowed_at" label="Tanggal pinjam" type="date" required
                      :value="now(config('perpustakaan.display_timezone'))->toDateString()" />

        <div class="flex items-end">
            <button type="submit" class="btn btn-primary w-full">Catat peminjaman</button>
        </div>

        <p class="text-label text-secondary sm:col-span-2 lg:col-span-4">
            Jatuh tempo dihitung otomatis {{ config('perpustakaan.loan.duration_days') }} hari dari tanggal pinjam.
            Buku yang sedang dipinjam tidak muncul di daftar di atas.
        </p>
    </form>

    <form method="GET" class="mt-6 flex max-w-xs items-end gap-2">
        <div class="flex-1">
            <label for="status" class="field-label">Filter status</label>
            <select id="status" name="status" class="field-input">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary shrink-0">Terapkan</button>
    </form>

    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>Anggota</th>
                    <th>Buku</th>
                    <th>Pinjam</th>
                    <th>Jatuh tempo</th>
                    <th>Status</th>
                    <th class="text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-medium text-primary">{{ $loan->user?->name ?? '-' }}</td>
                        <td class="text-secondary">{{ $loan->book?->title ?? '-' }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->due_at)?->format('d M Y') }}</td>
                        <td>
                            <x-status-badge :status="$loan->isOverdue() ? 'overdue' : $loan->status" />
                            @if ($loan->hasReturnRequest() && $loan->isActive())
                                <span class="mt-1 block text-label text-borrowed">Menunggu konfirmasi</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($loan->isActive())
                                    <form method="POST" action="{{ route('loans.return', $loan) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm text-available hover:bg-available/5">
                                            {{ $loan->hasReturnRequest() ? 'Konfirmasi pengembalian' : 'Kembalikan' }}
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('loans.destroy', $loan) }}"
                                      data-confirm="Hapus data peminjaman ini?"
                                      data-confirm-title="Hapus data peminjaman">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-0">
                            <x-empty-state class="border-0"
                                           title="Belum ada data peminjaman"
                                           description="Catat peminjaman pertama lewat formulir di atas." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
