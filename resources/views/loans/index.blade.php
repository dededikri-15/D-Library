@extends('layouts.app')

@section('title', __('loans.management_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('loans.management_title') }}</h1>
    {{-- <p class="mt-1 text-sm text-secondary">Lacak pengembalian buku.</p> --}}

    @include('partials.loan-summary-banner', ['summary' => $summary])

    @include('partials.loan-status-filter', ['statuses' => $statuses, 'onlyActive' => $onlyActive])

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
