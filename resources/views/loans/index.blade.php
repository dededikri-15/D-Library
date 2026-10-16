@extends('layouts.app')

@section('title', __('loans.management_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('loans.management_title') }}</h1>
    {{-- <p class="mt-1 text-sm text-secondary">Lacak pengembalian buku.</p> --}}

    {{--
        Banner ringkasan: gambaran sekilas kondisi peminjaman sebelum tabel.
        Pustakawan tidak perlu menghitung baris kuning/merah satu per satu —
        angka yang paling butuh tindakan (terlambat, menunggu konfirmasi,
        jatuh tempo dekat) muncul paling atas. Tiap baris adalah tautan ke
        filter tabel, supaya angka dan daftar tidak pernah berbeda cerita.
    --}}
    <div class="mt-4 space-y-2">
        @if ($summary['overdue'] > 0)
            <a href="{{ route('loans.index', ['status' => 'overdue']) }}"
               class="flex items-center gap-3 rounded-lg border border-overdue/40 bg-overdue/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-overdue/15 dark:bg-overdue/15">
                <svg class="h-5 w-5 shrink-0 text-overdue" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                </svg>
                <span>{{ __('loans.summary_overdue', ['count' => $summary['overdue']]) }}</span>
            </a>
        @endif

        @if ($summary['return_pending'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-borrowed/40 bg-borrowed/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-borrowed/15 dark:bg-borrowed/15">
                <svg class="h-5 w-5 shrink-0 text-borrowed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3"/>
                </svg>
                <span>{{ __('loans.summary_return_pending', ['count' => $summary['return_pending']]) }}</span>
            </a>
        @endif

        @if ($summary['due_soon'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-borrowed/40 bg-borrowed/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-borrowed/15 dark:bg-borrowed/15">
                <svg class="h-5 w-5 shrink-0 text-borrowed" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ __('loans.summary_due_soon', ['count' => $summary['due_soon'], 'days' => $summary['due_soon_days']]) }}</span>
            </a>
        @endif

        @if ($summary['active'] > 0)
            <a href="{{ route('loans.index', ['active' => 1]) }}"
               class="flex items-center gap-3 rounded-lg border border-tertiary/40 bg-tertiary/10 px-4 py-3 text-sm text-primary transition-colors hover:bg-tertiary/15 dark:bg-tertiary/15">
                <svg class="h-5 w-5 shrink-0 text-tertiary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"/>
                </svg>
                <span>{{ __('loans.summary_active', ['count' => $summary['active']]) }}</span>
            </a>
        @endif

        @if ($summary['overdue'] === 0 && $summary['return_pending'] === 0 && $summary['due_soon'] === 0 && $summary['active'] === 0)
            <div class="flex items-center gap-3 rounded-lg border border-available/40 bg-available/10 px-4 py-3 text-sm text-primary dark:bg-available/15">
                <svg class="h-5 w-5 shrink-0 text-available" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                </svg>
                <span>{{ __('loans.summary_clear') }}</span>
            </div>
        @endif
    </div>

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
            <label for="status" class="field-label">{{ __('loans.filter_status') }}</label>
            <select id="status" name="status" class="field-input">
                <option value="">{{ __('loans.all_statuses') }}</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ App\Models\Loan::statusOptions()[$status] ?? $status }}
                    </option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn btn-secondary shrink-0">{{ __('loans.apply') }}</button>
    </form>

    @if ($onlyActive)
        <p class="mt-3 flex flex-wrap items-center gap-2 text-sm text-secondary">
            <span class="badge badge-borrowed">{{ __('loans.active') }}</span>
            {{ __('loans.active_description') }}
            <a href="{{ route('loans.index') }}" class="link-accent">{{ __('loans.show_all') }}</a>
        </p>
    @endif

    <div class="table-wrap mt-6">
        <table class="table">
            <thead>
                <tr>
                    <th>{{ __('loans.member') }}</th>
                    <th>{{ __('loans.book') }}</th>
                    <th>{{ __('loans.copy') }}</th>
                    <th>{{ __('loans.borrowed_at') }}</th>
                    <th>{{ __('loans.due_at') }}</th>
                    <th>{{ __('loans.status') }}</th>
                    <th class="text-right">{{ __('loans.action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($loans as $loan)
                    <tr>
                        <td class="font-medium text-primary">
                            {{ $loan->user?->name ?? '-' }}
                            {{-- Masa sanksi anggota tampil di sini, di baris
                                 peminjamannya, supaya pustakawan melihat
                                 larangan meminjam tepat saat menimbang
                                 tindak lanjut keterlambatan — bukan di halaman
                                 terpisah yang harus dia buka sendiri. --}}
                            @if ($loan->user?->isSuspended())
                                <span class="mt-1 block text-label text-overdue">
                                    {{ __('loans.member_suspended', [
                                        'until' => $loan->user->suspensionEndsAt()
                                            ?->setTimezone((string) config('perpustakaan.display_timezone', 'Asia/Jakarta'))
                                            ->translatedFormat('d M Y H:i'),
                                    ]) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $loan->book?->title ?? '-' }}</td>
                        <td class="font-mono text-secondary">{{ $loan->bookCopy?->inventory_code ?? '-' }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->due_at)?->format('d M Y') }}</td>
                        <td>
                            <x-status-badge :status="$loan->isOverdue() ? 'overdue' : $loan->status" />
                            {{-- Keterlambatan kini dinyatakan dalam HARI, bukan
                                 rupiah: tidak ada yang ditagih, tapi pustakawan
                                 tetap perlu tahu seberapa jauh telatnya. --}}
                            @if ($loan->isOverdue() && $loan->overdueDays() > 0)
                                <span class="mt-1 block text-label text-overdue">
                                    {{ __('loans.overdue_days', ['count' => $loan->overdueDays()]) }}
                                </span>
                            @endif
                            @if ($loan->hasReturnRequest() && $loan->isActive())
                                <span class="mt-1 block text-label text-borrowed">{{ __('loans.return_pending') }}</span>
                            @endif
                        </td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($loan->isActive())
                                    @if ($loan->canRenew())
                                        <form method="POST" action="{{ route('loans.renew', $loan) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-ghost btn-sm">{{ __('loans.renew') }}</button>
                                        </form>
                                    @endif
                                    <form method="POST" action="{{ route('loans.return', $loan) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-ghost btn-sm text-available hover:bg-available/5">
                                            {{ $loan->hasReturnRequest() ? __('loans.confirm_return') : __('loans.return_book') }}
                                        </button>
                                    </form>
                                @endif

                                <form method="POST" action="{{ route('loans.destroy', $loan) }}"
                                      data-confirm="{{ __('loans.delete_confirmation') }}"
                                      data-confirm-title="{{ __('loans.delete') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm">{{ __('loans.delete') }}</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-0">
                            <x-empty-state class="border-0"
                                           :title="__('loans.empty_loans')"
                                           :description="__('loans.empty_loans_description')" />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
