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
