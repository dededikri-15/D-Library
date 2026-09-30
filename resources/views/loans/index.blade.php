@extends('layouts.app')

@section('title', 'Peminjaman - ' . config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">Data Peminjaman</h1>
    {{-- <p class="mt-1 text-sm text-secondary">Lacak pengembalian buku.</p> --}}

    {{--
        Filter `active=1` datang dari kartu "Peminjaman Aktif" di dasbor, jadi
        filter status biasa tidak bisa menampilkannya: "aktif" itu status
        "dipinjam" + "terlambat" sekaligus, bukan satu status. Karena itu tidak
        ada checkbox untuk mengaktifkannya — kalau ada, orang akan mengira
        "aktif" itu status keempat di dropdown ini.

        Filter itu muncul sebagai catatan kecil di bawah form, yang sekaligus
        satu-satunya cara membuangnya lagi. Tanpa catatan itu, orang yang
        menekan "Terapkan" justru ikut menghapus filternya, karena form-nya
        tidak pernah mengirim `active`.
    --}}
    <form method="GET" class="mt-6 flex max-w-xs items-end gap-2">
        <div class="flex-1">
            <label for="status" class="field-label">Filter status</label>
            <select id="status" name="status" class="field-input">
                <option value="">Semua status</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ App\Models\Loan::statusOptions()[$status] ?? $status }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary shrink-0">Terapkan</button>
    </form>

    @if ($onlyActive)
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-secondary">
            <span class="badge badge-borrowed">Aktif</span>
            Menampilkan peminjaman yang dipinjam dan terlambat saja.
            <a href="{{ route('loans.index') }}" class="link-accent">Tampilkan semua</a>
        </p>
    @endif

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
                                           description="Belum ada peminjaman yang tercatat." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
