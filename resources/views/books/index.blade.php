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

    {{-- Form pencarian & filter --}}
    <form method="GET" action="{{ route('books.index') }}"
          class="card mt-7 grid gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="q" class="field-label">{{ __('catalog.keyword') }}</label>
            <div class="relative mt-1.5">
                <svg class="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-secondary"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/>
                </svg>
                {{--
                    Atribut `data-search-*` diaktifkan di sini saja, bukan
                    otomatis: endpoint pratinjau hanya ada untuk katalog buku.
                    Kotak pencarian di master data tetap form biasa, karena
                    endpoint JSON-nya belum dibuat dan lebih baik form biasa
                    daripada panel yang tidak pernah berisi apa-apa.
                --}}
                <input id="q" name="q" type="search" value="{{ $filters['q'] ?? '' }}"
                       placeholder="{{ __('catalog.search_placeholder') }}"
                       class="field-input mt-0 pl-9"
                       data-search-input
                       data-search-url="{{ route('books.search') }}"
                       data-search-min="2"
                       role="combobox"
                       aria-expanded="false"
                       aria-haspopup="listbox"
                       aria-autocomplete="list"
                       aria-controls="search-results-list">

                {{-- Panel pratinjau. Kosong di HTML: isinya dirakit JS dari
                     respons JSON, jadi tidak perlu template HTML di server.

                     Tapi satu pengecualian: bentuk loading state TIDAK boleh
                     ditulis ulang sebagai string di app.js. `data-search-skeleton`
                     di bawah dirender Blade, lalu diklon JS saat request berjalan
                     (Task 14.8) — sumber markup-nya sama dengan komponen
                     loading state di halaman lain. --}}
                <div data-search-results class="search-panel" hidden></div>

                <template data-search-skeleton>
                    <x-loading-skeleton :rows="3" :label="__('catalog.loading_search')" />
                </template>
            </div>
            <p class="mt-1.5 text-label text-secondary">
                {{ __('catalog.search_hint') }}
            </p>
        </div>

        <div>
            <label for="category" class="field-label">{{ __('catalog.category') }}</label>
            <select id="category" name="category" class="field-input">
                <option value="">{{ __('catalog.all_categories') }}</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->slug }}" @selected(($filters['category'] ?? null) === $category->slug)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="author" class="field-label">{{ __('catalog.author') }}</label>
            <select id="author" name="author" class="field-input">
                <option value="">{{ __('catalog.all_authors') }}</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->name }}" @selected(($filters['author'] ?? null) === $author->name)>
                        {{ $author->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="publisher" class="field-label">{{ __('catalog.publisher') }}</label>
            <select id="publisher" name="publisher" class="field-input">
                <option value="">{{ __('catalog.all_publishers') }}</option>
                @foreach ($publishers as $publisher)
                    <option value="{{ $publisher->name }}" @selected(($filters['publisher'] ?? null) === $publisher->name)>
                        {{ $publisher->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="status" class="field-label">{{ __('catalog.status') }}</label>
            <select id="status" name="status" class="field-input">
                <option value="">{{ __('catalog.all_statuses') }}</option>
                <option value="available" @selected(($filters['status'] ?? null) === 'available')>{{ __('catalog.available') }}</option>
                <option value="borrowed" @selected(($filters['status'] ?? null) === 'borrowed')>{{ __('catalog.borrowed') }}</option>
            </select>
        </div>

        <div class="sm:col-span-2 lg:col-span-4">
            <label for="sort" class="field-label">{{ __('catalog.sort') }}</label>
            <select id="sort" name="sort" class="field-input mt-1.5 lg:max-w-xs">
                <option value="latest" @selected($sort === 'latest')>{{ __('catalog.latest') }}</option>
                <option value="title" @selected($sort === 'title')>{{ __('catalog.title_ascending') }}</option>
                <option value="oldest" @selected($sort === 'oldest')>{{ __('catalog.oldest_publication') }}</option>
            </select>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:col-span-2 lg:col-span-4">
            <button type="submit" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                </svg>
                {{ __('catalog.apply_filters') }}
            </button>
            <a href="{{ route('books.index') }}" class="btn btn-ghost">{{ __('catalog.reset') }}</a>
        </div>
    </form>

    {{-- Ringkasan hasil --}}
    <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-secondary">
            {{ __('catalog.results_count', ['count' => number_format($books->total(), 0, ',', '.')]) }}
        </p>

        @if ($sort !== 'latest')
            <span class="badge badge-muted">
                {{ __('catalog.sorted_as', ['sort' => [
                    'latest' => __('catalog.latest'),
                    'title' => __('catalog.title_ascending'),
                    'oldest' => __('catalog.oldest_publication'),
                ][$sort]]) }}
            </span>
        @endif
    </div>

    @if ($books->isEmpty())
        <x-empty-state class="mt-6"
                       :title="__('catalog.book_not_found')"
                       :description="__('catalog.empty_description')">
            @if ($isManagement)
                <a href="{{ route('books.create') }}" class="btn btn-primary btn-sm">{{ __('catalog.add_book') }}</a>
            @else
                <a href="{{ route('books.index') }}" class="btn btn-secondary btn-sm">{{ __('catalog.reset_filters') }}</a>
            @endif
        </x-empty-state>
    @else
        <div class="mt-5 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($books as $book)
                <div class="flex min-w-0 flex-col gap-3">
                    <x-book-card :book="$book" />
                    <x-book-quick-view :book="$book" />
                    @if ($isManagement)
                        <a href="{{ route('books.edit', $book) }}" class="btn btn-secondary btn-sm w-full">
                            {{ __('catalog.edit_book') }}
                        </a>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-10">
            {{ $books->links() }}
        </div>
    @endif
@endsection
