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
