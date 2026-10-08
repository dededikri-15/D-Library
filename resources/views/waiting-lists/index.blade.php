@extends('layouts.app')

@section('title', __('member.waiting_title').' - '.config('app.name'))

@section('content')
    <h1 class="text-h1 font-semibold text-primary">{{ __('member.waiting_title') }}</h1>
    <p class="mt-1 text-sm text-secondary">
        {{ __('member.waiting_count', [
            'count' => number_format($entries->total(), 0, ',', '.'),
            'max' => $queueLimit,
        ]) }}
    </p>

    @if ($entries->isEmpty())
        <x-empty-state class="mt-6"
                       :title="__('member.no_waiting')"
                       :description="__('member.waiting_hint')">
            <a href="{{ route('books.index') }}" class="btn btn-primary btn-sm">{{ __('member.explore_catalog') }}</a>
        </x-empty-state>
    @else
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($entries as $entry)
                @php $book = $entry->book; @endphp

                @if ($book === null)
                    @continue
                @endif

                <article class="card flex h-full flex-col overflow-hidden">
                    <div class="flex flex-1 flex-col p-4">
                        <div class="flex flex-wrap items-center gap-2">
                            @if ($book->category)
                                <span class="inline-flex w-fit rounded bg-tertiary/10 px-2 py-0.5 text-label font-medium text-tertiary">
                                    {{ $book->category->name }}
                                </span>
                            @endif
                            <x-status-badge :status="$book->status" />

                            @if ($entry->notified_at !== null)
                                {{-- Bukan warna status: indigo adalah aksen interaktif,
                                     jadi badge ini tidak menambah keluarga warna ketiga. --}}
                                <span class="inline-flex w-fit rounded bg-tertiary/10 px-2 py-0.5 text-label font-medium text-tertiary">
                                    {{ __('member.waiting_notified', ['date' => $entry->notified_at->diffForHumans()]) }}
                                </span>
                            @endif
                        </div>

                        <h2 class="mt-2 line-clamp-2 font-semibold text-primary">
                            <a href="{{ route('books.show', $book) }}" class="transition-colors hover:text-tertiary">
                                {{ $book->title }}
                            </a>
                        </h2>

                        <p class="mt-1 text-sm text-secondary">{{ $book->author?->name ?? __('member.unknown_author') }}</p>
                        <p class="text-sm text-secondary">{{ __('member.waiting_joined', ['date' => $entry->created_at->diffForHumans()]) }}</p>

                        <div class="mt-auto flex flex-wrap gap-2 pt-4">
                            <a href="{{ route('books.show', $book) }}" class="btn btn-secondary btn-sm">{{ __('member.details') }}</a>

                            <form method="POST" action="{{ route('waiting-lists.destroy', $book) }}"
                                  data-confirm="{{ __('member.cancel_waiting_confirmation', ['title' => $book->title]) }}"
                                  data-confirm-title="{{ __('member.cancel_waiting') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">{{ __('member.cancel_waiting') }}</button>
                            </form>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-6">{{ $entries->links() }}</div>
    @endif
@endsection
