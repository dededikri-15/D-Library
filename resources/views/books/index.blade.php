@extends('layouts.app')

@section('title', __('catalog.page_title').' - '.config('app.name'))

@section('content')
    {{-- Kepala halaman --}}
    <header class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <p class="section-eyebrow">{{ __($isManagement ? 'catalog.management_eyebrow' : 'catalog.collection_eyebrow') }}</p>
            <h1 class="mt-2 text-h1 font-bold tracking-tight text-primary">
                {{ __($isManagement ? 'catalog.management_title' : 'catalog.title') }}
            </h1>
            <p class="mt-1.5 text-secondary">
                {{ __($isManagement ? 'catalog.management_description' : 'catalog.description') }}
            </p>
        </div>

        @if ($isManagement)
            <a href="{{ route('books.create') }}" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                {{ __('catalog.add_book') }}
            </a>
        @endif
    </header>

    @include('partials.catalog-filter', [
        'modeParams' => $modeParams,
        'filters' => $filters,
        'categories' => $categories,
        'authors' => $authors,
        'publishers' => $publishers,
        'sort' => $sort,
        'isManagement' => $isManagement,
    ])

    @include('partials.catalog-chips', [
        'modeParams' => $modeParams,
        'filters' => $filters,
        'categories' => $categories,
        'books' => $books,
        'sort' => $sort,
    ])

    @if ($books->isEmpty())
        <x-empty-state class="mt-6"
                       :title="__('catalog.book_not_found')"
                       :description="__('catalog.empty_description')">
            @if ($isManagement)
                <a href="{{ route('books.create') }}" class="btn btn-primary btn-sm">{{ __('catalog.add_book') }}</a>
            @else
                <a href="{{ route('books.index', $modeParams) }}" class="btn btn-secondary btn-sm">{{ __('catalog.reset_filters') }}</a>
            @endif
        </x-empty-state>
    @else
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($books as $book)
                {{--
                    Jeda masuk memakai style inline, bukan `[animation-delay:…]`:
                    Tailwind memindai Blade sebagai teks, jadi class yang
                    dirangkai tidak pernah menghasilkan CSS.
                --}}
                <div class="flex min-w-0 flex-col gap-3 animate-fade-up" style="animation-delay: {{ min($loop->index * 40, 320) }}ms">
                    {{--
                        Pratinjau + Edit sengaja dikirim lewat slot supaya
                        masuk ke dalam kartu (x-book-card): tombol yang
                        mengendalikan kartu tidak boleh berdiri sendiri di
                        luar kartunya.
                    --}}
                    <x-book-card :book="$book" :mode-params="$modeParams">
                        <x-book-quick-view :book="$book" :mode-params="$modeParams" />
                        @if ($isManagement)
                            <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary btn-sm w-full">
                                {{ __('catalog.edit_book') }}
                            </a>
                        @endif
                    </x-book-card>
                </div>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $books->links() }}
        </div>
    @endif
@endsection
