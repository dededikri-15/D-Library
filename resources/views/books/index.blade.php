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

    {{--
        Form pencarian & filter.

        `modeParams` dikirim sebagai input tersembunyi, bukan ditanam di
        `action`. Saat method GET, browser MENGHAPUS query yang sudah ada di
        action lalu menggantinya dengan isi form — jadi `?lihat=katalog` di
        action akan hilang begitu tombol "Terapkan" ditekan dan staf terlempar
        ke mode kelola. Input tersembunyi ikut terkirim apa adanya.

        Susunannya sengaja dibuat turun-tegas: PENCARIAN (fokus utama) →
        penyaring dalam SATU baris grid → aksi di ujung kanan. Versi lama
        memecah lima dropdown jadi tiga baris terpisah, sehingga panel terasa
        bertingkat dan urutan langkahnya kabur.

        `data-submit-on-change` membuat pilihan dropdown langsung diterapkan
        (lihat `initFilterAutosubmit()` di app.js) — pengguna cukup memilih,
        tidak perlu mengingat menekan "Terapkan filter". Tombolnya tetap ada
        untuk pencarian via Enter dan untuk pengguna yang lebih suka alur manual.
    --}}
    <form method="GET" action="{{ route('books.index') }}"
          class="card mt-7 p-5 sm:p-6"
          data-submit-on-change>
        @foreach ($modeParams as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach

        {{-- Pencarian: satu-satunya kolom yang selalu dipakai, jadi dapat ruang paling luas. --}}
        <div>
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
                     (Task 14.8) — sumber markupnya sama dengan komponen
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

        {{-- Penyaring. Label tetap ada di atas tiap kolom: menghapusnya supaya
             terlihat "ringkas" justru memaksa pengguna menebak isi dropdown,
             terutama di layar kecil tempat teks placeholder terpotong. --}}
        <div class="mt-5 grid gap-4 border-t border-hairline pt-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
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

            <div>
                <label for="sort" class="field-label">{{ __('catalog.sort') }}</label>
                <select id="sort" name="sort" class="field-input">
                    <option value="latest" @selected($sort === 'latest')>{{ __('catalog.latest') }}</option>
                    <option value="title" @selected($sort === 'title')>{{ __('catalog.title_ascending') }}</option>
                    <option value="oldest" @selected($sort === 'oldest')>{{ __('catalog.oldest_publication') }}</option>
                </select>
            </div>
        </div>

        {{-- Aksi di ujung kanan: alur mata jadi pencarian → filter → tombol,
             tanpa perloncatan bolak-balik seperti versi lama. --}}
        <div class="mt-5 flex flex-wrap items-center justify-end gap-2 border-t border-hairline pt-4">
            <button type="submit" class="btn btn-primary">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16l-6 7v7l-4 2v-9L4 5Z" />
                </svg>
                {{ __('catalog.apply_filters') }}
            </button>
            <a href="{{ route('books.index', $modeParams) }}" class="btn btn-ghost">{{ __('catalog.reset') }}</a>
        </div>
    </form>

    {{--
        Filter yang sedang aktif ditampilkan sebagai chip yang bisa dilepas
        satu per satu. Tanpa ini pengguna hanya bisa menebak "kenapa hasilnya
        sedikit" lalu menekan Reset — yang membuang SEMUA pilihan sekaligus.
    --}}
    @php
        $activeFilters = [];

        if (filled($filters['q'] ?? null)) {
            $activeFilters['q'] = __('catalog.keyword').': '.$filters['q'];
        }

        if (filled($filters['category'] ?? null)) {
            $activeFilters['category'] = __('catalog.category').': '
                .($categories->firstWhere('slug', $filters['category'])?->name ?? $filters['category']);
        }

        if (filled($filters['author'] ?? null)) {
            $activeFilters['author'] = __('catalog.author').': '.$filters['author'];
        }

        if (filled($filters['publisher'] ?? null)) {
            $activeFilters['publisher'] = __('catalog.publisher').': '.$filters['publisher'];
        }

        if (filled($filters['status'] ?? null)) {
            $activeFilters['status'] = __('catalog.status').': '
                .(\App\Models\Book::statusOptions()[$filters['status']] ?? $filters['status']);
        }
    @endphp

    {{-- Ringkasan hasil --}}
    <div class="mt-7 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-secondary">
            {{ __('catalog.results_count', ['count' => number_format($books->total(), 0, ',', '.')]) }}
        </p>

        <div class="flex flex-wrap items-center gap-2">
            @foreach ($activeFilters as $key => $label)
                @php
                    // Sisanya = semua filter lain + mode tampilan + urutan,
                    // supaya melepas satu chip tidak ikut membuang pilihan lain.
                    $remaining = array_filter(
                        array_merge($modeParams, array_diff_key($filters, [$key => true])),
                        fn ($value) => filled($value)
                    );

                    if ($sort !== 'latest') {
                        $remaining['sort'] = $sort;
                    }
                @endphp
                <a href="{{ route('books.index', $remaining) }}"
                   class="group inline-flex max-w-full items-center gap-1.5 rounded-full border border-tertiary/30 bg-tertiary/10 py-1.5 pr-2.5 pl-3 text-sm font-medium text-tertiary transition-colors hover:bg-tertiary/15 focus-visible:ring-2 focus-visible:ring-tertiary/40 focus-visible:outline-none"
                   title="{{ __('catalog.remove_filter') }}">
                    <span class="max-w-[12rem] truncate">{{ $label }}</span>
                    <svg class="h-3.5 w-3.5 shrink-0 opacity-70 transition-opacity group-hover:opacity-100" fill="none"
                         viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                    <span class="sr-only">{{ __('catalog.remove_filter') }}</span>
                </a>
            @endforeach

            @if ($activeFilters !== [])
                <a href="{{ route('books.index', $modeParams) }}" class="link-accent text-sm">
                    {{ __('catalog.reset_filters') }}
                </a>
            @endif

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
    </div>

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
