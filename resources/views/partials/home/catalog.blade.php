    {{-- Buku terbaru (umum) --}}
    <section class="mt-14">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <x-section-heading
                :eyebrow="__('home.collection')"
                :title="__('home.latest_books')"
                :description="__('home.latest_description')" />
            <a href="{{ route('books.index') }}" class="link-accent text-sm">
                {{ __('home.see_all') }} &rarr;
            </a>
        </div>

        @if ($latestBooks->isEmpty())
            <x-empty-state class="mt-6" :title="__('home.empty_catalog')" :description="__('home.empty_catalog_description')" />
        @else
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($latestBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Populer (umum) --}}
    @if ($popularBooks->isNotEmpty() && $popularBooks->first()->loans_count > 0)
        <section class="mt-14">
            <x-section-heading
                :eyebrow="__('home.reader_favorites')"
                :title="__('home.popular_books')"
                :description="__('home.popular_description')" />

            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($popularBooks as $book)
                    <x-book-card :book="$book" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- Kategori (umum) --}}
    @if ($categories->isNotEmpty())
        <section class="mt-14">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <x-section-heading
                    :eyebrow="__('home.topics')"
                    :title="__('home.explore_categories')"
                    :description="__('home.categories_description')" />
                <a href="{{ route('categories.public') }}" class="link-accent text-sm">
                    {{ __('home.all_categories') }} &rarr;
                </a>
            </div>

            {{--
                Grid kartu, bukan baris pill: pill membungkus secara acak sehingga
                baris terakhir sering menyisakan ruang kosong yang janggal. Kartu
                memakai lebar sama rata, jadi jumlah buku tetap terbaca jelas dan
                area klik lebih besar di layar sentuh.

                Jeda animasi ditulis sebagai style inline, bukan
                `[animation-delay:{{ ... }}ms]`: Tailwind memindai file sebagai
                teks, jadi class yang dirangkai Blade tidak pernah menghasilkan CSS.
            --}}
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach ($categories as $category)
                    <a href="{{ route('books.index', ['category' => $category->slug]) }}"
                       class="group card flex items-center gap-3.5 p-4 transition-all duration-300 hover:-translate-y-0.5 hover:border-tertiary/40 hover:shadow-lift focus-visible:ring-2 focus-visible:ring-tertiary/40 focus-visible:outline-none motion-reduce:hover:transform-none animate-fade-up"
                       style="animation-delay: {{ $loop->index * 40 }}ms">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-lg bg-tertiary/10 text-tertiary transition-colors duration-300 group-hover:bg-tertiary/20">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z M6 6h.008v.008H6V6Z"/>
                            </svg>
                        </span>

                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-semibold text-primary">{{ $category->name }}</span>
                            <span class="mt-0.5 block text-sm text-secondary">
                                {{ __('categories.book_count', ['count' => $category->books_count]) }}
                            </span>
                        </span>

                        <svg class="h-4 w-4 shrink-0 -translate-x-1 text-tertiary opacity-0 transition-all duration-300 group-hover:translate-x-0 group-hover:opacity-100 group-focus-visible:translate-x-0 group-focus-visible:opacity-100 motion-reduce:transition-none"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"/>
                        </svg>
                    </a>
                @endforeach
            </div>
        </section>
    @endif
