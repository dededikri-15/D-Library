@extends('layouts.app')

@section('title', __('loans.history_title').' - '.config('app.name'))

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="text-h1 font-semibold text-primary">{{ __('loans.history_title') }}</h1>
            <p class="mt-1 text-sm text-secondary">{{ __('loans.history_description') }}
            </p>
        </div>
        <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">{{ __('loans.search_books') }}</a>
    </div>

    {{-- Ringkasan singkat. Tiga angka ini jadi kartu, bukan teks, karena
         dari sanalah orang cepat tahu "berapa yang masih harus saya
         kembalikan". --}}
    <div class="mt-6 grid gap-4 sm:grid-cols-3">
        @foreach ([['label' => __('loans.active_count'), 'value' => $activeCount, 'tone' => ''], ['label' => __('loans.overdue_count'), 'value' => $overdueCount, 'tone' => $overdueCount > 0 ? 'text-overdue' : ''], ['label' => __('loans.returned_count'), 'value' => $returnedCount, 'tone' => '']] as $stat)
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
                    <th>{{ __('loans.book') }}</th>
                    <th>{{ __('loans.copy') }}</th>
                    <th>{{ __('loans.borrowed_at') }}</th>
                    <th>{{ __('loans.due_at') }}</th>
                    <th>{{ __('loans.returned_at') }}</th>
                    <th>{{ __('loans.status') }}</th>
                    <th class="text-right">{{ __('loans.action') }}</th>
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
                                <span class="text-secondary">{{ __('loans.book_deleted') }}</span>
                            @endif
                        </td>
                        <td class="font-mono text-secondary">{{ $loan->bookCopy?->inventory_code ?? '-' }}</td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->borrowed_at)?->format('d M Y') }}</td>
                        <td class="text-secondary">
                            {{ $loan->displayDate($loan->due_at)?->format('d M Y') }}
                            @if ($loan->isOverdue())
                                <span class="block text-label text-overdue">
                                    {{ __('loans.late_since', ['date' => $loan->displayDate($loan->due_at)?->diffForHumans()]) }}
                                </span>
                            @endif
                        </td>
                        <td class="text-secondary">{{ $loan->displayDate($loan->returned_at)?->format('d M Y') ?? '-' }}
                        </td>
                        <td><x-status-badge :status="$loan->status" /></td>
                        <td>
                            <div class="flex items-center justify-end gap-2">
                                @if ($loan->isReturned())
                                    <span class="text-label text-secondary">{{ __('loans.done') }}</span>
                                @elseif ($loan->hasReturnRequest())
                                    <span class="text-label font-medium text-borrowed">{{ __('loans.waiting_librarian') }}</span>
                                @elseif ($loan->isActive() && $loan->book)
                                    <form method="POST" action="{{ route('loans.mine.request-return', $loan) }}"
                                        data-confirm="{{ __('loans.request_confirmation', ['title' => $loan->book->title]) }}"
                                        data-confirm-title="{{ __('loans.request_return') }}">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary btn-sm">{{ __('loans.request_return') }}</button>
                                    </form>
                                @elseif ($loan->book)
                                    <a href="{{ route('books.show', $loan->book) }}" class="btn btn-ghost btn-sm">
                                        {{ __('book.details') }}
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="p-0">
                            <x-empty-state class="border-0" :title="__('loans.empty_history')"
                                :description="__('loans.empty_history_description')">
                                <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">{{ __('loans.open_catalog') }}</a>
                            </x-empty-state>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $loans->links() }}</div>
@endsection
