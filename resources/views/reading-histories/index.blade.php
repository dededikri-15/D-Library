@extends('layouts.app')

@section('title', __('member.reading_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('member.reading_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">{{ __('member.reading_count', ['count' => number_format($histories->total(), 0, ',', '.')]) }}</p>

    @if ($histories->isEmpty())
        <x-empty-state class="mt-6"
                       :title="__('member.no_reading')"
                       :description="__('member.reading_hint')">
            <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">{{ __('member.explore_catalog') }}</a>
        </x-empty-state>
    @else
        <div class="table-wrap mt-6">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{ __('loans.book') }}</th>
                        <th class="w-32">{{ __('member.last_page') }}</th>
                        <th class="w-44">{{ __('member.last_read') }}</th>
                        <th class="w-56 text-right">{{ __('member.action') }}</th>
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
                                            {{ __('member.continue_reading') }}
                                        </a>
                                    @endif

                                    <form method="POST" action="{{ route('reading-histories.destroy', $history) }}"
                                          data-confirm="{{ __('member.delete_reading_confirmation', ['title' => $history->book->title]) }}"
                                          data-confirm-title="{{ __('member.delete_history') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm">{{ __('member.delete') }}</button>
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
